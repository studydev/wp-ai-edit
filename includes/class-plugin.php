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
        $has_api_key = ! empty( $settings['api_key_encrypted'] ?? '' );

        wp_localize_script(
            'wp-ai-edit-editor',
            'wpAiEdit',
            [
                'restUrl'  => esc_url_raw( rest_url( 'wp-ai-edit/v1/' ) ),
                'nonce'    => wp_create_nonce( 'wp_rest' ),
                'hasApiKey' => $has_api_key,
                'model'    => $settings['model'] ?? 'gpt-5.4',
            ]
        );

        wp_set_script_translations(
            'wp-ai-edit-editor',
            'wp-ai-edit',
            WP_AI_EDIT_PATH . 'languages'
        );
    }
}
