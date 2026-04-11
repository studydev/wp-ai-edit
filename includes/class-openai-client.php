<?php

declare(strict_types=1);

namespace WpAiEdit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class OpenAIClient {

    public const PROVIDER_AZURE_OPENAI = 'azure_openai';
    public const PROVIDER_OPENAI       = 'openai';
    public const PROVIDER_ANTHROPIC    = 'anthropic';

    private const OPENAI_API_ENDPOINT    = 'https://api.openai.com/v1';
    private const ANTHROPIC_API_ENDPOINT = 'https://api.anthropic.com/v1';
    private const ANTHROPIC_VERSION      = '2023-06-01';
    private const DEFAULT_MODEL          = 'gpt-5.4';

    /**
     * @var array<string, array<string, array{label: string, price: string}>>
     */
    private const PROVIDER_MODELS = [
        self::PROVIDER_AZURE_OPENAI => [
            'gpt-5.4-nano' => [ 'label' => 'GPT-5.4 Nano', 'price' => '$0.20 / $1.25' ],
            'gpt-5.4-mini' => [ 'label' => 'GPT-5.4 Mini', 'price' => '$0.75 / $4.50' ],
            'gpt-5.4'      => [ 'label' => 'GPT-5.4',      'price' => '$2.50 / $15' ],
            'gpt-5.4-pro'  => [ 'label' => 'GPT-5.4 Pro',  'price' => '$30 / $180' ],
        ],
        self::PROVIDER_OPENAI => [
            'gpt-5.4-nano' => [ 'label' => 'GPT-5.4 Nano', 'price' => '$0.20 / $1.25' ],
            'gpt-5.4-mini' => [ 'label' => 'GPT-5.4 Mini', 'price' => '$0.75 / $4.50' ],
            'gpt-5.4'      => [ 'label' => 'GPT-5.4',      'price' => '$2.50 / $15' ],
            'gpt-5.4-pro'  => [ 'label' => 'GPT-5.4 Pro',  'price' => '$30 / $180' ],
        ],
        self::PROVIDER_ANTHROPIC => [
            'claude-haiku-4-5'  => [ 'label' => 'Claude Haiku 4.5',  'price' => '$1 / $5' ],
            'claude-sonnet-4-6' => [ 'label' => 'Claude Sonnet 4.6', 'price' => '$3 / $15' ],
            'claude-opus-4-6'   => [ 'label' => 'Claude Opus 4.6',   'price' => '$5 / $25' ],
        ],
    ];

    private readonly string $provider;
    private readonly string $endpoint;
    private readonly string $api_key;
    private readonly string $model;

    public function __construct( string $provider, string $endpoint, string $api_key, string $model ) {
        $this->provider = self::normalize_provider( $provider );
        $this->endpoint = rtrim( $endpoint, '/' );
        $this->api_key  = $api_key;
        $this->model    = $model;
    }

    public static function get_default_provider(): string {
        return self::PROVIDER_AZURE_OPENAI;
    }

	public static function get_default_model(): string {
		return self::DEFAULT_MODEL;
	}

    /**
     * @return string[]
     */
    public static function get_supported_providers(): array {
        return [
            self::PROVIDER_AZURE_OPENAI,
            self::PROVIDER_OPENAI,
            self::PROVIDER_ANTHROPIC,
        ];
    }

    public static function normalize_provider( string $provider ): string {
        return in_array( $provider, self::get_supported_providers(), true )
            ? $provider
            : self::get_default_provider();
    }

    /**
     * @return array<string, string>
     */
    public static function get_supported_models(): array {
        $all = [];
        foreach ( self::PROVIDER_MODELS as $models ) {
            foreach ( $models as $key => $meta ) {
                $all[ $key ] = $meta['label'];
            }
        }
        return $all;
    }

    /**
     * @return array<string, array{label: string, price: string}>
     */
    public static function get_models_for_provider( string $provider ): array {
        $normalized_provider = self::normalize_provider( $provider );
        return self::PROVIDER_MODELS[ $normalized_provider ] ?? self::PROVIDER_MODELS[ self::get_default_provider() ];
    }

