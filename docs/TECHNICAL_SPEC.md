# Technical Specification: Avro Multisite Menu Sync

## 1. System Overview

### 1.1 Purpose
Synchronize WordPress navigation menus across multiple sites in a multisite network, maintaining consistency while allowing for flexible configuration.

### 1.2 Scope
- Menu synchronization from source to target sites
- Menu item hierarchy and metadata preservation
- Custom menu item types support
- Conflict resolution
- Logging and audit trail

## 2. Architecture

### 2.1 High-Level Architecture

```
┌─────────────────────────────────────────────────────┐
│           Network Admin Interface                    │
│  (Configuration, Monitoring, Manual Sync)           │
└──────────────────┬──────────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────────┐
│              Core Plugin Layer                       │
│  - Settings Management                              │
│  - Hook Registration                                │
│  - Permission Checks                                │
└──────────────────┬──────────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────────┐
│           Synchronization Engine                     │
│  - Menu Extraction                                  │
│  - Menu Comparison                                  │
│  - Menu Application                                 │
│  - Conflict Resolution                              │
└──────────────────┬──────────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────────┐
│              Data Layer                             │
│  - WordPress Menu API                               │
│  - Site Switching                                   │
│  - Database Operations                              │
└─────────────────────────────────────────────────────┘
```

### 2.2 Component Breakdown

#### 2.2.1 Core Plugin (`class-menu-sync-core.php`)
- Plugin initialization
- Hook registration
- Dependency management
- Version management

#### 2.2.2 Admin Interface (`class-menu-sync-admin.php`)
- Network admin pages
- Settings UI
- Dashboard widgets
- AJAX handlers

#### 2.2.3 Sync Engine (`class-menu-sync-engine.php`)
- Menu extraction from source
- Menu comparison logic
- Menu creation/update on targets
- Conflict detection and resolution

#### 2.2.4 Logger (`class-menu-sync-logger.php`)
- Sync operation logging
- Error tracking
- Audit trail
- Report generation

#### 2.2.5 Settings Manager (`class-menu-sync-settings.php`)
- Configuration storage
- Settings validation
- Default values
- Network options management

## 3. Data Models

### 3.1 Plugin Settings (Network Options)

```php
[
    'source_site_id' => int,           // Source site ID
    'target_site_ids' => array,        // Array of target site IDs
    'sync_mode' => string,             // 'auto' or 'manual'
    'conflict_resolution' => string,   // 'override', 'skip', 'merge'
    'sync_menu_locations' => bool,     // Sync menu location assignments
    'sync_menu_items' => array,        // Which item types to sync
    'preserve_custom_fields' => bool,  // Keep custom fields on targets
    'enabled' => bool,                 // Plugin enabled/disabled
    'last_sync' => timestamp,          // Last sync timestamp
]
```

### 3.2 Menu Structure

```php
[
    'menu_id' => int,
    'menu_name' => string,
    'menu_slug' => string,
    'locations' => array,              // Theme locations
    'items' => [
        [
            'item_id' => int,
            'parent_id' => int,
            'position' => int,
            'type' => string,          // post, page, custom, category, etc.
            'object' => string,        // post_type or taxonomy name
            'object_id' => int,
            'title' => string,
            'url' => string,
            'target' => string,
            'classes' => array,
            'xfn' => string,
            'description' => string,
            'attr_title' => string,
            'meta' => array,           // Custom fields
        ]
    ]
]
```

### 3.3 Sync Log Entry

```php
[
    'id' => int,
    'timestamp' => datetime,
    'source_site_id' => int,
    'target_site_id' => int,
    'menu_id' => int,
    'menu_name' => string,
    'operation' => string,             // 'create', 'update', 'delete'
    'status' => string,                // 'success', 'error', 'warning'
    'message' => string,
    'items_synced' => int,
    'conflicts' => array,
    'user_id' => int,
]
```

## 4. Core Functionality

### 4.1 Menu Extraction

**Function**: Extract complete menu structure from source site

**Process**:
1. Switch to source site context
2. Get all registered menus
3. For each menu:
   - Get menu object
   - Get all menu items with `wp_get_nav_menu_items()`
   - Get menu location assignments
   - Extract item hierarchy
   - Collect custom fields and metadata
4. Build normalized menu structure
5. Restore original site context

**Key WordPress Functions**:
- `wp_get_nav_menus()`
- `wp_get_nav_menu_items()`
- `get_nav_menu_locations()`
- `get_post_meta()`

### 4.2 Menu Synchronization

**Function**: Apply menu structure to target sites

**Process**:
1. Validate source menu exists
2. For each target site:
   - Switch to target site context
   - Check if menu exists (by slug)
   - If exists and conflict_resolution = 'skip': skip
   - If exists and conflict_resolution = 'override': delete and recreate
   - If exists and conflict_resolution = 'merge': update items
   - If not exists: create new menu
   - Create/update menu items in order
   - Preserve hierarchy (parent-child relationships)
   - Map object IDs (posts, pages, categories)
   - Assign to theme locations if configured
   - Log operation
3. Restore original site context

