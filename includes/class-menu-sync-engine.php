<?php
/**
 * Menu Sync Engine Class
 *
 * Handles core menu synchronization logic.
 *
 * @package Avro_Multisite_Menu_Sync
 * @subpackage Engine
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Synchronization engine class
 *
 * @since 1.0.0
 */
class Menu_Sync_Engine {

	/**
	 * Settings instance
	 *
	 * @var Menu_Sync_Settings
	 */
	private $settings;

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
	 * @param Menu_Sync_Logger   $logger   Logger instance.
	 */
	public function __construct( $settings, $logger ) {
		$this->settings = $settings;
		$this->logger   = $logger;
	}

	/**
	 * Synchronize menu to target sites
	 *
	 * @since 1.0.0
	 * @param int   $menu_id      Menu ID to sync.
	 * @param array $target_sites Target site IDs.
	 * @param array $options      Sync options.
	 * @return array|WP_Error Sync results or error.
	 */
	public function sync_menu( $menu_id, $target_sites, $options = array() ) {
		// Validate inputs
		$menu_id = absint( $menu_id );
		if ( $menu_id < 1 ) {
			return new WP_Error( 'invalid_menu_id', __( 'Invalid menu ID.', 'avro-multisite-menu-sync' ) );
		}

		if ( empty( $target_sites ) || ! is_array( $target_sites ) ) {
			return new WP_Error( 'no_target_sites', __( 'No target sites specified.', 'avro-multisite-menu-sync' ) );
		}

		// Parse options
		$defaults = array(
			'conflict_resolution'    => $this->settings->get( 'conflict_resolution', 'override' ),
			'sync_locations'         => $this->settings->get( 'sync_menu_locations', true ),
			'preserve_custom_fields' => $this->settings->get( 'preserve_custom_fields', true ),
		);
		$options = wp_parse_args( $options, $defaults );

		// Extract menu from source
		$source_site_id = get_current_blog_id();
		$menu_data = $this->extract_menu( $menu_id );

		if ( is_wp_error( $menu_data ) ) {
			return $menu_data;
		}

		// Fire before sync action
		do_action( 'avro_menu_sync_before_sync', $menu_id, $target_sites, $options );

		// Sync to each target site
		$results = array(
			'success' => array(),
			'failed'  => array(),
		);

		foreach ( $target_sites as $target_site_id ) {
			$target_site_id = absint( $target_site_id );

			if ( $target_site_id < 1 ) {
				continue;
			}

			// Skip if target is same as source
			if ( $target_site_id === $source_site_id ) {
				continue;
			}

			// Apply menu to target site
			$result = $this->apply_menu( $menu_data, $target_site_id, $options );

			if ( is_wp_error( $result ) ) {
				$results['failed'][ $target_site_id ] = $result->get_error_message();

				// Log failure
				$this->logger->log( array(
					'source_site_id' => $source_site_id,
					'target_site_id' => $target_site_id,
					'menu_id'        => $menu_id,
					'menu_name'      => $menu_data['menu_name'],
					'operation'      => 'sync',
					'status'         => 'error',
					'message'        => $result->get_error_message(),
				) );
			} else {
				$results['success'][ $target_site_id ] = $result;

				// Log success
				$this->logger->log( array(
					'source_site_id' => $source_site_id,
					'target_site_id' => $target_site_id,
					'menu_id'        => $menu_id,
					'menu_name'      => $menu_data['menu_name'],
					'operation'      => 'sync',
					'status'         => 'success',
					'message'        => __( 'Menu synced successfully.', 'avro-multisite-menu-sync' ),
					'items_synced'   => count( $menu_data['items'] ),
				) );
			}
		}

		// Fire after sync action
		do_action( 'avro_menu_sync_after_sync', $menu_id, $target_sites, $results );

		// Update last sync timestamp
		$this->settings->update_last_sync();

		return $results;
	}

	/**
	 * Extract menu structure from source site
	 *
	 * @since 1.0.0
	 * @param int $menu_id Menu ID.
	 * @return array|WP_Error Menu data or error.
	 */
	public function extract_menu( $menu_id ) {
		$menu_id = absint( $menu_id );

		// Get menu object
		$menu = wp_get_nav_menu_object( $menu_id );

		if ( ! $menu || is_wp_error( $menu ) ) {
			return new WP_Error( 'menu_not_found', __( 'Menu not found.', 'avro-multisite-menu-sync' ) );
		}

		// Get menu items
		$menu_items = wp_get_nav_menu_items( $menu_id );

		if ( ! $menu_items ) {
			$menu_items = array();
		}

		// Build menu data structure
		$menu_data = array(
			'menu_id'    => $menu->term_id,
			'menu_name'  => $menu->name,
			'menu_slug'  => $menu->slug,
			'locations'  => array(),
			'items'      => array(),
		);

		// Get menu locations
		$locations = get_nav_menu_locations();
		foreach ( $locations as $location => $assigned_menu_id ) {
			if ( absint( $assigned_menu_id ) === absint( $menu_id ) ) {
				$menu_data['locations'][] = $location;
			}
		}

		// Extract menu items
		foreach ( $menu_items as $item ) {
			$menu_data['items'][] = $this->extract_menu_item( $item );
		}

		// Allow filtering
		return apply_filters( 'avro_menu_sync_source_menu', $menu_data, $menu_id );
	}

