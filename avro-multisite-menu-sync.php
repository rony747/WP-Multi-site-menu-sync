<?php
/**
 * Plugin Name: Avro Multisite Menu Sync
 * Plugin URI: https://github.com/yourusername/avro-multisite-menu-sync
 * Description: Synchronize navigation menus across WordPress multisite network
 * Version: 1.0.0
 * Author: t.i.rony
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: avro-multisite-menu-sync
 * Domain Path: /languages
 * Network: true
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package Avro_Multisite_Menu_Sync
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants
define( 'AVRO_MENU_SYNC_VERSION', '1.0.0' );
define( 'AVRO_MENU_SYNC_PLUGIN_FILE', __FILE__ );
define( 'AVRO_MENU_SYNC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AVRO_MENU_SYNC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AVRO_MENU_SYNC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Require core class
require_once AVRO_MENU_SYNC_PLUGIN_DIR . 'includes/class-menu-sync-core.php';

/**
 * Initialize plugin
 *
 * @since 1.0.0
 */
function avro_menu_sync_init() {
	// Check if multisite
	if ( ! is_multisite() ) {
		add_action( 'admin_notices', 'avro_menu_sync_multisite_notice' );
		return;
	}

	// Initialize core
	Menu_Sync_Core::get_instance();
}
add_action( 'plugins_loaded', 'avro_menu_sync_init' );

/**
 * Display multisite requirement notice
 *
 * @since 1.0.0
 */
function avro_menu_sync_multisite_notice() {
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'Avro Multisite Menu Sync requires WordPress Multisite to be enabled.', 'avro-multisite-menu-sync' ); ?></p>
	</div>
	<?php
}

/**
 * Activation hook
 *
 * @since 1.0.0
 */
function avro_menu_sync_activate() {
	// Check multisite requirement
	if ( ! is_multisite() ) {
		wp_die(
			esc_html__( 'This plugin requires WordPress Multisite.', 'avro-multisite-menu-sync' ),
			esc_html__( 'Plugin Activation Error', 'avro-multisite-menu-sync' ),
			array( 'back_link' => true )
		);
	}

	// Check PHP version
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		wp_die(
			esc_html__( 'This plugin requires PHP 7.4 or higher.', 'avro-multisite-menu-sync' ),
			esc_html__( 'Plugin Activation Error', 'avro-multisite-menu-sync' ),
			array( 'back_link' => true )
		);
	}

	// Create database tables and set default options
	require_once AVRO_MENU_SYNC_PLUGIN_DIR . 'includes/class-menu-sync-core.php';
	Menu_Sync_Core::activate();
}
register_activation_hook( __FILE__, 'avro_menu_sync_activate' );

/**
 * Deactivation hook
 *
 * @since 1.0.0
 */
function avro_menu_sync_deactivate() {
	// Cleanup tasks
	require_once AVRO_MENU_SYNC_PLUGIN_DIR . 'includes/class-menu-sync-core.php';
	Menu_Sync_Core::deactivate();
}
register_deactivation_hook( __FILE__, 'avro_menu_sync_deactivate' );

/**
 * Load plugin text domain for translations
 *
 * @since 1.0.0
 */
function avro_menu_sync_load_textdomain() {
	load_plugin_textdomain(
		'avro-multisite-menu-sync',
		false,
		dirname( AVRO_MENU_SYNC_PLUGIN_BASENAME ) . '/languages'
	);
}
add_action( 'plugins_loaded', 'avro_menu_sync_load_textdomain' );
