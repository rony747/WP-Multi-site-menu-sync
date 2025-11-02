<?php
/**
 * Menu Sync Admin Class
 *
 * Handles admin interface and pages.
 *
 * @package Avro_Multisite_Menu_Sync
 * @subpackage Admin
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin interface class
 *
 * @since 1.0.0
 */
class Menu_Sync_Admin {

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
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Menu_Sync_Settings $settings Settings instance.
	 * @param Menu_Sync_Engine   $engine   Engine instance.
	 * @param Menu_Sync_Logger   $logger   Logger instance.
	 */
	public function __construct( $settings, $engine, $logger ) {
		$this->settings = $settings;
		$this->engine   = $engine;
		$this->logger   = $logger;
	}

	/**
	 * Register admin pages
	 *
	 * @since 1.0.0
	 */
	public function register_pages() {
		// Main menu page
		add_menu_page(
			__( 'Menu Sync', 'avro-multisite-menu-sync' ),
			__( 'Menu Sync', 'avro-multisite-menu-sync' ),
			'manage_network_options',
			'menu-sync',
			array( $this, 'render_dashboard' ),
			'dashicons-update',
			30
		);

		// Dashboard submenu
		add_submenu_page(
			'menu-sync',
			__( 'Dashboard', 'avro-multisite-menu-sync' ),
			__( 'Dashboard', 'avro-multisite-menu-sync' ),
			'manage_network_options',
			'menu-sync',
			array( $this, 'render_dashboard' )
		);

		// Settings submenu
		add_submenu_page(
			'menu-sync',
			__( 'Settings', 'avro-multisite-menu-sync' ),
			__( 'Settings', 'avro-multisite-menu-sync' ),
			'manage_network_options',
			'menu-sync-settings',
			array( $this, 'render_settings' )
		);

		// Logs submenu
		add_submenu_page(
			'menu-sync',
			__( 'Logs', 'avro-multisite-menu-sync' ),
			__( 'Logs', 'avro-multisite-menu-sync' ),
			'manage_network_options',
			'menu-sync-logs',
			array( $this, 'render_logs' )
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		// Only load on our plugin pages
		if ( false === strpos( $hook, 'menu-sync' ) ) {
			return;
		}

		// Enqueue CSS
		wp_enqueue_style(
			'avro-menu-sync-admin',
			AVRO_MENU_SYNC_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			AVRO_MENU_SYNC_VERSION
		);

		// Enqueue JS
		wp_enqueue_script(
			'avro-menu-sync-admin',
			AVRO_MENU_SYNC_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			AVRO_MENU_SYNC_VERSION,
			true
		);

		// Get target sites from settings for dashboard
		$target_site_ids = $this->settings->get( 'target_site_ids', array() );
		
		// Localize script
		wp_localize_script(
			'avro-menu-sync-admin',
			'avroMenuSync',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'avro_menu_sync_ajax' ),
				'targetSites'   => $target_site_ids,
				'strings'       => array(
					'confirm'       => __( 'Are you sure you want to sync this menu?', 'avro-multisite-menu-sync' ),
					'syncing'       => __( 'Syncing...', 'avro-multisite-menu-sync' ),
					'success'       => __( 'Sync completed successfully.', 'avro-multisite-menu-sync' ),
					'error'         => __( 'Sync failed. Please check the logs.', 'avro-multisite-menu-sync' ),
					'selectSites'   => __( 'Please select at least one target site.', 'avro-multisite-menu-sync' ),
				),
			)
		);
	}

	/**
	 * Render dashboard page
	 *
	 * @since 1.0.0
	 */
	public function render_dashboard() {
		// Check permissions
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'avro-multisite-menu-sync' ) );
		}

		// Get source site
		$source_site_id = $this->settings->get( 'source_site_id' );
		$source_site    = get_blog_details( $source_site_id );

		// Get available menus from source site
		$menus = array();
		if ( $source_site ) {
			switch_to_blog( $source_site_id );
			$menus = wp_get_nav_menus();
			restore_current_blog();
		}

		// Get target sites
		$target_sites = $this->settings->get_target_sites();

		// Get statistics
		$stats = $this->logger->get_statistics();

		// Include template
		include AVRO_MENU_SYNC_PLUGIN_DIR . 'templates/admin-dashboard.php';
	}

	/**
	 * Render settings page
	 *
	 * @since 1.0.0
	 */
	public function render_settings() {
		// Check permissions
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'avro-multisite-menu-sync' ) );
		}

		// Handle form submission
		if ( isset( $_POST['avro_menu_sync_save_settings'] ) ) {
			$this->save_settings();
		}

		// Get current settings
		$current_settings = $this->settings->get_all();

		// Get available sites
		$available_sites = $this->settings->get_available_sites();

		// Include template
		include AVRO_MENU_SYNC_PLUGIN_DIR . 'templates/admin-settings.php';
	}

	/**
	 * Save settings
	 *
	 * @since 1.0.0
	 */
	private function save_settings() {
		// Verify nonce
		if ( ! isset( $_POST['avro_menu_sync_settings_nonce'] ) || 
		     ! wp_verify_nonce( $_POST['avro_menu_sync_settings_nonce'], 'avro_menu_sync_save_settings' ) ) {
			add_settings_error(
				'avro_menu_sync',
				'nonce_failed',
				__( 'Security check failed. Please try again.', 'avro-multisite-menu-sync' ),
				'error'
			);
			return;
		}

		// Prepare settings
		$new_settings = array(
			'source_site_id'         => isset( $_POST['source_site_id'] ) ? absint( $_POST['source_site_id'] ) : 1,
			'target_site_ids'        => isset( $_POST['target_site_ids'] ) ? array_map( 'absint', (array) $_POST['target_site_ids'] ) : array(),
			'sync_mode'              => isset( $_POST['sync_mode'] ) ? sanitize_text_field( $_POST['sync_mode'] ) : 'manual',
			'conflict_resolution'    => isset( $_POST['conflict_resolution'] ) ? sanitize_text_field( $_POST['conflict_resolution'] ) : 'override',
			'sync_menu_locations'    => isset( $_POST['sync_menu_locations'] ) ? true : false,
			'preserve_custom_fields' => isset( $_POST['preserve_custom_fields'] ) ? true : false,
			'enabled'                => isset( $_POST['enabled'] ) ? true : false,
		);

		// Update settings
		$result = $this->settings->update_all( $new_settings );

		if ( is_wp_error( $result ) ) {
			add_settings_error(
				'avro_menu_sync',
				$result->get_error_code(),
				sprintf(
					/* translators: %s: error message */
					__( 'Settings save failed: %s', 'avro-multisite-menu-sync' ),
					$result->get_error_message()
				),
				'error'
			);
		} else {
			$target_count = count( $new_settings['target_site_ids'] );
			add_settings_error(
				'avro_menu_sync',
				'settings_saved',
				sprintf(
					/* translators: %d: number of target sites */
					__( 'Settings saved successfully. %d target site(s) configured.', 'avro-multisite-menu-sync' ),
					$target_count
				),
				'success'
			);
		}
	}

	/**
	 * Render logs page
	 *
	 * @since 1.0.0
	 */
	public function render_logs() {
		// Check permissions
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'avro-multisite-menu-sync' ) );
		}

		// Get filter parameters
		$current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$per_page     = 20;
		$status       = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';

		// Build query args
		$args = array(
			'limit'  => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
		);

		if ( ! empty( $status ) ) {
			$args['status'] = $status;
		}

		// Get logs
		$logs        = $this->logger->get_logs( $args );
		$total_count = $this->logger->get_total_count( $args );
		$total_pages = ceil( $total_count / $per_page );

		// Get statistics
		$stats = $this->logger->get_statistics();

		// Include template
		include AVRO_MENU_SYNC_PLUGIN_DIR . 'templates/admin-logs.php';
	}
}
