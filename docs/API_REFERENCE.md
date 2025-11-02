# API Reference: Avro Multisite Menu Sync

## Core Classes

### Menu_Sync_Core
Main plugin class handling initialization.

### Menu_Sync_Engine
Handles menu synchronization logic.

**Key Methods**:
- `sync_menu( $menu_id, $target_sites, $options )` - Sync menu to targets
- `extract_menu( $menu_id )` - Extract menu structure
- `apply_menu( $menu_data, $site_id, $options )` - Apply menu to site
- `map_object_id( $object_id, $type, $source, $target )` - Map IDs

### Menu_Sync_Settings
Manages plugin configuration.

**Key Methods**:
- `get( $key, $default )` - Get setting
- `update( $key, $value )` - Update setting
- `get_all()` - Get all settings
- `validate( $settings )` - Validate settings

### Menu_Sync_Logger
Handles logging and audit trail.

**Key Methods**:
- `log( $data )` - Log operation
- `get_logs( $args )` - Retrieve logs
- `cleanup_old_logs( $days )` - Delete old logs
- `get_statistics( $args )` - Get stats

## Hooks Reference

### Actions

#### avro_menu_sync_before_sync
Fires before menu synchronization starts.

```php
do_action( 'avro_menu_sync_before_sync', $menu_id, $target_sites, $options );
```

#### avro_menu_sync_after_sync
Fires after menu synchronization completes.

```php
do_action( 'avro_menu_sync_after_sync', $menu_id, $target_sites, $results );
```

#### avro_menu_sync_before_menu_create
Fires before creating menu on target site.

```php
do_action( 'avro_menu_sync_before_menu_create', $menu_data, $site_id );
```

#### avro_menu_sync_after_menu_create
Fires after creating menu on target site.

```php
do_action( 'avro_menu_sync_after_menu_create', $menu_id, $site_id, $menu_data );
```

#### avro_menu_sync_error
Fires when sync error occurs.

```php
do_action( 'avro_menu_sync_error', $error, $context );
```

### Filters

#### avro_menu_sync_source_menu
Modify source menu data before sync.

```php
$menu_data = apply_filters( 'avro_menu_sync_source_menu', $menu_data, $menu_id );
```

#### avro_menu_sync_target_sites
Modify target site list.

```php
$target_sites = apply_filters( 'avro_menu_sync_target_sites', $target_sites, $menu_id );
```

#### avro_menu_sync_menu_item
Modify individual menu item.

```php
$item = apply_filters( 'avro_menu_sync_menu_item', $item, $site_id, $menu_id );
```

#### avro_menu_sync_conflict_resolution
Custom conflict handling.

```php
$resolution = apply_filters( 'avro_menu_sync_conflict_resolution', $resolution, $menu_id, $site_id );
```

#### avro_menu_sync_object_id_map
Custom object ID mapping.

```php
$target_id = apply_filters( 'avro_menu_sync_object_id_map', $target_id, $source_id, $type, $source_site, $target_site );
```

## Helper Functions

### avro_menu_sync_get_settings()
Get plugin settings.

```php
$settings = avro_menu_sync_get_settings();
```

### avro_menu_sync_is_enabled()
Check if sync is enabled.

```php
if ( avro_menu_sync_is_enabled() ) {
    // Sync is active
}
```

### avro_menu_sync_get_source_site()
Get configured source site ID.

```php
$source_id = avro_menu_sync_get_source_site();
```

### avro_menu_sync_get_target_sites()
Get configured target site IDs.

```php
$targets = avro_menu_sync_get_target_sites();
```

## AJAX Endpoints

### avro_menu_sync_manual_sync
Trigger manual sync via AJAX.

**Action**: `wp_ajax_avro_menu_sync_manual_sync`

**Parameters**:
- `menu_id` (int) - Menu ID
- `site_ids` (array) - Target site IDs
- `nonce` (string) - Security nonce

**Response**:
```json
{
    "success": true,
    "data": {
        "synced": 3,
        "failed": 0,
        "message": "Menu synced successfully"
    }
}
```

### avro_menu_sync_get_logs
Retrieve sync logs via AJAX.

**Action**: `wp_ajax_avro_menu_sync_get_logs`

**Parameters**:
- `page` (int) - Page number
- `per_page` (int) - Items per page
- `filters` (array) - Filter criteria

## Usage Examples

### Basic Sync
```php
$result = avro_menu_sync_sync_menu( 5, array( 2, 3 ) );
```

### Custom Hook Usage
```php
add_filter( 'avro_menu_sync_menu_item', function( $item, $site_id ) {
    // Modify URLs for specific site
    if ( $site_id === 3 ) {
        $item['url'] = str_replace( 'http://', 'https://', $item['url'] );
    }
    return $item;
}, 10, 2 );
```

### Get Sync Statistics
```php
$logger = new Menu_Sync_Logger();
$stats = $logger->get_statistics( array(
    'start_date' => '2024-01-01',
    'end_date' => '2024-12-31',
) );
```