**Key WordPress Functions**:
- `wp_create_nav_menu()`
- `wp_update_nav_menu_item()`
- `wp_delete_nav_menu()`
- `set_theme_mod()`

### 4.3 Object ID Mapping

**Challenge**: Post IDs, page IDs, and term IDs differ across sites

**Solution**:
1. For post/page menu items:
   - Get post slug from source
   - Find matching post by slug on target
   - Use target post ID
   - If not found: log warning, create custom link instead

2. For taxonomy term items:
   - Get term slug and taxonomy
   - Find matching term on target
   - Use target term ID
   - If not found: log warning, skip or create custom link

3. For custom links:
   - Use URL as-is
   - Optionally convert absolute URLs to relative

### 4.4 Conflict Resolution

**Scenarios**:

1. **Menu exists on target with same slug**
   - Override: Delete existing, create new
   - Skip: Keep existing, don't sync
   - Merge: Update items, preserve unmatched items

2. **Referenced object doesn't exist on target**
   - Convert to custom link
   - Skip item
   - Log warning

3. **Menu location already assigned**
   - Override: Reassign to synced menu
   - Skip: Keep existing assignment

## 5. Database Schema

### 5.1 Custom Tables

#### sync_logs
```sql
CREATE TABLE {prefix}_menu_sync_logs (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME NOT NULL,
    source_site_id BIGINT(20) NOT NULL,
    target_site_id BIGINT(20) NOT NULL,
    menu_id BIGINT(20) NOT NULL,
    menu_name VARCHAR(255) NOT NULL,
    operation VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL,
    message TEXT,
    items_synced INT,
    conflicts TEXT,
    user_id BIGINT(20),
    INDEX idx_timestamp (timestamp),
    INDEX idx_source_site (source_site_id),
    INDEX idx_target_site (target_site_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 5.2 WordPress Tables Used

- `wp_terms` - Menu terms
- `wp_term_taxonomy` - Menu taxonomy
- `wp_term_relationships` - Menu item relationships
- `wp_posts` - Menu items (nav_menu_item post type)
- `wp_postmeta` - Menu item metadata
- `wp_options` / `wp_sitemeta` - Settings and configuration

## 6. Security Considerations

### 6.1 Permissions
- Only network administrators can configure sync
- Use `is_super_admin()` checks
- Capability: `manage_network_options`

### 6.2 Data Validation
- Sanitize all input with `sanitize_text_field()`, `absint()`, etc.
- Validate site IDs exist in network
- Verify menu IDs before operations
- Escape output with `esc_html()`, `esc_url()`, etc.

### 6.3 Nonce Verification
- Use WordPress nonces for all forms
- Verify nonces before processing
- Use `wp_verify_nonce()` and `check_admin_referer()`

### 6.4 SQL Injection Prevention
- Use `$wpdb->prepare()` for all queries
- Never concatenate user input into SQL

## 7. Performance Considerations

### 7.1 Optimization Strategies
- Batch operations where possible
- Use transients for caching
- Implement background processing for large syncs
- Limit number of sites synced per operation
- Use WordPress object cache

### 7.2 Resource Management
- Set appropriate execution time limits
- Monitor memory usage
- Implement pagination for large menus
- Use `switch_to_blog()` efficiently

### 7.3 Scalability
- Support for 100+ sites in network
- Handle menus with 500+ items
- Queue system for async processing
- Rate limiting for API operations

## 8. Error Handling

### 8.1 Error Types
- Site not found
- Menu not found
- Permission denied
- Database errors
- Object mapping failures
- Network connectivity issues

### 8.2 Error Recovery
- Transaction rollback on failure
- Partial sync completion
- Retry mechanism with exponential backoff
- Detailed error logging
- Admin notifications

## 9. Hooks and Filters

### 9.1 Actions
- `avro_menu_sync_before_sync` - Before sync starts
- `avro_menu_sync_after_sync` - After sync completes
- `avro_menu_sync_before_menu_create` - Before menu creation
- `avro_menu_sync_after_menu_create` - After menu creation
- `avro_menu_sync_error` - On error

### 9.2 Filters
- `avro_menu_sync_source_menu` - Modify source menu data
- `avro_menu_sync_target_sites` - Modify target site list
- `avro_menu_sync_menu_item` - Modify individual menu item
- `avro_menu_sync_conflict_resolution` - Custom conflict handling
- `avro_menu_sync_object_id_map` - Custom object ID mapping

## 10. Testing Requirements

### 10.1 Unit Tests
- Menu extraction
- Object ID mapping
- Conflict resolution logic
- Settings validation

### 10.2 Integration Tests
- Full sync workflow
- Multi-site operations
- Database operations
- Hook execution

### 10.3 Manual Testing Scenarios
- Fresh installation
- Existing menus on targets
- Missing referenced objects
- Large menu structures
- Network with many sites

## 11. Future Enhancements

- Selective menu synchronization (choose specific menus)
- Bidirectional sync
- Scheduled sync operations
- REST API endpoints
- WP-CLI commands
- Sync history and rollback
- Menu templates
- Import/export functionality
