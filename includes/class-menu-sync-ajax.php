<?php
/**
 * Menu Sync AJAX Class
 *
 * Handles AJAX requests for the plugin.
 *
 * @package Avro_Multisite_Menu_Sync
 * @subpackage Ajax
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX handler class
 *
 * @since 1.0.0
 */
class Menu_Sync_Ajax {

	/**
	 * Handle manual sync AJAX request
	 *
	 * @since 1.0.0
	 */
	public static function handle_manual_sync() {
		// Verify nonce
		check_ajax_referer( 'avro_menu_sync_ajax', 'nonce' );

		// Check permissions
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_send_json_error( array(
				'message' => __( 'Insufficient permissions.', 'avro-multisite-menu-sync' ),
			), 403 );
		}

		// Get and validate menu ID
		$menu_id = isset( $_POST['menu_id'] ) ? absint( $_POST['menu_id'] ) : 0;
		if ( $menu_id < 1 ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid menu ID.', 'avro-multisite-menu-sync' ),
			), 400 );
		}

		// Get and validate target sites
		$site_ids = isset( $_POST['site_ids'] ) ? array_map( 'absint', (array) $_POST['site_ids'] ) : array();
		if ( empty( $site_ids ) ) {
			wp_send_json_error( array(
				'message' => __( 'No target sites specified.', 'avro-multisite-menu-sync' ),
			), 400 );
		}

		// Get plugin instances
		$core     = Menu_Sync_Core::get_instance();
		$engine   = $core->get_engine();
		$settings = $core->get_settings();

		// Perform sync
		$result = $engine->sync_menu( $menu_id, $site_ids );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'message' => $result->get_error_message(),
			), 500 );
		}

		// Prepare response
		$success_count = isset( $result['success'] ) ? count( $result['success'] ) : 0;
		$failed_count  = isset( $result['failed'] ) ? count( $result['failed'] ) : 0;

		// Sanitize site IDs in response
		$success_sites = isset( $result['success'] ) ? array_map( 'absint', array_keys( $result['success'] ) ) : array();
		$failed_sites  = isset( $result['failed'] ) ? array_map( 'absint', array_keys( $result['failed'] ) ) : array();

		wp_send_json_success( array(
			'message'       => sprintf(
				/* translators: 1: success count, 2: failed count */
				__( 'Sync completed. Success: %1$d, Failed: %2$d', 'avro-multisite-menu-sync' ),
				$success_count,
				$failed_count
			),
			'synced'        => $success_count,
			'failed'        => $failed_count,
			'success_sites' => $success_sites,
			'failed_sites'  => $failed_sites,
		) );
	}

	/**
	 * Handle get logs AJAX request
	 *
	 * @since 1.0.0
	 */
	public static function handle_get_logs() {
		// Verify nonce
		check_ajax_referer( 'avro_menu_sync_ajax', 'nonce' );

		// Check permissions
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_send_json_error( array(
				'message' => __( 'Insufficient permissions.', 'avro-multisite-menu-sync' ),
			), 403 );
		}

		// Get filter parameters
		$page     = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20;
		$status   = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';

		// Calculate offset
		$offset = ( $page - 1 ) * $per_page;

		// Build query args
		$args = array(
			'limit'  => $per_page,
			'offset' => $offset,
		);

		if ( ! empty( $status ) ) {
			$args['status'] = $status;
		}

		// Get logger instance
		$core   = Menu_Sync_Core::get_instance();
		$logger = $core->get_logger();

		// Get logs
		$logs        = $logger->get_logs( $args );
		$total_count = $logger->get_total_count( $args );

		// Format logs for display
		$formatted_logs = array();
		foreach ( $logs as $log ) {
			$formatted_logs[] = array(
				'id'             => absint( $log['id'] ),
				'timestamp'      => sanitize_text_field( $log['timestamp'] ),
				'source_site_id' => absint( $log['source_site_id'] ),
				'target_site_id' => absint( $log['target_site_id'] ),
				'menu_name'      => sanitize_text_field( $log['menu_name'] ),
				'status'         => sanitize_text_field( $log['status'] ),
				'message'        => sanitize_text_field( $log['message'] ),
				'items_synced'   => absint( $log['items_synced'] ),
			);
		}

		wp_send_json_success( array(
			'logs'        => $formatted_logs,
			'total'       => $total_count,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_count / $per_page ),
		) );
	}
}