	/**
	 * Extract menu item data
	 *
	 * @since 1.0.0
	 * @param object $item Menu item object.
	 * @return array Menu item data.
	 */
	private function extract_menu_item( $item ) {
		$item_data = array(
			'item_id'     => $item->ID,
			'parent_id'   => absint( $item->menu_item_parent ),
			'position'    => absint( $item->menu_order ),
			'type'        => sanitize_text_field( $item->type ),
			'object'      => sanitize_text_field( $item->object ),
			'object_id'   => absint( $item->object_id ),
			'title'       => sanitize_text_field( $item->title ),
			'url'         => esc_url_raw( $item->url ),
			'target'      => sanitize_text_field( $item->target ),
			'classes'     => array_map( 'sanitize_html_class', $item->classes ),
			'xfn'         => sanitize_text_field( $item->xfn ),
			'description' => sanitize_textarea_field( $item->description ),
			'attr_title'  => sanitize_text_field( $item->attr_title ),
		);

		// Get custom fields
		$item_data['meta'] = array();
		$meta_keys = array( '_menu_item_type', '_menu_item_menu_item_parent', '_menu_item_object_id', '_menu_item_object', '_menu_item_target', '_menu_item_classes', '_menu_item_xfn', '_menu_item_url' );
		
		foreach ( $meta_keys as $meta_key ) {
			$value = get_post_meta( $item->ID, $meta_key, true );
			if ( ! empty( $value ) ) {
				$item_data['meta'][ $meta_key ] = $value;
			}
		}

		return $item_data;
	}

	/**
	 * Apply menu to target site
	 *
	 * @since 1.0.0
	 * @param array $menu_data Menu structure.
	 * @param int   $site_id   Target site ID.
	 * @param array $options   Application options.
	 * @return bool|WP_Error Success or error.
	 */
	public function apply_menu( $menu_data, $site_id, $options = array() ) {
		$site_id = absint( $site_id );

		// Verify site exists
		if ( ! get_blog_details( $site_id ) ) {
			return new WP_Error( 'site_not_found', __( 'Target site not found.', 'avro-multisite-menu-sync' ) );
		}

		// Switch to target site
		switch_to_blog( $site_id );

		// Fire before menu create action
		do_action( 'avro_menu_sync_before_menu_create', $menu_data, $site_id );

		// Check if menu exists
		$existing_menu = wp_get_nav_menu_object( $menu_data['menu_slug'] );

		if ( $existing_menu ) {
			// Handle conflict
			$result = $this->resolve_conflict( $options['conflict_resolution'], $existing_menu, $menu_data );
			if ( is_wp_error( $result ) ) {
				restore_current_blog();
				return $result;
			}
			$menu_id = $existing_menu->term_id;
		} else {
			// Create new menu
			$menu_id = wp_create_nav_menu( $menu_data['menu_name'] );
			if ( is_wp_error( $menu_id ) ) {
				restore_current_blog();
				return $menu_id;
			}
		}

		// Clear existing menu items if override
		if ( 'override' === $options['conflict_resolution'] && $existing_menu ) {
			$existing_items = wp_get_nav_menu_items( $menu_id );
			if ( $existing_items ) {
				foreach ( $existing_items as $existing_item ) {
					wp_delete_post( $existing_item->ID, true );
				}
			}
		}

		// Create menu items
		$item_id_map = array(); // Map old IDs to new IDs for parent relationships

		foreach ( $menu_data['items'] as $item_data ) {
			$new_item_id = $this->create_menu_item( $menu_id, $item_data, $item_id_map, $site_id );
			
			if ( ! is_wp_error( $new_item_id ) ) {
				$item_id_map[ $item_data['item_id'] ] = $new_item_id;
			}
		}

		// Assign menu locations
		if ( ! empty( $options['sync_locations'] ) && ! empty( $menu_data['locations'] ) ) {
			$this->assign_menu_locations( $menu_id, $menu_data['locations'] );
		}

		// Fire after menu create action
		do_action( 'avro_menu_sync_after_menu_create', $menu_id, $site_id, $menu_data );

		// Restore original site
		restore_current_blog();

		return true;
	}