    public static function get_default_model_for_provider( string $provider ): string {
        $models = self::get_models_for_provider( $provider );
        $keys   = array_keys( $models );
        return $keys[0] ?? self::DEFAULT_MODEL;
    }

    public static function normalize_model( string $model ): string {
        $normalized_model = sanitize_text_field( $model );

        return array_key_exists( $normalized_model, self::get_supported_models() )
            ? $normalized_model
            : self::get_default_model();
    }

    public static function normalize_model_for_provider( string $model, string $provider ): string {
        $normalized_model = sanitize_text_field( $model );
        $provider_models  = self::get_models_for_provider( $provider );

        if ( array_key_exists( $normalized_model, $provider_models ) ) {
            return $normalized_model;
        }

        return self::get_default_model_for_provider( $provider );
    }

    public static function get_model_label( string $model ): string {
        $all = self::get_supported_models();
        return $all[ $model ] ?? $model;
    }

    public static function get_provider_label( string $provider ): string {
        $normalized_provider = self::normalize_provider( $provider );

        if ( $normalized_provider === self::PROVIDER_ANTHROPIC ) {
            return __( 'Anthropic', 'wp-ai-edit' );
        }

        if ( $normalized_provider === self::PROVIDER_OPENAI ) {
            return __( 'OpenAI', 'wp-ai-edit' );
        }

        return __( 'Azure OpenAI', 'wp-ai-edit' );
    }

    public static function get_provider_icon_url( string $provider ): string {
        $normalized_provider = self::normalize_provider( $provider );

        if ( $normalized_provider === self::PROVIDER_ANTHROPIC ) {
            return WP_AI_EDIT_URL . 'assets/icons/anthropic.png';
        }

        if ( $normalized_provider === self::PROVIDER_OPENAI ) {
            return WP_AI_EDIT_URL . 'assets/icons/openai.png';
        }

        return WP_AI_EDIT_URL . 'assets/icons/azure-openai.png';
    }

    public static function get_provider_short_label( string $provider ): string {
        $normalized_provider = self::normalize_provider( $provider );

        if ( $normalized_provider === self::PROVIDER_ANTHROPIC ) {
            return 'Claude';
        }

        if ( $normalized_provider === self::PROVIDER_OPENAI ) {
            return 'OpenAI';
        }

        return 'AOAI';
    }

    /**
     * Whether this provider requires a user-configurable endpoint URL.
     */
    public static function provider_needs_endpoint( string $provider ): bool {
        $normalized_provider = self::normalize_provider( $provider );
        return $normalized_provider !== self::PROVIDER_ANTHROPIC;
    }

    public static function get_provider_endpoint_placeholder( string $provider ): string {
        $normalized_provider = self::normalize_provider( $provider );

        if ( $normalized_provider === self::PROVIDER_OPENAI ) {
            return self::get_default_endpoint_for_provider( $normalized_provider );
        }

        return 'https://your-resource.openai.azure.com';
    }

    public static function get_provider_endpoint_help( string $provider ): string {
        $normalized_provider = self::normalize_provider( $provider );

        if ( $normalized_provider === self::PROVIDER_OPENAI ) {
            return __( 'OpenAI uses https://api.openai.com/v1 by default. Leave the endpoint blank to use it, or enter a compatible base URL.', 'wp-ai-edit' );
        }

        return __( 'Enter your Azure OpenAI resource URL (e.g. https://your-resource.openai.azure.com). The path /openai/v1 is added automatically.', 'wp-ai-edit' );
    }

    /**
     * @return array{endpoint: string, api_key_encrypted: string, model: string}
     */
    public static function get_empty_provider_settings(): array {
        return [
            'endpoint'          => '',
            'api_key_encrypted' => '',
            'model'             => self::get_default_model(),
        ];
    }

    /**
     * @param array<string, mixed> $settings
     */
    public static function get_active_provider_from_settings( array $settings ): string {
        return self::normalize_provider(
            (string) ( $settings['active_provider'] ?? $settings['provider'] ?? self::get_default_provider() )
        );
    }

