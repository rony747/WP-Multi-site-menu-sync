<?php
/**
 * Uninstall Script
 *
 * Fired when the plugin is uninstalled.
 *
 * @package Avro_Multisite_Menu_Sync
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if this is a multisite installation
if ( ! is_multisite() ) {
	return;
}

// Only proceed if user has proper permissions
if ( ! current_user_can( 'manage_network_options' ) ) {
	return;
}

global $wpdb;

// Delete plugin options
delete_site_option( 'avro_menu_sync_settings' );
delete_site_option( 'avro_menu_sync_version' );
delete_site_option( 'avro_menu_sync_activated' );
delete_site_option( 'avro_menu_sync_deactivated' );

// Delete transients
delete_site_transient( 'avro_menu_sync_source_menus' );
delete_site_transient( 'avro_menu_sync_target_sites' );

// Drop custom table
$table_name = $wpdb->base_prefix . 'menu_sync_logs';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

// Clear any cached data
wp_cache_flush();
