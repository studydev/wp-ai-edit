<?php

declare(strict_types=1);

namespace WpAiEdit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class AdminSettings {

    private static ?self $instance = null;

    private const OPTION_GROUP = 'wp_ai_edit_settings_group';
    private const OPTION_NAME  = 'wp_ai_edit_settings';
    private const MENU_SLUG    = 'wp-ai-edit';

    public static function get_instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    public function add_menu_page(): void {
        add_menu_page(
            __( 'WP AI Edit', 'wp-ai-edit' ),
            __( 'WP AI Edit', 'wp-ai-edit' ),
            'manage_options',
            self::MENU_SLUG,
            [ $this, 'render_page' ],
            'dashicons-edit-large',
            85
        );
    }

    public function register_settings(): void {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            [
                'type'              => 'array',
                'sanitize_callback' => [ $this, 'sanitize_settings' ],
            ]
        );

        // ── API Settings Section ──
        add_settings_section(
            'wp_ai_edit_api_section',
            __( 'Azure OpenAI API Settings', 'wp-ai-edit' ),
            [ $this, 'render_api_section' ],
            self::MENU_SLUG
        );

        add_settings_field(
            'endpoint',
            __( 'API Endpoint URL', 'wp-ai-edit' ),
            [ $this, 'render_endpoint_field' ],
            self::MENU_SLUG,
            'wp_ai_edit_api_section'
        );

        add_settings_field(
            'api_key',
            __( 'API Key', 'wp-ai-edit' ),
            [ $this, 'render_api_key_field' ],
            self::MENU_SLUG,
            'wp_ai_edit_api_section'
        );

        add_settings_field(
            'model',
            __( 'Model', 'wp-ai-edit' ),
            [ $this, 'render_model_field' ],
            self::MENU_SLUG,
            'wp_ai_edit_api_section'
        );

        // ── Prompt Settings Section ──
        add_settings_section(
            'wp_ai_edit_prompt_section',
            __( 'System Prompt Settings', 'wp-ai-edit' ),
            [ $this, 'render_prompt_section' ],
            self::MENU_SLUG
        );

        $labels = PromptManager::get_action_labels();
        foreach ( $labels as $key => $label ) {
            add_settings_field(
                'prompt_' . $key,
                $label,
                [ $this, 'render_prompt_field' ],
                self::MENU_SLUG,
                'wp_ai_edit_prompt_section',
                [ 'action_key' => $key ]
            );
        }

        // ── Tail Prompt Section (bottom) ──
        add_settings_section(
            'wp_ai_edit_tail_section',
            __( 'Tail Prompt (Response Format)', 'wp-ai-edit' ),
            [ $this, 'render_tail_section' ],
            self::MENU_SLUG
        );