    /**
     * @param array<string, mixed> $settings
     * @return array{endpoint: string, api_key_encrypted: string, model: string}
     */
    public static function get_provider_settings_from_settings( array $settings, string $provider ): array {
        $normalized_provider = self::normalize_provider( $provider );
        $defaults            = self::get_empty_provider_settings();
        $config              = $defaults;
        $providers           = $settings['providers'] ?? [];
        $has_model           = false;

        if ( is_array( $providers ) ) {
            $provider_settings = $providers[ $normalized_provider ] ?? null;

            if ( is_array( $provider_settings ) ) {
                $config['endpoint']          = sanitize_url( (string) ( $provider_settings['endpoint'] ?? '' ) );
                $config['api_key_encrypted'] = (string) ( $provider_settings['api_key_encrypted'] ?? '' );
                $config['model']             = self::normalize_model( (string) ( $provider_settings['model'] ?? self::get_default_model() ) );
                $has_model                   = array_key_exists( 'model', $provider_settings );
            }
        }

        $legacy_provider = self::normalize_provider( (string) ( $settings['provider'] ?? self::get_default_provider() ) );

        if ( $normalized_provider === $legacy_provider ) {
            if ( $config['endpoint'] === '' ) {
                $config['endpoint'] = sanitize_url( (string) ( $settings['endpoint'] ?? '' ) );
            }

            if ( $config['api_key_encrypted'] === '' ) {
                $config['api_key_encrypted'] = (string) ( $settings['api_key_encrypted'] ?? '' );
            }

            if ( ! $has_model && isset( $settings['model'] ) ) {
                $config['model'] = self::normalize_model( (string) $settings['model'] );
            }
        }

        return $config;
    }

    public static function get_default_endpoint_for_provider( string $provider ): string {
        $normalized_provider = self::normalize_provider( $provider );

        if ( $normalized_provider === self::PROVIDER_ANTHROPIC ) {
            return self::ANTHROPIC_API_ENDPOINT;
        }

        if ( $normalized_provider === self::PROVIDER_OPENAI ) {
            return self::OPENAI_API_ENDPOINT;
        }

        return '';
    }

    /**
     * Validate that the endpoint URL is safe (HTTPS only, no private IPs).
     */
    public static function validate_endpoint( string $url ): bool {
        $parsed = wp_parse_url( $url );
        if ( ! is_array( $parsed ) ) {
            return false;
        }

        // Must be HTTPS
        $scheme = strtolower( $parsed['scheme'] ?? '' );
        if ( $scheme !== 'https' ) {
            return false;
        }

        $host = $parsed['host'] ?? '';
        if ( $host === '' ) {
            return false;
        }

        // Block private/reserved IP ranges
        $ip = gethostbyname( $host );
        if ( $ip !== $host ) { // resolved to IP
            $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
            if ( filter_var( $ip, FILTER_VALIDATE_IP, $flags ) === false ) {
                return false;
            }
        }

        // Block localhost variants
        $lower_host = strtolower( $host );
        if ( in_array( $lower_host, [ 'localhost', '127.0.0.1', '::1', '0.0.0.0' ], true ) ) {
            return false;
        }

        return true;
    }

    public static function from_settings(): ?self {
        $settings = get_option( 'wp_ai_edit_settings', [] );

        if ( ! is_array( $settings ) ) {
            return null;
        }

        $provider          = self::get_active_provider_from_settings( $settings );
        $provider_settings = self::get_provider_settings_from_settings( $settings, $provider );
        $endpoint          = self::normalize_endpoint( $provider, $provider_settings['endpoint'] );
        $encrypted         = $provider_settings['api_key_encrypted'];
        $model             = self::normalize_model( $provider_settings['model'] );

        if ( $endpoint === '' || $encrypted === '' ) {
            return null;
        }

        if ( ! self::validate_endpoint( $endpoint ) ) {
            return null;
        }

        $api_key = self::decrypt_api_key( $encrypted );
        if ( $api_key === null ) {
            return null;
        }

        return new self( $provider, $endpoint, $api_key, $model );
    }

