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
    public const MASKED_API_KEY = '••••••••';

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
            __( 'Hemtory AI Editor', 'wp-ai-edit' ),
            __( 'Hemtory AI Editor', 'wp-ai-edit' ),
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
            __( 'AI API Settings', 'wp-ai-edit' ),
            [ $this, 'render_api_section' ],
            self::MENU_SLUG
        );

        add_settings_field(
            'provider',
            __( 'API Provider', 'wp-ai-edit' ),
            [ $this, 'render_provider_field' ],
            self::MENU_SLUG,
            'wp_ai_edit_api_section'
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
    * @return array<string, mixed>
     */
    public function sanitize_settings( mixed $input ): array {
        if ( ! is_array( $input ) ) {
            return [];
        }

        $current  = get_option( self::OPTION_NAME, [] );
        if ( ! is_array( $current ) ) {
            $current = [];
        }

        $sanitized       = [];
        $active_provider = OpenAIClient::normalize_provider(
            sanitize_text_field( (string) ( $input['active_provider'] ?? ( $current['active_provider'] ?? $current['provider'] ?? OpenAIClient::get_default_provider() ) ) )
        );

        $sanitized['active_provider'] = $active_provider;
        $sanitized['provider']        = $active_provider;
        $sanitized['providers']       = [];

        foreach ( OpenAIClient::get_supported_providers() as $provider ) {
            $current_provider_settings = OpenAIClient::get_provider_settings_from_settings( $current, $provider );
            $provider_input            = $input['providers'][ $provider ] ?? [];

            if ( ! is_array( $provider_input ) ) {
                $provider_input = [];
            }

            // If this provider's panel was hidden (not the active tab),
            // the browser submits empty values — keep the previously saved data.
            $is_active_input = ( $provider === $active_provider );

            $endpoint_input = sanitize_url( (string) ( $provider_input['endpoint'] ?? '' ) );
            if ( ! $is_active_input && $endpoint_input === '' ) {
                $endpoint = $current_provider_settings['endpoint'];
            } else {
                $endpoint = $endpoint_input;
            }
            if ( $endpoint !== '' && ! OpenAIClient::validate_endpoint( $endpoint ) ) {
                add_settings_error(
                    self::OPTION_NAME,
                    'invalid_endpoint_' . $provider,
                    sprintf(
                        /* translators: %s: provider label */
                        __( 'Invalid API endpoint for %s. Only HTTPS URLs pointing to public hosts are allowed.', 'wp-ai-edit' ),
                        OpenAIClient::get_provider_label( $provider )
                    ),
                    'error'
                );
                $endpoint = $current_provider_settings['endpoint'];
            }

            $raw_key = (string) ( $provider_input['api_key'] ?? '' );
            if ( $raw_key !== '' && $raw_key !== self::MASKED_API_KEY ) {
                $encrypted = OpenAIClient::encrypt_api_key( sanitize_text_field( $raw_key ) );
            } else {
                $encrypted = $current_provider_settings['api_key_encrypted'];
            }

            $model_input = (string) ( $provider_input['model'] ?? '' );
            if ( ! $is_active_input && $model_input === '' ) {
                $model = $current_provider_settings['model'];
            } else {
                $model = OpenAIClient::normalize_model(
                    $model_input !== '' ? $model_input : $current_provider_settings['model']
                );
            }

            $sanitized['providers'][ $provider ] = [
                'endpoint'          => $endpoint,
                'api_key_encrypted' => is_string( $encrypted ) ? $encrypted : '',
                'model'             => $model,
            ];
        }

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
                    'providerSwitchFailed' => __( 'Failed to activate the selected LLM. Save settings to apply it manually.', 'wp-ai-edit' ),
                    'proModelWarning' => __( 'GPT-5.4 Pro costs $30/MTok input and $180/MTok output — significantly more expensive than other models. Continue?', 'wp-ai-edit' ),
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
        echo '<p>' . esc_html__( 'Choose an LLM tab, save each provider with its own endpoint, API key, and model, and the last active tab will be used for editor requests.', 'wp-ai-edit' ) . '</p>';
    }

    public function render_provider_field(): void {
        $settings        = get_option( self::OPTION_NAME, [] );
        $active_provider = is_array( $settings )
            ? OpenAIClient::get_active_provider_from_settings( $settings )
            : OpenAIClient::get_default_provider();
        ?>
        <input
            type="hidden"
            id="wp-ai-edit-active-provider"
            name="<?php echo esc_attr( self::OPTION_NAME ); ?>[active_provider]"
            value="<?php echo esc_attr( $active_provider ); ?>"
        />
        <div class="wp-ai-edit-provider-tabs" role="tablist" aria-label="<?php esc_attr_e( 'LLM Providers', 'wp-ai-edit' ); ?>">
            <?php foreach ( OpenAIClient::get_supported_providers() as $provider ) : ?>
                <button
                    type="button"
                    class="button wp-ai-edit-provider-tab<?php echo $provider === $active_provider ? ' is-active' : ''; ?>"
                    data-provider="<?php echo esc_attr( $provider ); ?>"
                    role="tab"
                    aria-selected="<?php echo $provider === $active_provider ? 'true' : 'false'; ?>"
                >
                    <img
                        src="<?php echo esc_url( OpenAIClient::get_provider_icon_url( $provider ) ); ?>"
                        alt=""
                        class="wp-ai-edit-provider-icon"
                    /><?php echo esc_html( OpenAIClient::get_provider_label( $provider ) ); ?>
                </button>
            <?php endforeach; ?>
        </div>
        <p class="description">
            <?php esc_html_e( 'Each provider keeps its own endpoint, API key, and model. Changing the active tab also changes which LLM the editor will call.', 'wp-ai-edit' ); ?>
        </p>
        <p class="description wp-ai-edit-provider-switch-status" id="wp-ai-edit-provider-switch-status" aria-live="polite"></p>
        <?php
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
        $settings        = get_option( self::OPTION_NAME, [] );
        $settings        = is_array( $settings ) ? $settings : [];
        $active_provider = OpenAIClient::get_active_provider_from_settings( $settings );
        ?>
        <?php foreach ( OpenAIClient::get_supported_providers() as $provider ) : ?>
            <?php
            $provider_settings = OpenAIClient::get_provider_settings_from_settings( $settings, $provider );
            $is_active         = $provider === $active_provider;
            $needs_endpoint    = OpenAIClient::provider_needs_endpoint( $provider );
            ?>
            <div class="wp-ai-edit-provider-panel<?php echo $is_active ? ' is-active' : ''; ?>" data-provider="<?php echo esc_attr( $provider ); ?>"<?php echo $is_active ? '' : ' hidden'; ?>>
                <?php if ( $needs_endpoint ) : ?>
                    <input
                        type="url"
                        id="wp-ai-edit-endpoint-<?php echo esc_attr( $provider ); ?>"
                        name="<?php echo esc_attr( self::OPTION_NAME ); ?>[providers][<?php echo esc_attr( $provider ); ?>][endpoint]"
                        value="<?php echo esc_attr( $provider_settings['endpoint'] ); ?>"
                        class="regular-text"
                        placeholder="<?php echo esc_attr( OpenAIClient::get_provider_endpoint_placeholder( $provider ) ); ?>"
                    />
                    <p class="description">
                        <?php echo esc_html( OpenAIClient::get_provider_endpoint_help( $provider ) ); ?>
                    </p>
                <?php else : ?>
                    <p class="description">
                        <?php
                        printf(
                            /* translators: %s: provider name */
                            esc_html__( '%s uses a fixed API endpoint. No configuration needed.', 'wp-ai-edit' ),
                            esc_html( OpenAIClient::get_provider_label( $provider ) )
                        );
                        ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php
    }

    public function render_api_key_field(): void {
        $settings        = get_option( self::OPTION_NAME, [] );
        $settings        = is_array( $settings ) ? $settings : [];
        $active_provider = OpenAIClient::get_active_provider_from_settings( $settings );
        ?>
        <?php foreach ( OpenAIClient::get_supported_providers() as $provider ) : ?>
            <?php
            $provider_settings = OpenAIClient::get_provider_settings_from_settings( $settings, $provider );
            $has_key           = $provider_settings['api_key_encrypted'] !== '';
            $display           = $has_key ? self::MASKED_API_KEY : '';
            $is_active         = $provider === $active_provider;
            ?>
            <div class="wp-ai-edit-provider-panel<?php echo $is_active ? ' is-active' : ''; ?>" data-provider="<?php echo esc_attr( $provider ); ?>"<?php echo $is_active ? '' : ' hidden'; ?>>
                <input
                    type="password"
                    id="wp-ai-edit-api-key-<?php echo esc_attr( $provider ); ?>"
                    name="<?php echo esc_attr( self::OPTION_NAME ); ?>[providers][<?php echo esc_attr( $provider ); ?>][api_key]"
                    value="<?php echo esc_attr( $display ); ?>"
                    class="regular-text"
                    autocomplete="new-password"
                />
                <button
                    type="button"
                    class="button button-secondary wp-ai-edit-test-connection"
                    data-provider="<?php echo esc_attr( $provider ); ?>"
                >
                    <?php esc_html_e( 'Test Connection', 'wp-ai-edit' ); ?>
                </button>
                <span class="wp-ai-edit-connection-status" id="wp-ai-edit-connection-status-<?php echo esc_attr( $provider ); ?>"></span>
                <?php if ( $has_key ) : ?>
                    <p class="description">
                        <?php
                        printf(
                            /* translators: %s: provider label */
                            esc_html__( 'API key for %s is saved (encrypted). Enter a new key to replace it.', 'wp-ai-edit' ),
                            esc_html( OpenAIClient::get_provider_label( $provider ) )
                        );
                        ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php
    }

    public function render_model_field(): void {
        $settings        = get_option( self::OPTION_NAME, [] );
        $settings        = is_array( $settings ) ? $settings : [];
        $active_provider = OpenAIClient::get_active_provider_from_settings( $settings );
        ?>
        <?php foreach ( OpenAIClient::get_supported_providers() as $provider ) : ?>
            <?php
            $provider_settings = OpenAIClient::get_provider_settings_from_settings( $settings, $provider );
            $models            = OpenAIClient::get_models_for_provider( $provider );
            $current_model     = OpenAIClient::normalize_model_for_provider( $provider_settings['model'], $provider );
            $is_active         = $provider === $active_provider;
            ?>
            <div class="wp-ai-edit-provider-panel<?php echo $is_active ? ' is-active' : ''; ?>" data-provider="<?php echo esc_attr( $provider ); ?>"<?php echo $is_active ? '' : ' hidden'; ?>>
                <input
                    type="hidden"
                    id="wp-ai-edit-model-<?php echo esc_attr( $provider ); ?>"
                    name="<?php echo esc_attr( self::OPTION_NAME ); ?>[providers][<?php echo esc_attr( $provider ); ?>][model]"
                    value="<?php echo esc_attr( $current_model ); ?>"
                />
                <div class="wp-ai-edit-model-tabs" data-provider="<?php echo esc_attr( $provider ); ?>">
                    <?php foreach ( $models as $model_value => $model_meta ) : ?>
                        <button
                            type="button"
                            class="button wp-ai-edit-model-tab<?php echo $current_model === $model_value ? ' is-active' : ''; ?>"
                            data-model="<?php echo esc_attr( $model_value ); ?>"
                        >
                            <span class="wp-ai-edit-model-name"><?php echo esc_html( $model_meta['label'] ); ?></span>
                            <span class="wp-ai-edit-model-price"><?php echo esc_html( $model_meta['price'] ); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <p class="description">
                    <?php esc_html_e( 'Price: input / output per MTok', 'wp-ai-edit' ); ?>
                </p>
            </div>
        <?php endforeach; ?>
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