	/**
	 * Create menu item on target site
	 *
	 * @since 1.0.0
	 * @param int   $menu_id     Menu ID.
	 * @param array $item_data   Item data.
	 * @param array $item_id_map ID mapping array.
	 * @param int   $site_id     Target site ID.
	 * @return int|WP_Error New item ID or error.
	 */
	private function create_menu_item( $menu_id, $item_data, $item_id_map, $site_id ) {
		// Map object ID if needed
		$object_id = $item_data['object_id'];
		if ( in_array( $item_data['type'], array( 'post_type', 'taxonomy' ), true ) && $object_id > 0 ) {
			$mapped_id = $this->map_object_id( $object_id, $item_data['object'], $site_id );
			if ( $mapped_id ) {
				$object_id = $mapped_id;
			} else {
				// Convert to custom link if object not found
				$item_data['type'] = 'custom';
				$object_id = 0;
			}
		}

		// Map parent ID
		$parent_id = 0;
		if ( $item_data['parent_id'] > 0 && isset( $item_id_map[ $item_data['parent_id'] ] ) ) {
			$parent_id = $item_id_map[ $item_data['parent_id'] ];
		}

		// Prepare menu item args with sanitization
		$args = array(
			'menu-item-title'       => sanitize_text_field( $item_data['title'] ),
			'menu-item-url'         => esc_url_raw( $item_data['url'] ),
			'menu-item-type'        => sanitize_key( $item_data['type'] ),
			'menu-item-object'      => sanitize_key( $item_data['object'] ),
			'menu-item-object-id'   => absint( $object_id ),
			'menu-item-parent-id'   => absint( $parent_id ),
			'menu-item-position'    => absint( $item_data['position'] ),
			'menu-item-target'      => sanitize_text_field( $item_data['target'] ),
			'menu-item-classes'     => sanitize_text_field( implode( ' ', $item_data['classes'] ) ),
			'menu-item-xfn'         => sanitize_text_field( $item_data['xfn'] ),
			'menu-item-description' => sanitize_textarea_field( $item_data['description'] ),
			'menu-item-attr-title'  => sanitize_text_field( $item_data['attr_title'] ),
			'menu-item-status'      => 'publish',
		);

		// Allow filtering
		$args = apply_filters( 'avro_menu_sync_menu_item', $args, $item_data, $site_id );

		// Create menu item
		$new_item_id = wp_update_nav_menu_item( $menu_id, 0, $args );

		return $new_item_id;
	}

	/**
	 * Map object ID from source to target site
	 *
	 * @since 1.0.0
	 * @param int    $object_id   Source object ID.
	 * @param string $object_type Object type.
	 * @param int    $target_site Target site ID.
	 * @return int|false Target object ID or false.
	 */
	private function map_object_id( $object_id, $object_type, $target_site ) {
		$source_site = get_current_blog_id();

		// Get source object
		switch_to_blog( $source_site );
		
		$source_object = null;
		$slug = '';

		if ( 'post_type' === $object_type || in_array( $object_type, get_post_types(), true ) ) {
			$source_object = get_post( $object_id );
			if ( $source_object ) {
				$slug = $source_object->post_name;
			}
		} elseif ( 'taxonomy' === $object_type || in_array( $object_type, get_taxonomies(), true ) ) {
			$source_object = get_term( $object_id, $object_type );
			if ( $source_object && ! is_wp_error( $source_object ) ) {
				$slug = $source_object->slug;
			}
		}

		restore_current_blog();

		if ( empty( $slug ) ) {
			return false;
		}

		// Find matching object on target site
		switch_to_blog( $target_site );

		$target_id = false;

		if ( 'post_type' === $object_type || in_array( $object_type, get_post_types(), true ) ) {
			$posts = get_posts( array(
				'name'           => $slug,
				'post_type'      => $object_type,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			) );
			if ( ! empty( $posts ) ) {
				$target_id = $posts[0];
			}
		} elseif ( 'taxonomy' === $object_type || in_array( $object_type, get_taxonomies(), true ) ) {
			$term = get_term_by( 'slug', $slug, $object_type );
			if ( $term && ! is_wp_error( $term ) ) {
				$target_id = $term->term_id;
			}
		}

		restore_current_blog();

		// Allow filtering
		return apply_filters( 'avro_menu_sync_object_id_map', $target_id, $object_id, $object_type, $source_site, $target_site );
	}

	/**
	 * Resolve conflict when menu exists
	 *
	 * @since 1.0.0
	 * @param string $resolution Conflict resolution strategy.
	 * @param object $menu       Existing menu object.
	 * @param array  $menu_data  New menu data.
	 * @return bool|WP_Error Success or error.
	 */
	private function resolve_conflict( $resolution, $menu, $menu_data ) {
		$resolution = apply_filters( 'avro_menu_sync_conflict_resolution', $resolution, $menu->term_id, $menu_data );

		switch ( $resolution ) {
			case 'override':
				// Will be handled by clearing items in apply_menu
				return true;

			case 'skip':
				return new WP_Error( 'menu_exists', __( 'Menu already exists. Skipping sync.', 'avro-multisite-menu-sync' ) );

			case 'merge':
				// Merge strategy - update existing items, keep others
				return true;

			default:
				return new WP_Error( 'invalid_resolution', __( 'Invalid conflict resolution strategy.', 'avro-multisite-menu-sync' ) );
		}
	}

	/**
	 * Assign menu to theme locations
	 *
	 * @since 1.0.0
	 * @param int   $menu_id   Menu ID.
	 * @param array $locations Location names.
	 */
	private function assign_menu_locations( $menu_id, $locations ) {
		$menu_locations = get_nav_menu_locations();

		foreach ( $locations as $location ) {
			$menu_locations[ $location ] = $menu_id;
		}

		set_theme_mod( 'nav_menu_locations', $menu_locations );
	}
}