    public static function from_config( string $provider, string $endpoint, string $api_key, string $model ): ?self {
        $normalized_provider = self::normalize_provider( $provider );
        $normalized_endpoint = self::normalize_endpoint( $normalized_provider, $endpoint );
        $normalized_model    = self::normalize_model( $model );

        if ( $normalized_endpoint === '' || $api_key === '' || $normalized_model === '' ) {
            return null;
        }

        if ( ! self::validate_endpoint( $normalized_endpoint ) ) {
            return null;
        }

        return new self( $normalized_provider, $normalized_endpoint, $api_key, $normalized_model );
    }

    private static function normalize_endpoint( string $provider, string $endpoint ): string {
        $normalized_endpoint = rtrim( sanitize_url( $endpoint ), '/' );

        if ( $normalized_endpoint === '' ) {
            return self::get_default_endpoint_for_provider( $provider );
        }

        // Auto-append /openai/v1 for Azure OpenAI base URLs.
        if ( $provider === self::PROVIDER_AZURE_OPENAI ) {
            $normalized_endpoint = self::ensure_azure_path( $normalized_endpoint );
        }

        return $normalized_endpoint;
    }

    /**
     * Ensure Azure OpenAI endpoint includes the /openai/v1 path.
     */
    private static function ensure_azure_path( string $url ): string {
        $parsed = wp_parse_url( $url );
        $path   = rtrim( $parsed['path'] ?? '', '/' );

        // Already has the full path.
        if ( str_ends_with( $path, '/openai/v1' ) ) {
            return $url;
        }

        // Has /openai but not /v1.
        if ( str_ends_with( $path, '/openai' ) ) {
            return $url . '/v1';
        }

        // Base URL only (e.g. https://xxx.openai.azure.com).
        return rtrim( $url, '/' ) . '/openai/v1';
    }

    /**
     * Stream a vision (image analysis) chat completion via SSE.
     *
     * Injects the image into the messages in the appropriate provider format,
     * then delegates to the standard stream_chat method.
     *
     * @param array{role: string, content: string}[] $messages
     */
    public function stream_chat_vision( array $messages, string $image_url, int $max_tokens = 2048 ): void {
        $messages = $this->inject_image_into_messages( $messages, $image_url );
        if ( $messages === null ) {
            self::send_sse_error( __( 'Failed to process the image for analysis.', 'wp-ai-edit' ) );
            return;
        }
        $this->stream_chat( $messages, $max_tokens );
    }

    /**
     * Convert the last user message to multi-modal format with image content.
     *
     * For OpenAI / Azure OpenAI: uses image_url type.
     * For Anthropic: downloads the image and encodes as base64.
     *
     * @param array{role: string, content: string}[] $messages
     * @return array{role: string, content: mixed}[]|null
     */
    private function inject_image_into_messages( array $messages, string $image_url ): ?array {
        for ( $i = count( $messages ) - 1; $i >= 0; $i-- ) {
            if ( $messages[ $i ]['role'] !== 'user' ) {
                continue;
            }

            $text = (string) $messages[ $i ]['content'];

            if ( $this->provider === self::PROVIDER_ANTHROPIC ) {
                $image_data = $this->download_image_as_base64( $image_url );
                if ( $image_data === null ) {
                    return null;
                }
                $messages[ $i ]['content'] = [
                    [
                        'type'   => 'image',
                        'source' => [
                            'type'       => 'base64',
                            'media_type' => $image_data['media_type'],
                            'data'       => $image_data['data'],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'text' => $text,
                    ],
                ];
            } else {
                $messages[ $i ]['content'] = [
                    [
                        'type' => 'text',
                        'text' => $text,
                    ],
                    [
                        'type'      => 'image_url',
                        'image_url' => [ 'url' => $image_url ],
                    ],
                ];
            }

            break;
        }

        return $messages;
    }

    /**
     * Download an image and return its base64-encoded data.
     *
     * @return array{media_type: string, data: string}|null
     */
    private function download_image_as_base64( string $url ): ?array {
        $parsed = wp_parse_url( $url );
        $scheme = strtolower( $parsed['scheme'] ?? '' );
        if ( ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
            return null;
        }

        $response = wp_remote_get( $url, [
            'timeout'     => 30,
            'redirection' => 3,
        ] );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            return null;
        }

        $body         = wp_remote_retrieve_body( $response );
        $content_type = wp_remote_retrieve_header( $response, 'content-type' );

        $allowed_types = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];
        $media_type    = explode( ';', (string) $content_type )[0];

