<?php

declare(strict_types=1);

namespace WpAiEdit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Plugin {

    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        AdminSettings::get_instance();
        RestApi::get_instance();

        add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
    }

    public function enqueue_editor_assets(): void {
        $asset_file = WP_AI_EDIT_PATH . 'build/index.asset.php';

        if ( ! file_exists( $asset_file ) ) {
            return;
        }

        $asset = require $asset_file;

        wp_enqueue_script(
            'wp-ai-edit-editor',
            WP_AI_EDIT_URL . 'build/index.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );

        wp_enqueue_style(
            'wp-ai-edit-editor',
            WP_AI_EDIT_URL . 'build/index.css',
            [],
            $asset['version']
        );

        $settings = get_option( 'wp_ai_edit_settings', [] );
        if ( ! is_array( $settings ) ) {
            $settings = [];
        }

        $active_provider   = OpenAIClient::get_active_provider_from_settings( $settings );
        $provider_settings = OpenAIClient::get_provider_settings_from_settings( $settings, $active_provider );
        $model             = OpenAIClient::normalize_model( $provider_settings['model'] );
        $has_api_key       = $provider_settings['api_key_encrypted'] !== '';
        $provider_summary  = sprintf(
            '%1$s - %2$s',
            OpenAIClient::get_provider_short_label( $active_provider ),
            OpenAIClient::get_model_label( $model )
        );

        wp_localize_script(
            'wp-ai-edit-editor',
            'wpAiEdit',
            [
                'restUrl'  => esc_url_raw( rest_url( 'wp-ai-edit/v1/' ) ),
                'nonce'    => wp_create_nonce( 'wp_rest' ),
                'hasApiKey' => $has_api_key,
                'activeProvider' => $active_provider,
                'activeProviderLabel' => OpenAIClient::get_provider_short_label( $active_provider ),
                'model'    => $model,
                'modelLabel' => OpenAIClient::get_model_label( $model ),
                'activeProviderSummary' => $provider_summary,
            ]
        );

        wp_set_script_translations(
            'wp-ai-edit-editor',
            'wp-ai-edit',
            WP_AI_EDIT_PATH . 'languages'
        );
    }
}
