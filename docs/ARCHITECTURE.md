# Architecture Overview: Avro Multisite Menu Sync

## System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    WordPress Multisite                       │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐            │
│  │  Site 1    │  │  Site 2    │  │  Site 3    │            │
│  │  (Source)  │  │  (Target)  │  │  (Target)  │            │
│  └─────┬──────┘  └──────▲─────┘  └──────▲─────┘            │
│        │                 │                │                  │
│        │                 │                │                  │
│  ┌─────▼─────────────────┴────────────────┴──────────────┐  │
│  │         Avro Multisite Menu Sync Plugin               │  │
│  │                                                        │  │
│  │  ┌──────────────┐  ┌──────────────┐  ┌────────────┐  │  │
│  │  │   Admin UI   │  │  Sync Engine │  │   Logger   │  │  │
│  │  └──────┬───────┘  └──────┬───────┘  └─────┬──────┘  │  │
│  │         │                  │                 │         │  │
│  │  ┌──────▼──────────────────▼─────────────────▼──────┐  │  │
│  │  │              Core Controller                     │  │  │
│  │  └──────────────────────┬───────────────────────────┘  │  │
│  │                         │                              │  │
│  │  ┌──────────────────────▼───────────────────────────┐  │  │
│  │  │           WordPress Menu API                     │  │  │
│  │  └──────────────────────────────────────────────────┘  │  │
│  └────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

## Component Architecture

### 1. Core Layer

**Menu_Sync_Core**
- Plugin initialization
- Hook registration
- Dependency injection
- Lifecycle management

**Responsibilities**:
- Load plugin components
- Register WordPress hooks
- Handle activation/deactivation
- Version management

### 2. Admin Layer

**Menu_Sync_Admin**
- Network admin interface
- Settings pages
- Dashboard widgets
- User interactions

**Components**:
- Settings page
- Dashboard page
- Logs viewer
- AJAX handlers

### 3. Business Logic Layer

**Menu_Sync_Engine**
- Core synchronization logic
- Menu extraction
- Menu application
- Object ID mapping

**Menu_Sync_Settings**
- Configuration management
- Settings validation
- Default values
- Option storage

**Menu_Sync_Logger**
- Operation logging
- Error tracking
- Statistics generation
- Log cleanup

### 4. Data Layer

**WordPress APIs**:
- `wp_get_nav_menus()`
- `wp_get_nav_menu_items()`
- `wp_create_nav_menu()`
- `wp_update_nav_menu_item()`
- `switch_to_blog()` / `restore_current_blog()`

**Database Tables**:
- `wp_terms` - Menu terms
- `wp_term_taxonomy` - Menu taxonomy
- `wp_posts` - Menu items
- `wp_postmeta` - Item metadata
- `wp_menu_sync_logs` - Custom log table

## Data Flow

### Synchronization Flow

```
1. User saves menu on source site
   ↓
2. WordPress fires 'wp_update_nav_menu' hook
   ↓
3. Plugin intercepts hook
   ↓
4. Check if auto-sync enabled
   ↓
5. Extract menu structure from source
   │
   ├─ Get menu object
   ├─ Get all menu items
   ├─ Build hierarchy
   └─ Collect metadata
   ↓
6. For each target site:
   │
   ├─ Switch to target site context
   ├─ Check for existing menu
   ├─ Apply conflict resolution
   ├─ Map object IDs
   ├─ Create/update menu
   ├─ Create/update menu items
   ├─ Assign menu locations
   ├─ Log operation
   └─ Restore site context
   ↓
7. Return results
   ↓
8. Display admin notice
```

### Menu Extraction Process

```
extract_menu($menu_id)
   ↓
1. Validate menu exists
   ↓
2. Get menu object
   ├─ term_id
   ├─ name
   ├─ slug
   └─ description
   ↓
3. Get menu items
   ├─ wp_get_nav_menu_items()
   └─ Order by menu_order
   ↓
4. For each item:
   ├─ Extract base properties
   ├─ Get metadata
   ├─ Determine parent relationship
   └─ Build item array
   ↓
5. Get menu locations
   ├─ get_nav_menu_locations()
   └─ Find assigned locations
   ↓
6. Return structured array
```

