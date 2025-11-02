<?php
/**
 * Menu Sync Logger Class
 *
 * Handles logging and audit trail for sync operations.
 *
 * @package Avro_Multisite_Menu_Sync
 * @subpackage Logger
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logger class
 *
 * @since 1.0.0
 */
class Menu_Sync_Logger {

	/**
	 * Table name
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->base_prefix . 'menu_sync_logs';
	}

	/**
	 * Log sync operation
	 *
	 * @since 1.0.0
	 * @param array $data Log data.
	 * @return int|false Log ID or false on failure.
	 */
	public function log( $data ) {
		global $wpdb;

		// Validate required fields
		$required_fields = array( 'source_site_id', 'target_site_id', 'menu_id', 'status' );
		foreach ( $required_fields as $field ) {
			if ( ! isset( $data[ $field ] ) ) {
				return false;
			}
		}

		// Prepare log entry
		$log_entry = array(
			'timestamp'       => current_time( 'mysql' ),
			'source_site_id'  => absint( $data['source_site_id'] ),
			'target_site_id'  => absint( $data['target_site_id'] ),
			'menu_id'         => absint( $data['menu_id'] ),
			'menu_name'       => isset( $data['menu_name'] ) ? sanitize_text_field( $data['menu_name'] ) : '',
			'operation'       => isset( $data['operation'] ) ? sanitize_text_field( $data['operation'] ) : 'sync',
			'status'          => sanitize_text_field( $data['status'] ),
			'message'         => isset( $data['message'] ) ? sanitize_textarea_field( $data['message'] ) : '',
			'items_synced'    => isset( $data['items_synced'] ) ? absint( $data['items_synced'] ) : 0,
			'conflicts'       => isset( $data['conflicts'] ) ? wp_json_encode( $data['conflicts'] ) : null,
			'user_id'         => get_current_user_id(),
		);

		// Insert into database
		$result = $wpdb->insert(
			$this->table_name,
			$log_entry,
			array( '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d' )
		);

		if ( false === $result ) {
			// Log to PHP error log if database insert fails
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Menu Sync: Failed to insert log entry - ' . $wpdb->last_error );
			}
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get logs with filters
	 *
	 * @since 1.0.0
	 * @param array $args Query arguments.
	 * @return array Log entries.
	 */
	public function get_logs( $args = array() ) {
		global $wpdb;

		// Default arguments
		$defaults = array(
			'source_site_id' => 0,
			'target_site_id' => 0,
			'menu_id'        => 0,
			'status'         => '',
			'start_date'     => '',
			'end_date'       => '',
			'limit'          => 50,
			'offset'         => 0,
			'orderby'        => 'timestamp',
			'order'          => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		// Build WHERE clause
		$where = array( '1=1' );
		$where_values = array();

		if ( ! empty( $args['source_site_id'] ) ) {
			$where[] = 'source_site_id = %d';
			$where_values[] = absint( $args['source_site_id'] );
		}

		if ( ! empty( $args['target_site_id'] ) ) {
			$where[] = 'target_site_id = %d';
			$where_values[] = absint( $args['target_site_id'] );
		}

		if ( ! empty( $args['menu_id'] ) ) {
			$where[] = 'menu_id = %d';
			$where_values[] = absint( $args['menu_id'] );
		}

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$where_values[] = sanitize_text_field( $args['status'] );
		}

		if ( ! empty( $args['start_date'] ) ) {
			$where[] = 'timestamp >= %s';
			$where_values[] = sanitize_text_field( $args['start_date'] );
		}

		if ( ! empty( $args['end_date'] ) ) {
			$where[] = 'timestamp <= %s';
			$where_values[] = sanitize_text_field( $args['end_date'] );
		}

		$where_clause = implode( ' AND ', $where );

		// Validate orderby
		$allowed_orderby = array( 'id', 'timestamp', 'source_site_id', 'target_site_id', 'status' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'timestamp';

		// Validate order
		$order = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		// Build query
		$query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$orderby} {$order}";

		// Add limit and offset
		if ( $args['limit'] > 0 ) {
			$query .= $wpdb->prepare( ' LIMIT %d', absint( $args['limit'] ) );
		}

		if ( $args['offset'] > 0 ) {
			$query .= $wpdb->prepare( ' OFFSET %d', absint( $args['offset'] ) );
		}

		// Prepare query with values
		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}

		// Execute query
		$results = $wpdb->get_results( $query, ARRAY_A );

		// Decode JSON fields
		if ( ! empty( $results ) ) {
			foreach ( $results as &$result ) {
				if ( ! empty( $result['conflicts'] ) ) {
					$result['conflicts'] = json_decode( $result['conflicts'], true );
				}
			}
		}

		return $results ? $results : array();
	}

