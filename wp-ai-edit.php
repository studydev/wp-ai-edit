<?php
/**
 * Plugin Name: WP AI Edit
 * Plugin URI:  https://github.com/studydev/wp-ai-edit
 * Description: AI-powered content editing for the WordPress Gutenberg editor. Provides Write More, Improve, Summarize, Write Analogy, Fix Grammar, and custom command features via Azure OpenAI API.
 * Version:     1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.3
 * Author:      WP AI Edit
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-ai-edit
 * Domain Path: /languages
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WP_AI_EDIT_VERSION', '1.0.0' );
define( 'WP_AI_EDIT_FILE', __FILE__ );
define( 'WP_AI_EDIT_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_AI_EDIT_URL', plugin_dir_url( __FILE__ ) );

require_once WP_AI_EDIT_PATH . 'includes/class-prompt-manager.php';
require_once WP_AI_EDIT_PATH . 'includes/class-openai-client.php';
require_once WP_AI_EDIT_PATH . 'includes/class-rest-api.php';
require_once WP_AI_EDIT_PATH . 'includes/class-admin-settings.php';
require_once WP_AI_EDIT_PATH . 'includes/class-plugin.php';

add_action( 'plugins_loaded', static function (): void {
    load_plugin_textdomain( 'wp-ai-edit', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    WpAiEdit\Plugin::get_instance();
} );
