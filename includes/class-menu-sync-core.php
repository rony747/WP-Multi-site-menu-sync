<?php
/**
 * Menu Sync Core Class
 *
 * Handles core plugin functionality and initialization.
 *
 * @package Avro_Multisite_Menu_Sync
 * @subpackage Core
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core plugin class
 *
 * @since 1.0.0
 */
class Menu_Sync_Core {

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version = '1.0.0';

	/**
	 * Singleton instance
	 *
	 * @var Menu_Sync_Core
	 */
	private static $instance = null;

	/**
	 * Settings instance
	 *
	 * @var Menu_Sync_Settings
	 */
	private $settings;

	/**
	 * Engine instance
	 *
	 * @var Menu_Sync_Engine
	 */
	private $engine;

	/**
	 * Logger instance
	 *
	 * @var Menu_Sync_Logger
	 */
	private $logger;

	/**
	 * Admin instance
	 *
	 * @var Menu_Sync_Admin
	 */
	private $admin;

	/**
	 * Get singleton instance
	 *
	 * @since 1.0.0
	 * @return Menu_Sync_Core
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init();
	}

	/**
	 * Load required dependencies
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {
		require_once AVRO_MENU_SYNC_PLUGIN_DIR . 'includes/class-menu-sync-settings.php';
		require_once AVRO_MENU_SYNC_PLUGIN_DIR . 'includes/class-menu-sync-logger.php';
		require_once AVRO_MENU_SYNC_PLUGIN_DIR . 'includes/class-menu-sync-engine.php';
		require_once AVRO_MENU_SYNC_PLUGIN_DIR . 'includes/class-menu-sync-admin.php';
		require_once AVRO_MENU_SYNC_PLUGIN_DIR . 'includes/class-menu-sync-ajax.php';
	}

	/**
	 * Initialize plugin
	 *
	 * @since 1.0.0
	 */
	private function init() {
		// Initialize components
		$this->settings = new Menu_Sync_Settings();
		$this->logger   = new Menu_Sync_Logger();
		$this->engine   = new Menu_Sync_Engine( $this->settings, $this->logger );
		$this->admin    = new Menu_Sync_Admin( $this->settings, $this->engine, $this->logger );

		// Register hooks
		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks
	 *
	 * @since 1.0.0
	 */
	private function register_hooks() {
		// Auto sync on menu update
		add_action( 'wp_update_nav_menu', array( $this, 'maybe_auto_sync' ), 10, 2 );

		// Admin hooks
		if ( is_admin() ) {
			add_action( 'network_admin_menu', array( $this->admin, 'register_pages' ) );
			add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_assets' ) );
		}

		// AJAX hooks
		add_action( 'wp_ajax_avro_menu_sync_manual_sync', array( 'Menu_Sync_Ajax', 'handle_manual_sync' ) );
		add_action( 'wp_ajax_avro_menu_sync_get_logs', array( 'Menu_Sync_Ajax', 'handle_get_logs' ) );
	}

	/**
	 * Maybe trigger auto sync on menu update
	 *
	 * @since 1.0.0
	 * @param int   $menu_id   Menu ID.
	 * @param array $menu_data Menu data.
	 */
	public function maybe_auto_sync( $menu_id, $menu_data = array() ) {
		// Check if auto sync is enabled
		if ( 'auto' !== $this->settings->get( 'sync_mode', 'manual' ) ) {
			return;
		}

		// Check if we're on the source site
		$source_site_id = $this->settings->get( 'source_site_id' );
		if ( empty( $source_site_id ) || absint( $source_site_id ) !== get_current_blog_id() ) {
			return;
		}

		// Get target sites
		$target_sites = $this->settings->get( 'target_site_ids', array() );
		if ( empty( $target_sites ) || ! is_array( $target_sites ) ) {
			return;
		}

		// Trigger sync
		$result = $this->engine->sync_menu( $menu_id, $target_sites );

		// Show admin notice
		if ( is_wp_error( $result ) ) {
			add_action( 'admin_notices', function() use ( $result ) {
				printf(
					'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
					esc_html( $result->get_error_message() )
				);
			} );
		} else {
			add_action( 'admin_notices', function() use ( $result ) {
				$success_count = isset( $result['success'] ) ? count( $result['success'] ) : 0;
				printf(
					'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
					esc_html(
						sprintf(
							/* translators: %d: number of sites */
							_n(
								'Menu synced to %d site successfully.',
								'Menu synced to %d sites successfully.',
								$success_count,
								'avro-multisite-menu-sync'
							),
							$success_count
						)
					)
				);
			} );
		}
	}

	/**
	 * Get plugin version
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get settings instance
	 *
	 * @since 1.0.0
	 * @return Menu_Sync_Settings
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Get engine instance
	 *
	 * @since 1.0.0
	 * @return Menu_Sync_Engine
	 */
	public function get_engine() {
		return $this->engine;
	}

	/**
	 * Get logger instance
	 *
	 * @since 1.0.0
	 * @return Menu_Sync_Logger
	 */
	public function get_logger() {
		return $this->logger;
	}

	/**
	 * Plugin activation
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		global $wpdb;

		// Create logs table
		$table_name      = $wpdb->base_prefix . 'menu_sync_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			timestamp datetime NOT NULL,
			source_site_id bigint(20) NOT NULL,
			target_site_id bigint(20) NOT NULL,
			menu_id bigint(20) NOT NULL,
			menu_name varchar(255) NOT NULL,
			operation varchar(50) NOT NULL,
			status varchar(50) NOT NULL,
			message text,
			items_synced int,
			conflicts text,
			user_id bigint(20),
			PRIMARY KEY  (id),
			KEY idx_timestamp (timestamp),
			KEY idx_source_site (source_site_id),
			KEY idx_target_site (target_site_id),
			KEY idx_status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Set default options
		$default_settings = array(
			'source_site_id'        => 1,
			'target_site_ids'       => array(),
			'sync_mode'             => 'manual',
			'conflict_resolution'   => 'override',
			'sync_menu_locations'   => true,
			'preserve_custom_fields' => true,
			'enabled'               => true,
		);

		if ( ! get_site_option( 'avro_menu_sync_settings' ) ) {
			add_site_option( 'avro_menu_sync_settings', $default_settings );
		}

		// Store version
		add_site_option( 'avro_menu_sync_version', AVRO_MENU_SYNC_VERSION );

		// Set activation flag
		add_site_option( 'avro_menu_sync_activated', time() );
	}

	/**
	 * Plugin deactivation
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		// Clear transients
		delete_site_transient( 'avro_menu_sync_source_menus' );
		delete_site_transient( 'avro_menu_sync_target_sites' );

		// Set deactivation flag
		add_site_option( 'avro_menu_sync_deactivated', time() );
	}
}
