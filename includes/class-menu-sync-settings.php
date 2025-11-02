<?php
/**
 * Menu Sync Settings Class
 *
 * Manages plugin settings and configuration.
 *
 * @package Avro_Multisite_Menu_Sync
 * @subpackage Settings
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings management class
 *
 * @since 1.0.0
 */
class Menu_Sync_Settings {

	/**
	 * Settings option name
	 *
	 * @var string
	 */
	private $option_name = 'avro_menu_sync_settings';

	/**
	 * Settings cache
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Default settings
	 *
	 * @var array
	 */
	private $defaults = array(
		'source_site_id'         => 1,
		'target_site_ids'        => array(),
		'sync_mode'              => 'manual',
		'conflict_resolution'    => 'override',
		'sync_menu_locations'    => true,
		'preserve_custom_fields' => true,
		'enabled'                => true,
		'last_sync'              => 0,
	);

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->load_settings();
	}

	/**
	 * Load settings from database
	 *
	 * @since 1.0.0
	 */
	private function load_settings() {
		$saved_settings = get_site_option( $this->option_name, array() );
		$this->settings = wp_parse_args( $saved_settings, $this->defaults );
	}

	/**
	 * Get setting value
	 *
	 * @since 1.0.0
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value if not found.
	 * @return mixed Setting value.
	 */
	public function get( $key, $default = null ) {
		if ( isset( $this->settings[ $key ] ) ) {
			return $this->settings[ $key ];
		}

		if ( null !== $default ) {
			return $default;
		}

		return isset( $this->defaults[ $key ] ) ? $this->defaults[ $key ] : null;
	}

	/**
	 * Update setting value
	 *
	 * @since 1.0.0
	 * @param string $key   Setting key.
	 * @param mixed  $value Setting value.
	 * @return bool Success status.
	 */
	public function update( $key, $value ) {
		// Validate the value
		$validated = $this->validate_setting( $key, $value );
		if ( is_wp_error( $validated ) ) {
			return false;
		}

		$this->settings[ $key ] = $validated;
		return update_site_option( $this->option_name, $this->settings );
	}

	/**
	 * Get all settings
	 *
	 * @since 1.0.0
	 * @return array All settings.
	 */
	public function get_all() {
		return $this->settings;
	}

	/**
	 * Update multiple settings
	 *
	 * @since 1.0.0
	 * @param array $settings Settings array.
	 * @return bool|WP_Error Success status or error.
	 */
	public function update_all( $settings ) {
		// Validate all settings
		$validated = $this->validate( $settings );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		// Merge with existing settings
		$this->settings = array_merge( $this->settings, $validated );

		// Save to database
		// Note: update_site_option returns false if value is unchanged, which is OK
		$result = update_site_option( $this->option_name, $this->settings );

		// Verify the save worked by checking if option exists
		$saved_value = get_site_option( $this->option_name );
		
		if ( false === $saved_value ) {
			return new WP_Error(
				'update_failed',
				__( 'Failed to update settings. Database error.', 'avro-multisite-menu-sync' )
			);
		}

		// Reload settings from database to ensure cache is fresh
		$this->load_settings();

		return true;
	}

	/**
	 * Reset to default settings
	 *
	 * @since 1.0.0
	 * @return bool Success status.
	 */
	public function reset() {
		$this->settings = $this->defaults;
		return update_site_option( $this->option_name, $this->settings );
	}

	/**
	 * Validate settings array
	 *
	 * @since 1.0.0
	 * @param array $settings Settings to validate.
	 * @return array|WP_Error Validated settings or error.
	 */
	public function validate( $settings ) {
		if ( ! is_array( $settings ) ) {
			return new WP_Error(
				'invalid_settings',
				__( 'Settings must be an array.', 'avro-multisite-menu-sync' )
			);
		}

		$validated = array();

		foreach ( $settings as $key => $value ) {
			$result = $this->validate_setting( $key, $value );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$validated[ $key ] = $result;
		}

		return $validated;
	}

	/**
	 * Validate individual setting
	 *
	 * @since 1.0.0
	 * @param string $key   Setting key.
	 * @param mixed  $value Setting value.
	 * @return mixed|WP_Error Validated value or error.
	 */
	private function validate_setting( $key, $value ) {
		switch ( $key ) {
			case 'source_site_id':
				$value = absint( $value );
				if ( $value < 1 ) {
					return new WP_Error(
						'invalid_source_site',
						__( 'Invalid source site ID.', 'avro-multisite-menu-sync' )
					);
				}
				// Verify site exists (use get_site for better compatibility)
				$site = get_site( $value );
				if ( ! $site ) {
					return new WP_Error(
						'site_not_found',
						sprintf(
							/* translators: %d: site ID */
							__( 'Source site (ID: %d) does not exist in the network.', 'avro-multisite-menu-sync' ),
							$value
						)
					);
				}
				break;

			case 'target_site_ids':
				if ( ! is_array( $value ) ) {
					return new WP_Error(
						'invalid_target_sites',
						__( 'Target sites must be an array.', 'avro-multisite-menu-sync' )
					);
				}
				// Sanitize and validate each site ID
				$value = array_map( 'absint', $value );
				$value = array_filter( $value, function( $site_id ) {
					return $site_id > 0 && get_site( $site_id );
				} );
				$value = array_values( $value ); // Re-index array
				break;

			case 'sync_mode':
				$allowed_modes = array( 'auto', 'manual' );
				if ( ! in_array( $value, $allowed_modes, true ) ) {
					return new WP_Error(
						'invalid_sync_mode',
						__( 'Invalid sync mode. Must be "auto" or "manual".', 'avro-multisite-menu-sync' )
					);
				}
				$value = sanitize_text_field( $value );
				break;

			case 'conflict_resolution':
				$allowed_strategies = array( 'override', 'skip', 'merge' );
				if ( ! in_array( $value, $allowed_strategies, true ) ) {
					return new WP_Error(
						'invalid_conflict_resolution',
						__( 'Invalid conflict resolution strategy.', 'avro-multisite-menu-sync' )
					);
				}
				$value = sanitize_text_field( $value );
				break;

			case 'sync_menu_locations':
			case 'preserve_custom_fields':
			case 'enabled':
				$value = (bool) $value;
				break;

			case 'last_sync':
				$value = absint( $value );
				break;

			default:
				// Unknown setting - sanitize as text
				$value = sanitize_text_field( $value );
				break;
		}

		return $value;
	}

	/**
	 * Get available sites in network
	 *
	 * @since 1.0.0
	 * @return array Array of site objects.
	 */
	public function get_available_sites() {
		$sites = get_sites( array(
			'number'  => 999,
			'orderby' => 'id',
			'order'   => 'ASC',
		) );

		return $sites;
	}

	/**
	 * Check if plugin is enabled
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_enabled() {
		return (bool) $this->get( 'enabled', true );
	}

	/**
	 * Check if auto sync is enabled
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_auto_sync_enabled() {
		return 'auto' === $this->get( 'sync_mode', 'manual' );
	}

	/**
	 * Get source site details
	 *
	 * @since 1.0.0
	 * @return object|false Site object or false.
	 */
	public function get_source_site() {
		$source_id = $this->get( 'source_site_id' );
		if ( empty( $source_id ) ) {
			return false;
		}

		return get_blog_details( $source_id );
	}

	/**
	 * Get target sites details
	 *
	 * @since 1.0.0
	 * @return array Array of site objects.
	 */
	public function get_target_sites() {
		$target_ids = $this->get( 'target_site_ids', array() );
		if ( empty( $target_ids ) || ! is_array( $target_ids ) ) {
			return array();
		}

		$sites = array();
		foreach ( $target_ids as $site_id ) {
			$site = get_blog_details( $site_id );
			if ( $site ) {
				$sites[] = $site;
			}
		}

		return $sites;
	}

	/**
	 * Update last sync timestamp
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function update_last_sync() {
		return $this->update( 'last_sync', time() );
	}

	/**
	 * Get last sync timestamp
	 *
	 * @since 1.0.0
	 * @return int Unix timestamp.
	 */
	public function get_last_sync() {
		return absint( $this->get( 'last_sync', 0 ) );
	}
}