        if ( ! in_array( $media_type, $allowed_types, true ) ) {
            return null;
        }

        // Limit to 20 MB
        if ( strlen( $body ) > 20 * 1024 * 1024 ) {
            return null;
        }

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        return [
            'media_type' => $media_type,
            'data'       => base64_encode( $body ),
        ];
    }

    /**
     * Stream a chat completion response via SSE.
     *
     * @param array{role: string, content: string}[] $messages
     */
    public function stream_chat( array $messages, int $max_tokens = 2048 ): void {
        if ( $this->provider === self::PROVIDER_ANTHROPIC ) {
            $this->stream_chat_anthropic( $messages, $max_tokens );
            return;
        }

        $url = $this->endpoint . '/chat/completions';

        $body = wp_json_encode( [
            'model'                => $this->model,
            'messages'             => $messages,
            'stream'               => true,
            'max_completion_tokens' => $max_tokens,
        ] );

        if ( $body === false ) {
            self::send_sse_error( __( 'Failed to encode request body.', 'wp-ai-edit' ) );
            return;
        }

        $ch = curl_init();

        if ( $ch === false ) {
            self::send_sse_error( __( 'Failed to initialize cURL.', 'wp-ai-edit' ) );
            return;
        }

        curl_setopt_array( $ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $this->get_curl_headers(),
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_WRITEFUNCTION  => static function ( $ch, string $data ) : int {
                $lines = explode( "\n", $data );

                foreach ( $lines as $line ) {
                    $line = trim( $line );

                    if ( $line === '' ) {
                        continue;
                    }

                    if ( ! str_starts_with( $line, 'data: ' ) ) {
                        continue;
                    }

                    $json_str = substr( $line, 6 );

                    if ( $json_str === '[DONE]' ) {
                        echo "data: [DONE]\n\n";
                        self::flush_output();
                        continue;
                    }

                    $payload = json_decode( $json_str, true );
                    if ( ! is_array( $payload ) ) {
                        continue;
                    }

                    $delta = $payload['choices'][0]['delta']['content'] ?? null;
                    if ( $delta !== null ) {
                        $sse_data = wp_json_encode( [ 'content' => $delta ] );
                        echo "data: {$sse_data}\n\n";
                        self::flush_output();
                    }
                }

                return strlen( $data );
            },
        ] );

        $result = curl_exec( $ch );

        if ( $result === false ) {
            $error = curl_error( $ch );
            $errno = curl_errno( $ch );
            curl_close( $ch );
            self::send_sse_error(
                sprintf(
                    /* translators: 1: error number, 2: error message */
                    __( 'cURL error (%1$d): %2$s', 'wp-ai-edit' ),
                    $errno,
                    $error
                )
            );
            return;
        }

        $http_code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );

        if ( $http_code >= 400 ) {
            self::send_sse_error(
                sprintf(
                    /* translators: %d: HTTP status code */
                    __( 'API error: HTTP %d', 'wp-ai-edit' ),
                    $http_code
                )
            );
        }
    }

    /**
     * Test the API connection with a simple request.
     *
     * @return array{success: bool, message: string}
     */
    public function test_connection(): array {
        if ( $this->provider === self::PROVIDER_ANTHROPIC ) {
            return $this->test_connection_anthropic();
        }

        $url = $this->endpoint . '/chat/completions';

        $body = wp_json_encode( [
            'model'      => $this->model,
            'messages'   => [
                [ 'role' => 'user', 'content' => 'Say "OK".' ],
            ],
            'max_completion_tokens' => 5,
        ] );

        $response = wp_remote_post( $url, [
            'timeout' => 15,
            'headers' => $this->get_wp_headers(),
            'body' => $body,
        ] );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body_str = wp_remote_retrieve_body( $response );

        if ( $code === 200 ) {
            return [
                'success' => true,
                'message' => __( 'Connection successful!', 'wp-ai-edit' ),
            ];
        }

        $decoded = json_decode( $body_str, true );
        $error_msg = $decoded['error']['message'] ?? $body_str;

        return [
            'success' => false,
            'message' => sprintf(
                /* translators: 1: HTTP code, 2: error message */
                __( 'HTTP %1$d: %2$s', 'wp-ai-edit' ),
                $code,
                $error_msg
            ),
        ];
    }

    /**
     * @return string[]
     */
    private function get_curl_headers(): array {
        $headers = [
            'Content-Type: application/json',
        ];

        if ( $this->provider === self::PROVIDER_ANTHROPIC ) {
            $headers[] = 'x-api-key: ' . $this->api_key;
            $headers[] = 'anthropic-version: ' . self::ANTHROPIC_VERSION;
            return $headers;
        }

        if ( $this->provider === self::PROVIDER_OPENAI ) {
            $headers[] = 'Authorization: Bearer ' . $this->api_key;
            return $headers;
        }

        $headers[] = 'api-key: ' . $this->api_key;

        return $headers;
    }

    /**
     * @return array<string, string>
     */
    private function get_wp_headers(): array {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        if ( $this->provider === self::PROVIDER_ANTHROPIC ) {
            $headers['x-api-key']          = $this->api_key;
            $headers['anthropic-version']  = self::ANTHROPIC_VERSION;
            return $headers;
        }

        if ( $this->provider === self::PROVIDER_OPENAI ) {
            $headers['Authorization'] = 'Bearer ' . $this->api_key;
            return $headers;
        }

        $headers['api-key'] = $this->api_key;

        return $headers;
    }

    // ── Anthropic-specific methods ──

    /**
     * Extract system prompt from messages and return Anthropic-formatted request parts.
     *
     * @param array{role: string, content: string}[] $messages
     * @return array{system: string, messages: array{role: string, content: string}[]}
     */
    private static function split_system_for_anthropic( array $messages ): array {
        $system       = '';
        $user_messages = [];

        foreach ( $messages as $msg ) {
            if ( $msg['role'] === 'system' ) {
                $system .= ( $system !== '' ? "\n\n" : '' ) . $msg['content'];
            } else {
                $user_messages[] = $msg;
            }
        }

        return [
            'system'   => $system,
            'messages' => $user_messages,
        ];
    }

    /**
     * @param array{role: string, content: string}[] $messages
     */
    private function stream_chat_anthropic( array $messages, int $max_tokens ): void {
        $url   = $this->endpoint . '/messages';
        $parts = self::split_system_for_anthropic( $messages );

        $payload = [
            'model'      => $this->model,
            'max_tokens' => $max_tokens,
            'messages'   => $parts['messages'],
            'stream'     => true,
        ];

        if ( $parts['system'] !== '' ) {
            $payload['system'] = $parts['system'];
        }

        $body = wp_json_encode( $payload );

        if ( $body === false ) {
            self::send_sse_error( __( 'Failed to encode request body.', 'wp-ai-edit' ) );
            return;
        }

        $ch = curl_init();

        if ( $ch === false ) {
            self::send_sse_error( __( 'Failed to initialize cURL.', 'wp-ai-edit' ) );
            return;
        }

        curl_setopt_array( $ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $this->get_curl_headers(),
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_WRITEFUNCTION  => static function ( $ch, string $data ) : int {
                $lines = explode( "\n", $data );

                foreach ( $lines as $line ) {
                    $line = trim( $line );

                    if ( $line === '' || str_starts_with( $line, 'event: ' ) ) {
                        continue;
                    }

                    if ( ! str_starts_with( $line, 'data: ' ) ) {
                        continue;
                    }

                    $json_str = substr( $line, 6 );
                    $payload  = json_decode( $json_str, true );

                    if ( ! is_array( $payload ) ) {
                        continue;
                    }

                    $type = $payload['type'] ?? '';

                    if ( $type === 'content_block_delta' ) {
                        $text = $payload['delta']['text'] ?? null;
                        if ( $text !== null ) {
                            $sse_data = wp_json_encode( [ 'content' => $text ] );
                            echo "data: {$sse_data}\n\n";
                            self::flush_output();
                        }
                    } elseif ( $type === 'message_stop' ) {
                        echo "data: [DONE]\n\n";
                        self::flush_output();
                    } elseif ( $type === 'error' ) {
                        $error_msg = $payload['error']['message'] ?? 'Unknown Anthropic error';
                        self::send_sse_error( $error_msg );
                    }
                }

                return strlen( $data );
            },
        ] );

        $result = curl_exec( $ch );

        if ( $result === false ) {
            $error = curl_error( $ch );
            $errno = curl_errno( $ch );
            curl_close( $ch );
            self::send_sse_error(
                sprintf(
                    __( 'cURL error (%1$d): %2$s', 'wp-ai-edit' ),
                    $errno,
                    $error
                )
            );
            return;
        }

        $http_code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );

        if ( $http_code >= 400 ) {
            self::send_sse_error(
                sprintf(
                    __( 'API error: HTTP %d', 'wp-ai-edit' ),
                    $http_code
                )
            );
        }
    }

    /**
     * @return array{success: bool, message: string}
     */
    private function test_connection_anthropic(): array {
        $url = $this->endpoint . '/messages';

        $body = wp_json_encode( [
            'model'      => $this->model,
            'max_tokens' => 5,
            'messages'   => [
                [ 'role' => 'user', 'content' => 'Say "OK".' ],
            ],
        ] );

        $response = wp_remote_post( $url, [
            'timeout' => 15,
            'headers' => $this->get_wp_headers(),
            'body'    => $body,
        ] );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }

        $code     = wp_remote_retrieve_response_code( $response );
        $body_str = wp_remote_retrieve_body( $response );

        if ( $code === 200 ) {
            return [
                'success' => true,
                'message' => __( 'Connection successful!', 'wp-ai-edit' ),
            ];
        }

        $decoded   = json_decode( $body_str, true );
        $error_msg = $decoded['error']['message'] ?? $body_str;

        return [
            'success' => false,
            'message' => sprintf(
                __( 'HTTP %1$d: %2$s', 'wp-ai-edit' ),
                $code,
                $error_msg
            ),
        ];
    }

    // ── Encryption helpers ──

    public static function encrypt_api_key( string $plain_key ): ?string {
        $secret = self::get_encryption_key();
        $nonce  = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
        $cipher = sodium_crypto_secretbox( $plain_key, $nonce, $secret );
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        return base64_encode( $nonce . $cipher );
    }

    public static function decrypt_api_key( string $encrypted ): ?string {
        $secret  = self::get_encryption_key();
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
        $decoded = base64_decode( $encrypted, true );

        if ( $decoded === false || strlen( $decoded ) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES ) {
            return null;
        }

        $nonce  = substr( $decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
        $cipher = substr( $decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
        $plain  = sodium_crypto_secretbox_open( $cipher, $nonce, $secret );

        return $plain === false ? null : $plain;
    }

    private static function get_encryption_key(): string {
        if ( defined( 'WP_AI_EDIT_ENCRYPTION_KEY' ) ) {
            // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
            return base64_decode( WP_AI_EDIT_ENCRYPTION_KEY );
        }

        $stored = get_option( 'wp_ai_edit_enc_key' );
        if ( is_string( $stored ) && $stored !== '' ) {
            // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
            return base64_decode( $stored );
        }

        $key = sodium_crypto_secretbox_keygen();
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        update_option( 'wp_ai_edit_enc_key', base64_encode( $key ), false );
        return $key;
    }

    // ── SSE helpers ──

    public static function init_sse_headers(): void {
        // Prevent any output buffering.
        while ( ob_get_level() > 0 ) {
            ob_end_clean();
        }

        header( 'Content-Type: text/event-stream; charset=utf-8' );
        header( 'Cache-Control: no-cache' );
        header( 'Connection: keep-alive' );
        header( 'X-Accel-Buffering: no' );
    }

    public static function send_sse_error( string $message ): void {
        $data = wp_json_encode( [ 'error' => $message ] );
        echo "data: {$data}\n\n";
        self::flush_output();
        echo "data: [DONE]\n\n";
        self::flush_output();
    }

    private static function flush_output(): void {
        if ( ob_get_level() > 0 ) {
            ob_flush();
        }
        flush();
    }
}