        add_settings_field(
            'tail_prompt',
            __( 'Tail Prompt', 'wp-ai-edit' ),
            [ $this, 'render_tail_prompt_field' ],
            self::MENU_SLUG,
            'wp_ai_edit_tail_section'
        );
    }

    /**
     * @param mixed $input
     * @return array<string, string>
     */
    public function sanitize_settings( mixed $input ): array {
        if ( ! is_array( $input ) ) {
            return [];
        }

        $current  = get_option( self::OPTION_NAME, [] );
        if ( ! is_array( $current ) ) {
            $current = [];
        }

        $sanitized = [];

        // Endpoint
        $endpoint = sanitize_url( $input['endpoint'] ?? '' );
        if ( $endpoint !== '' && ! OpenAIClient::validate_endpoint( $endpoint ) ) {
            add_settings_error(
                self::OPTION_NAME,
                'invalid_endpoint',
                __( 'Invalid API endpoint. Only HTTPS URLs pointing to public hosts are allowed.', 'wp-ai-edit' ),
                'error'
            );
            $endpoint = $current['endpoint'] ?? '';
        }
        $sanitized['endpoint'] = $endpoint;

        // API Key — only update if a new one is provided
        $raw_key = $input['api_key'] ?? '';
        if ( $raw_key !== '' && $raw_key !== '••••••••' ) {
            $encrypted = OpenAIClient::encrypt_api_key( sanitize_text_field( $raw_key ) );
            $sanitized['api_key_encrypted'] = $encrypted;
        } else {
            $sanitized['api_key_encrypted'] = $current['api_key_encrypted'] ?? '';
        }

        // Model
        $allowed_models = [ 'gpt-5.4-pro', 'gpt-5.4-mini', 'gpt-5.4-nano', 'gpt-5.4' ];
        $model = sanitize_text_field( $input['model'] ?? 'gpt-5.4' );
        $sanitized['model'] = in_array( $model, $allowed_models, true ) ? $model : 'gpt-5.4';

        // Prompts
        $prompts = [];
        $defaults = PromptManager::get_defaults();
        foreach ( array_keys( $defaults ) as $key ) {
            $field_key = 'prompt_' . $key;
            if ( isset( $input[ $field_key ] ) && $input[ $field_key ] !== '' ) {
                $prompts[ $key ] = sanitize_textarea_field( $input[ $field_key ] );
            }
        }
        if ( ! empty( $prompts ) ) {
            PromptManager::save( $prompts );
        }

        // Tail Prompt
        if ( isset( $input['tail_prompt'] ) ) {
            PromptManager::save_tail_prompt( $input['tail_prompt'] );
        }

        return $sanitized;
    }

    public function enqueue_admin_assets( string $hook ): void {
        if ( $hook !== 'toplevel_page_' . self::MENU_SLUG ) {
            return;
        }

        wp_enqueue_style(
            'wp-ai-edit-admin',
            WP_AI_EDIT_URL . 'assets/admin.css',
            [],
            WP_AI_EDIT_VERSION
        );

        wp_enqueue_script(
            'wp-ai-edit-admin',
            WP_AI_EDIT_URL . 'assets/admin.js',
            [],
            WP_AI_EDIT_VERSION,
            true
        );

        wp_localize_script(
            'wp-ai-edit-admin',
            'wpAiEditAdmin',
            [
                'restUrl'        => esc_url_raw( rest_url( 'wp-ai-edit/v1/' ) ),
                'nonce'          => wp_create_nonce( 'wp_rest' ),
                'noTailActions'  => PromptManager::get_no_tail_actions(),
                'i18n'           => [
                    'testing'      => __( 'Testing connection...', 'wp-ai-edit' ),
                    'success'      => __( 'Connection successful!', 'wp-ai-edit' ),
                    'failed'       => __( 'Connection failed:', 'wp-ai-edit' ),
                    'resetConfirm' => __( 'Reset this prompt to default?', 'wp-ai-edit' ),
                ],
            ]
        );
    }

    // ── Render functions ──

    public function render_page(): void {
        ?>
        <div class="wrap wp-ai-edit-settings">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( self::OPTION_GROUP );
                do_settings_sections( self::MENU_SLUG );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_api_section(): void {
        echo '<p>' . esc_html__( 'Configure your Azure OpenAI API connection settings.', 'wp-ai-edit' ) . '</p>';
    }

    public function render_prompt_section(): void {
        echo '<p>' . esc_html__(
            'Customize the system prompts for each AI action. Leave empty to use the default prompt.',
            'wp-ai-edit'
        ) . '</p>';
    }

    public function render_tail_section(): void {
        echo '<p>' . esc_html__(
            'The tail prompt is automatically appended to every system prompt (except SEO Helper) to ensure the AI response is formatted in Gutenberg-compatible HTML.',
            'wp-ai-edit'
        ) . '</p>';
    }

    public function render_endpoint_field(): void {
        $settings = get_option( self::OPTION_NAME, [] );
        $value    = $settings['endpoint'] ?? '';
        ?>
        <input
            type="url"
            id="wp-ai-edit-endpoint"
            name="<?php echo esc_attr( self::OPTION_NAME ); ?>[endpoint]"
            value="<?php echo esc_attr( $value ); ?>"
            class="regular-text"
            placeholder="https://your-resource.openai.azure.com/openai/v1"
        />
        <p class="description">
            <?php esc_html_e( 'Enter your Azure OpenAI API endpoint URL.', 'wp-ai-edit' ); ?>
        </p>
        <?php
    }

    public function render_api_key_field(): void {
        $settings   = get_option( self::OPTION_NAME, [] );
        $has_key    = ! empty( $settings['api_key_encrypted'] ?? '' );
        $display    = $has_key ? '••••••••' : '';
        ?>
        <input
            type="password"
            id="wp-ai-edit-api-key"
            name="<?php echo esc_attr( self::OPTION_NAME ); ?>[api_key]"
            value="<?php echo esc_attr( $display ); ?>"
            class="regular-text"
            autocomplete="new-password"
        />
        <button
            type="button"
            id="wp-ai-edit-test-connection"
            class="button button-secondary"
        >
            <?php esc_html_e( 'Test Connection', 'wp-ai-edit' ); ?>
        </button>
        <span id="wp-ai-edit-connection-status"></span>
        <?php if ( $has_key ) : ?>
            <p class="description">
                <?php esc_html_e( 'API key is saved (encrypted). Enter a new key to replace it.', 'wp-ai-edit' ); ?>
            </p>
        <?php endif; ?>
        <?php
    }

    public function render_model_field(): void {
        $settings = get_option( self::OPTION_NAME, [] );
        $value    = $settings['model'] ?? 'gpt-5.4';
        $models   = [
            'gpt-5.4-pro'  => 'GPT-5.4 Pro',
            'gpt-5.4-mini' => 'GPT-5.4 Mini',
            'gpt-5.4-nano' => 'GPT-5.4 Nano',
            'gpt-5.4'      => 'GPT-5.4',
        ];
        ?>
        <select id="wp-ai-edit-model" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[model]">
            <?php foreach ( $models as $model_value => $model_label ) : ?>
                <option
                    value="<?php echo esc_attr( $model_value ); ?>"
                    <?php selected( $value, $model_value ); ?>
                >
                    <?php echo esc_html( $model_label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * @param array{action_key: string} $args
     */
    public function render_prompt_field( array $args ): void {
        $key         = $args['action_key'];
        $prompts     = PromptManager::get_all();
        $defaults    = PromptManager::get_defaults();
        $value       = $prompts[ $key ] ?? '';
        $default     = $defaults[ $key ] ?? '';
        $no_tail     = in_array( $key, PromptManager::get_no_tail_actions(), true );
        $tail_prompt = PromptManager::get_tail_prompt();
        ?>
        <textarea
            name="<?php echo esc_attr( self::OPTION_NAME ); ?>[prompt_<?php echo esc_attr( $key ); ?>]"
            rows="4"
            class="large-text wp-ai-edit-prompt-textarea"
            data-action="<?php echo esc_attr( $key ); ?>"
            data-default="<?php echo esc_attr( $default ); ?>"
            placeholder="<?php echo esc_attr( $default ); ?>"
        ><?php echo esc_textarea( $value ); ?></textarea>
        <button
            type="button"
            class="button button-link wp-ai-edit-reset-prompt"
            data-target="prompt_<?php echo esc_attr( $key ); ?>"
        >
            <?php esc_html_e( 'Reset to default', 'wp-ai-edit' ); ?>
        </button>

        <details class="wp-ai-edit-prompt-preview" data-action="<?php echo esc_attr( $key ); ?>">
            <summary><?php esc_html_e( 'View Full Prompt', 'wp-ai-edit' ); ?></summary>
            <pre class="wp-ai-edit-preview-content"><?php
                echo esc_html( $value !== '' ? $value : $default );
                if ( ! $no_tail && $tail_prompt !== '' ) {
                    echo "\n\n" . esc_html( str_repeat( '─', 40 ) );
                    echo "\n" . esc_html__( '[Tail Prompt]', 'wp-ai-edit' );
                    echo "\n" . esc_html( $tail_prompt );
                }
            ?></pre>
        </details>
        <?php
    }

    public function render_tail_prompt_field(): void {
        $value   = PromptManager::get_tail_prompt();
        $default = PromptManager::get_default_tail_prompt();
        ?>
        <textarea
            id="wp-ai-edit-tail-prompt"
            name="<?php echo esc_attr( self::OPTION_NAME ); ?>[tail_prompt]"
            rows="6"
            class="large-text wp-ai-edit-prompt-textarea"
            data-default="<?php echo esc_attr( $default ); ?>"
            placeholder="<?php echo esc_attr( $default ); ?>"
        ><?php echo esc_textarea( $value ); ?></textarea>
        <button
            type="button"
            class="button button-link wp-ai-edit-reset-prompt"
            data-target="tail_prompt"
        >
            <?php esc_html_e( 'Reset to default', 'wp-ai-edit' ); ?>
        </button>
        <p class="description">
            <?php esc_html_e( 'This prompt is appended to all system prompts except SEO Helper, instructing the AI to respond in Gutenberg-compatible HTML.', 'wp-ai-edit' ); ?>
        </p>
        <?php
    }
}
