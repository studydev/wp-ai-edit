<?php

declare(strict_types=1);

namespace WpAiEdit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class RestApi {

    private static ?self $instance = null;

    private const NAMESPACE = 'wp-ai-edit/v1';

    private const VALID_ACTIONS = [ 'command', 'write_more', 'my_style', 'highlight', 'summarize', 'analogy', 'grammar', 'seo_helper' ];

    private const MAX_TOKENS = [
        'command'    => 2048,
        'write_more' => 1024,
        'my_style'   => 2048,
        'highlight'  => 2048,
        'summarize'  => 512,
        'analogy'    => 2048,
        'grammar'    => 2048,
        'seo_helper' => 1024,
    ];

    private const MAX_TEXT_LENGTH = 50000;

    private const RATE_LIMIT_SECONDS = 5;

    public static function get_instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        register_rest_route( self::NAMESPACE, '/generate', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_generate' ],
            'permission_callback' => static fn (): bool => current_user_can( 'edit_posts' ),
            'args'                => [
                'action'  => [
                    'required'          => true,
                    'type'              => 'string',
                    'enum'              => self::VALID_ACTIONS,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'text'    => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'wp_kses_post',
                    'validate_callback' => static function ( $value ): bool {
                        return is_string( $value ) && mb_strlen( $value ) <= self::MAX_TEXT_LENGTH;
                    },
                ],
                'command' => [
                    'required'          => false,
                    'type'              => 'string',
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/test-connection', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_test_connection' ],
            'permission_callback' => static fn (): bool => current_user_can( 'manage_options' ),
        ] );

        register_rest_route( self::NAMESPACE, '/prompts', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'handle_get_prompts' ],
            'permission_callback' => static fn (): bool => current_user_can( 'edit_posts' ),
        ] );
    }

    public function handle_generate( \WP_REST_Request $request ): void {
        // Rate limit per user
        $user_id   = get_current_user_id();
        $throttle  = 'wp_ai_edit_rate_' . $user_id;
        if ( get_transient( $throttle ) ) {
            OpenAIClient::init_sse_headers();
            OpenAIClient::send_sse_error(
                __( 'Too many requests. Please wait a few seconds before trying again.', 'wp-ai-edit' )
            );
            exit;
        }
        set_transient( $throttle, true, self::RATE_LIMIT_SECONDS );

        $action  = $request->get_param( 'action' );
        $text    = $request->get_param( 'text' );
        $command = $request->get_param( 'command' ) ?? '';

        $client = OpenAIClient::from_settings();
        if ( $client === null ) {
            OpenAIClient::init_sse_headers();
            OpenAIClient::send_sse_error(
                __( 'API is not configured. Please set up the API in WP AI Edit settings.', 'wp-ai-edit' )
            );
            exit;
        }

        $messages   = PromptManager::build_messages( $action, $text, $command );
        $max_tokens = self::MAX_TOKENS[ $action ] ?? 2048;

        OpenAIClient::init_sse_headers();
        $client->stream_chat( $messages, $max_tokens );
        exit;
    }

    public function handle_test_connection( \WP_REST_Request $request ): \WP_REST_Response {
        $params   = $request->get_json_params();
        $settings = get_option( 'wp_ai_edit_settings', [] );

        if ( ! is_array( $settings ) ) {
            $settings = [];
        }

        $provider = OpenAIClient::normalize_provider(
            sanitize_text_field( (string) ( $params['provider'] ?? ( $settings['provider'] ?? OpenAIClient::get_default_provider() ) ) )
        );
        $endpoint = sanitize_url( (string) ( $params['endpoint'] ?? ( $settings['endpoint'] ?? '' ) ) );
        $model    = sanitize_text_field( (string) ( $params['model'] ?? ( $settings['model'] ?? 'gpt-5.4' ) ) );
        $api_key  = sanitize_text_field( (string) ( $params['api_key'] ?? '' ) );

        if ( $api_key === '' || $api_key === '••••••••' ) {
            $encrypted = (string) ( $settings['api_key_encrypted'] ?? '' );
            $api_key   = OpenAIClient::decrypt_api_key( $encrypted ) ?? '';
        }

        $client = OpenAIClient::from_config( $provider, $endpoint, $api_key, $model );

        if ( $client === null ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'API is not configured.', 'wp-ai-edit' ),
            ], 400 );
        }

        $result = $client->test_connection();

        return new \WP_REST_Response( $result, $result['success'] ? 200 : 400 );
    }

    public function handle_get_prompts( \WP_REST_Request $request ): \WP_REST_Response {
        return new \WP_REST_Response( [
            'prompts'          => PromptManager::get_all(),
            'defaults'         => PromptManager::get_defaults(),
            'labels'           => PromptManager::get_action_labels(),
            'tailPrompt'       => PromptManager::get_tail_prompt(),
            'defaultTailPrompt' => PromptManager::get_default_tail_prompt(),
            'noTailActions'    => PromptManager::get_no_tail_actions(),
        ] );
    }
}