	/**
	 * Get log by ID
	 *
	 * @since 1.0.0
	 * @param int $log_id Log ID.
	 * @return array|null Log entry or null.
	 */
	public function get_log( $log_id ) {
		global $wpdb;

		$log_id = absint( $log_id );
		if ( $log_id < 1 ) {
			return null;
		}

		$query = $wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE id = %d",
			$log_id
		);

		$result = $wpdb->get_row( $query, ARRAY_A );

		if ( $result && ! empty( $result['conflicts'] ) ) {
			$result['conflicts'] = json_decode( $result['conflicts'], true );
		}

		return $result;
	}

	/**
	 * Get total log count
	 *
	 * @since 1.0.0
	 * @param array $args Filter arguments.
	 * @return int Total count.
	 */
	public function get_total_count( $args = array() ) {
		global $wpdb;

		// Build WHERE clause (same as get_logs)
		$where = array( '1=1' );
		$where_values = array();

		if ( ! empty( $args['source_site_id'] ) ) {
			$where[] = 'source_site_id = %d';
			$where_values[] = absint( $args['source_site_id'] );
		}

		if ( ! empty( $args['target_site_id'] ) ) {
			$where[] = 'target_site_id = %d';
			$where_values[] = absint( $args['target_site_id'] );
		}

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$where_values[] = sanitize_text_field( $args['status'] );
		}

		$where_clause = implode( ' AND ', $where );

		$query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}

		return absint( $wpdb->get_var( $query ) );
	}

	/**
	 * Delete old logs
	 *
	 * @since 1.0.0
	 * @param int $days Days to keep (default 30).
	 * @return int Number of deleted logs.
	 */
	public function cleanup_old_logs( $days = 30 ) {
		global $wpdb;

		$days = absint( $days );
		if ( $days < 1 ) {
			$days = 30;
		}

		$date_threshold = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table_name} WHERE timestamp < %s",
				$date_threshold
			)
		);

		return absint( $deleted );
	}

	/**
	 * Get sync statistics
	 *
	 * @since 1.0.0
	 * @param array $args Filter arguments.
	 * @return array Statistics data.
	 */
	public function get_statistics( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'start_date' => '',
			'end_date'   => '',
		);

		$args = wp_parse_args( $args, $defaults );

		// Build WHERE clause
		$where = array( '1=1' );
		$where_values = array();

		if ( ! empty( $args['start_date'] ) ) {
			$where[] = 'timestamp >= %s';
			$where_values[] = sanitize_text_field( $args['start_date'] );
		}

		if ( ! empty( $args['end_date'] ) ) {
			$where[] = 'timestamp <= %s';
			$where_values[] = sanitize_text_field( $args['end_date'] );
		}

		$where_clause = implode( ' AND ', $where );

		// Get total syncs
		$query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}
		$total_syncs = absint( $wpdb->get_var( $query ) );

		// Get successful syncs
		$success_where = $where;
		$success_where[] = "status = 'success'";
		$success_clause = implode( ' AND ', $success_where );
		$query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$success_clause}";
		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}
		$successful_syncs = absint( $wpdb->get_var( $query ) );

		// Get failed syncs
		$failed_where = $where;
		$failed_where[] = "status = 'error'";
		$failed_clause = implode( ' AND ', $failed_where );
		$query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$failed_clause}";
		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}
		$failed_syncs = absint( $wpdb->get_var( $query ) );

		// Get total items synced
		$query = "SELECT SUM(items_synced) FROM {$this->table_name} WHERE {$where_clause}";
		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}
		$total_items = absint( $wpdb->get_var( $query ) );

		return array(
			'total_syncs'      => $total_syncs,
			'successful_syncs' => $successful_syncs,
			'failed_syncs'     => $failed_syncs,
			'total_items'      => $total_items,
			'success_rate'     => $total_syncs > 0 ? round( ( $successful_syncs / $total_syncs ) * 100, 2 ) : 0,
		);
	}

	/**
	 * Delete all logs
	 *
	 * @since 1.0.0
	 * @return int Number of deleted logs.
	 */
	public function delete_all_logs() {
		global $wpdb;

		$deleted = $wpdb->query( "TRUNCATE TABLE {$this->table_name}" );

		return absint( $deleted );
	}
}