### Menu Application Process

```
apply_menu($menu_data, $site_id)
   ↓
1. Switch to target site
   ↓
2. Check if menu exists
   ├─ Yes → Apply conflict resolution
   └─ No → Create new menu
   ↓
3. Create/update menu term
   ├─ wp_create_nav_menu()
   └─ or wp_update_term()
   ↓
4. Clear existing items (if override)
   ↓
5. For each menu item:
   │
   ├─ Map object ID
   │  ├─ Post/Page: Find by slug
   │  ├─ Category: Find by slug
   │  └─ Custom: Use as-is
   │
   ├─ Create menu item
   │  └─ wp_update_nav_menu_item()
   │
   └─ Set metadata
      └─ update_post_meta()
   ↓
6. Assign menu locations
   ├─ get_nav_menu_locations()
   └─ set_theme_mod()
   ↓
7. Restore original site
   ↓
8. Return success/error
```

## Design Patterns

### 1. Singleton Pattern
Used in core classes to ensure single instance:

```php
class Menu_Sync_Core {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {}
}
```

### 2. Strategy Pattern
For conflict resolution:

```php
interface Conflict_Resolution_Strategy {
    public function resolve($existing_menu, $new_menu_data);
}

class Override_Strategy implements Conflict_Resolution_Strategy {
    public function resolve($existing_menu, $new_menu_data) {
        // Delete and recreate
    }
}

class Skip_Strategy implements Conflict_Resolution_Strategy {
    public function resolve($existing_menu, $new_menu_data) {
        // Keep existing
    }
}
```

### 3. Observer Pattern
Using WordPress hooks:

```php
// Subject
do_action('avro_menu_sync_before_sync', $menu_id, $targets);

// Observers
add_action('avro_menu_sync_before_sync', function($menu_id, $targets) {
    // Custom logic
});
```

### 4. Factory Pattern
For creating menu items:

```php
class Menu_Item_Factory {
    public static function create($type, $data) {
        switch ($type) {
            case 'post':
                return new Post_Menu_Item($data);
            case 'custom':
                return new Custom_Menu_Item($data);
            default:
                return new Generic_Menu_Item($data);
        }
    }
}
```

## Security Architecture

### 1. Authentication & Authorization
- Network admin capability checks
- `is_super_admin()` verification
- `current_user_can('manage_network_options')`

### 2. Input Validation
- Sanitization functions
- Type checking
- Whitelist validation

### 3. Output Escaping
- Context-aware escaping
- `esc_html()`, `esc_attr()`, `esc_url()`

### 4. Nonce Protection
- Form nonces
- AJAX nonces
- Action verification

### 5. SQL Injection Prevention
- Prepared statements
- `$wpdb->prepare()`
- No direct queries

## Performance Considerations

### 1. Caching Strategy
- Transient API for menu data
- Object cache for frequent queries
- Cache invalidation on updates

### 2. Batch Processing
- Process sites in batches
- Prevent timeouts
- Progress tracking

### 3. Database Optimization
- Indexed columns
- Efficient queries
- Minimal joins

### 4. Resource Management
- Memory limit monitoring
- Execution time management
- Background processing for large operations

## Scalability

### Horizontal Scaling
- Supports unlimited sites
- Batch processing for large networks
- Queue system for async operations

### Vertical Scaling
- Optimized queries
- Efficient memory usage
- Minimal database writes

## Error Handling

### Error Levels
1. **Critical**: Sync completely fails
2. **Error**: Individual site sync fails
3. **Warning**: Item mapping issues
4. **Notice**: Informational messages

### Recovery Strategies
- Graceful degradation
- Partial sync completion
- Detailed error logging
- Admin notifications

## Extension Points

### Hooks for Customization
- Filters for data modification
- Actions for custom logic
- Pluggable functions
- Template overrides

### API for Developers
- Public methods
- Helper functions
- Data structures
- Event system
