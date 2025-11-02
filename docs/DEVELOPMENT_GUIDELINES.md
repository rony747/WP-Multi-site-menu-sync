# Development Guidelines: Avro Multisite Menu Sync

## 1. Development Environment Setup

### 1.1 Prerequisites
- Local WordPress multisite installation
- PHP 7.4+ with extensions: mysqli, mbstring, json
- Composer for dependency management
- Node.js and npm for asset building
- Git for version control

### 1.2 Local Development Setup

```bash
# Clone or create plugin directory
cd wp-content/plugins/
mkdir avro-multisite-menu-sync
cd avro-multisite-menu-sync

# Initialize git repository
git init

# Install PHP dependencies (if using Composer)
composer install

# Install Node dependencies (if using npm)
npm install
```

### 1.3 WordPress Multisite Configuration

**wp-config.php additions**:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
define('SAVEQUERIES', true);

// Enable multisite
define('WP_ALLOW_MULTISITE', true);
define('MULTISITE', true);
define('SUBDOMAIN_INSTALL', false);
define('DOMAIN_CURRENT_SITE', 'localhost');
define('PATH_CURRENT_SITE', '/');
define('SITE_ID_CURRENT_SITE', 1);
define('BLOG_ID_CURRENT_SITE', 1);
```

## 2. Coding Standards

### 2.1 WordPress Coding Standards

Follow the [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)

**Key Points**:
- Use tabs for indentation
- Use single quotes for strings (unless interpolation needed)
- Yoda conditions for comparisons
- Space after control structures
- Proper DocBlock comments

### 2.2 Naming Conventions

**Files**:
```
class-menu-sync-core.php          // Class files
admin-dashboard.php               // Template files
admin.css                         // Asset files
```

**Classes**:
```php
class Menu_Sync_Core {}           // PascalCase with underscores
```

**Functions**:
```php
function avro_menu_sync_init() {} // Prefix + snake_case
```

**Hooks**:
```php
do_action('avro_menu_sync_before_sync', $data);
apply_filters('avro_menu_sync_menu_item', $item);
```

**Variables**:
```php
$source_site_id                   // snake_case
$targetSites                      // camelCase acceptable
```

### 2.3 Code Organization

**Class Structure**:
```php
<?php
/**
 * Class description
 *
 * @package Avro_Multisite_Menu_Sync
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
     * Get singleton instance
     *
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
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Initialization code
    }
    
    // Public methods
    
    // Protected methods
    
    // Private methods
}
```

### 2.4 Documentation Standards

**File Headers**:
```php
<?php
/**
 * Menu Sync Core Class
 *
 * Handles core plugin functionality and initialization.
 *
 * @package Avro_Multisite_Menu_Sync
 * @subpackage Core
 * @since 1.0.0
 * @version 1.0.0
 */
```

**Function Documentation**:
```php
/**
 * Synchronize menu to target sites
 *
 * Takes a menu from the source site and replicates it to all
 * configured target sites in the network.
 *
 * @since 1.0.0
 *
 * @param int   $menu_id        Menu ID to synchronize.
 * @param array $target_sites   Array of target site IDs.
 * @param array $options        Sync options.
 * @return array|WP_Error       Sync results or error object.
 */
public function sync_menu( $menu_id, $target_sites, $options = array() ) {
    // Function code
}
```

## 3. Plugin Structure

### 3.1 Directory Structure

```
avro-multisite-menu-sync/
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── admin.min.css
│   ├── js/
│   │   ├── admin.js
│   │   └── admin.min.js
│   └── images/
│       └── icon.png
├── includes/
│   ├── class-menu-sync-core.php
│   ├── class-menu-sync-admin.php
│   ├── class-menu-sync-engine.php
│   ├── class-menu-sync-logger.php
│   ├── class-menu-sync-settings.php
│   └── class-menu-sync-ajax.php
├── templates/
│   ├── admin-dashboard.php
│   ├── admin-settings.php
│   ├── admin-logs.php
│   └── partials/
│       ├── site-selector.php
│       └── sync-status.php
├── languages/
│   └── avro-multisite-menu-sync.pot
├── docs/
│   ├── TECHNICAL_SPEC.md
│   ├── DEVELOPMENT_GUIDELINES.md
│   ├── API_REFERENCE.md
│   ├── TESTING_GUIDE.md
│   └── USER_GUIDE.md
├── tests/
│   ├── bootstrap.php
│   ├── test-core.php
│   ├── test-engine.php
│   └── test-logger.php
├── avro-multisite-menu-sync.php
├── uninstall.php
├── composer.json
├── package.json
├── .gitignore
├── README.md
└── LICENSE
```

### 3.2 Main Plugin File

**avro-multisite-menu-sync.php**:
```php
<?php
/**
 * Plugin Name: Avro Multisite Menu Sync
 * Plugin URI: https://example.com/avro-multisite-menu-sync
 * Description: Synchronize navigation menus across WordPress multisite network
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: avro-multisite-menu-sync
 * Domain Path: /languages
 * Network: true
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package Avro_Multisite_Menu_Sync
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'AVRO_MENU_SYNC_VERSION', '1.0.0' );
define( 'AVRO_MENU_SYNC_PLUGIN_FILE', __FILE__ );
define( 'AVRO_MENU_SYNC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AVRO_MENU_SYNC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AVRO_MENU_SYNC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Require dependencies
require_once AVRO_MENU_SYNC_PLUGIN_DIR . 'includes/class-menu-sync-core.php';

// Initialize plugin
function avro_menu_sync_init() {
    // Check if multisite
    if ( ! is_multisite() ) {
        add_action( 'admin_notices', 'avro_menu_sync_multisite_notice' );
        return;
    }
    
    // Initialize core
    Menu_Sync_Core::get_instance();
}
add_action( 'plugins_loaded', 'avro_menu_sync_init' );

/**
 * Display multisite requirement notice
 */
function avro_menu_sync_multisite_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e( 'Avro Multisite Menu Sync requires WordPress Multisite to be enabled.', 'avro-multisite-menu-sync' ); ?></p>
    </div>
    <?php
}

/**
 * Activation hook
 */
function avro_menu_sync_activate() {
    if ( ! is_multisite() ) {
        wp_die( esc_html__( 'This plugin requires WordPress Multisite.', 'avro-multisite-menu-sync' ) );
    }
    
    // Create database tables
    require_once AVRO_MENU_SYNC_PLUGIN_DIR . 'includes/class-menu-sync-core.php';
    Menu_Sync_Core::activate();
}
register_activation_hook( __FILE__, 'avro_menu_sync_activate' );

/**
 * Deactivation hook
 */
function avro_menu_sync_deactivate() {
    // Cleanup tasks
    require_once AVRO_MENU_SYNC_PLUGIN_DIR . 'includes/class-menu-sync-core.php';
    Menu_Sync_Core::deactivate();
}
register_deactivation_hook( __FILE__, 'avro_menu_sync_deactivate' );
```

## 4. Development Workflow

### 4.1 Git Workflow

**Branch Strategy**:
- `main` - Production-ready code
- `develop` - Development branch
- `feature/*` - Feature branches
- `bugfix/*` - Bug fix branches
- `release/*` - Release preparation

**Commit Messages**:
```
feat: Add menu synchronization engine
fix: Resolve object ID mapping issue
docs: Update API reference
style: Format code according to WPCS
refactor: Optimize sync performance
test: Add unit tests for logger class
```

### 4.2 Development Process

1. **Create Feature Branch**
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feature/menu-extraction
   ```

2. **Develop and Test**
   - Write code following standards
   - Add inline documentation
   - Create/update tests
   - Test in local multisite environment

3. **Commit Changes**
   ```bash
   git add .
   git commit -m "feat: Implement menu extraction logic"
   ```

4. **Create Pull Request**
   - Push to remote
   - Create PR to `develop`
   - Request code review

5. **Merge and Deploy**
   - Merge approved PR
   - Tag releases on `main`

### 4.3 Code Review Checklist

- [ ] Follows WordPress coding standards
- [ ] Proper sanitization and escaping
- [ ] Security checks (nonces, capabilities)
- [ ] Error handling implemented
- [ ] Documentation complete
- [ ] Tests pass
- [ ] No PHP errors or warnings
- [ ] Compatible with multisite
- [ ] Performance optimized

## 5. Security Best Practices

### 5.1 Input Validation

```php
// Sanitize text input
$menu_name = sanitize_text_field( $_POST['menu_name'] );

// Validate integers
$site_id = absint( $_POST['site_id'] );

// Validate arrays
$site_ids = array_map( 'absint', (array) $_POST['site_ids'] );

// Sanitize URLs
$url = esc_url_raw( $_POST['url'] );

// Validate against whitelist
$sync_mode = in_array( $_POST['sync_mode'], array( 'auto', 'manual' ), true ) 
    ? $_POST['sync_mode'] 
    : 'manual';
```

### 5.2 Output Escaping

```php
// Escape HTML
echo esc_html( $menu_name );

// Escape attributes
echo '<div class="' . esc_attr( $class ) . '">';

// Escape URLs
echo '<a href="' . esc_url( $url ) . '">';

// Escape JavaScript
echo '<script>var data = ' . wp_json_encode( $data ) . ';</script>';

// Escape for textarea
echo '<textarea>' . esc_textarea( $content ) . '</textarea>';
```

### 5.3 Nonce Verification

```php
// Create nonce
wp_nonce_field( 'avro_menu_sync_settings', 'avro_menu_sync_nonce' );

// Verify nonce
if ( ! isset( $_POST['avro_menu_sync_nonce'] ) 
    || ! wp_verify_nonce( $_POST['avro_menu_sync_nonce'], 'avro_menu_sync_settings' ) ) {
    wp_die( esc_html__( 'Security check failed.', 'avro-multisite-menu-sync' ) );
}
```

### 5.4 Capability Checks

```php
// Check super admin
if ( ! is_super_admin() ) {
    wp_die( esc_html__( 'Insufficient permissions.', 'avro-multisite-menu-sync' ) );
}

// Check specific capability
if ( ! current_user_can( 'manage_network_options' ) ) {
    wp_die( esc_html__( 'Insufficient permissions.', 'avro-multisite-menu-sync' ) );
}
```

### 5.5 Database Queries

```php
global $wpdb;

// Use prepare for queries
$results = $wpdb->get_results( 
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}menu_sync_logs WHERE source_site_id = %d",
        $site_id
    )
);

// Use placeholders
$wpdb->insert(
    $wpdb->prefix . 'menu_sync_logs',
    array(
        'source_site_id' => $source_id,
        'menu_name' => $menu_name,
    ),
    array( '%d', '%s' )
);
```

## 6. Performance Optimization

### 6.1 Caching Strategies

```php
// Use transients
$menus = get_transient( 'avro_menu_sync_source_menus' );
if ( false === $menus ) {
    $menus = $this->get_source_menus();
    set_transient( 'avro_menu_sync_source_menus', $menus, HOUR_IN_SECONDS );
}

// Object caching
wp_cache_set( 'menu_' . $menu_id, $menu_data, 'avro_menu_sync' );
$menu_data = wp_cache_get( 'menu_' . $menu_id, 'avro_menu_sync' );

// Clear cache on update
delete_transient( 'avro_menu_sync_source_menus' );
wp_cache_delete( 'menu_' . $menu_id, 'avro_menu_sync' );
```

### 6.2 Batch Processing

```php
// Process in batches
$batch_size = 10;
$offset = 0;

while ( $sites = array_slice( $target_sites, $offset, $batch_size ) ) {
    foreach ( $sites as $site_id ) {
        $this->sync_to_site( $menu_id, $site_id );
    }
    $offset += $batch_size;
    
    // Prevent timeout
    set_time_limit( 30 );
}
```

### 6.3 Query Optimization

```php
// Minimize database queries
$menu_items = wp_get_nav_menu_items( $menu_id );

// Use WP_Query efficiently
$query = new WP_Query( array(
    'post_type' => 'page',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'no_found_rows' => true,
    'update_post_meta_cache' => false,
    'update_post_term_cache' => false,
) );
```

## 7. Internationalization (i18n)

### 7.1 Text Domain

```php
// Load text domain
function avro_menu_sync_load_textdomain() {
    load_plugin_textdomain(
        'avro-multisite-menu-sync',
        false,
        dirname( AVRO_MENU_SYNC_PLUGIN_BASENAME ) . '/languages'
    );
}
add_action( 'plugins_loaded', 'avro_menu_sync_load_textdomain' );
```

### 7.2 Translatable Strings

```php
// Simple string
__( 'Menu synchronized successfully.', 'avro-multisite-menu-sync' );

// Echo string
esc_html_e( 'Sync Settings', 'avro-multisite-menu-sync' );

// With variables
sprintf(
    /* translators: %d: number of sites */
    __( 'Synced to %d sites.', 'avro-multisite-menu-sync' ),
    count( $target_sites )
);

// Plural forms
sprintf(
    /* translators: %d: number of items */
    _n(
        '%d menu item synced.',
        '%d menu items synced.',
        $count,
        'avro-multisite-menu-sync'
    ),
    $count
);
```

### 7.3 Generate POT File

```bash
# Using WP-CLI
wp i18n make-pot . languages/avro-multisite-menu-sync.pot

# Or use Poedit or other tools
```

## 8. Debugging and Logging

### 8.1 Debug Logging

```php
// Custom debug function
function avro_menu_sync_log( $message, $data = array() ) {
    if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
        return;
    }
    
    $log_entry = sprintf(
        '[%s] %s',
        current_time( 'mysql' ),
        $message
    );
    
    if ( ! empty( $data ) ) {
        $log_entry .= ' | Data: ' . wp_json_encode( $data );
    }
    
    error_log( $log_entry );
}

// Usage
avro_menu_sync_log( 'Starting menu sync', array(
    'menu_id' => $menu_id,
    'target_sites' => $target_sites,
) );
```

### 8.2 Error Handling

```php
// Try-catch for critical operations
try {
    $result = $this->sync_menu( $menu_id, $target_sites );
} catch ( Exception $e ) {
    avro_menu_sync_log( 'Sync error: ' . $e->getMessage() );
    return new WP_Error( 'sync_failed', $e->getMessage() );
}

// Check for WP_Error
$result = $this->some_operation();
if ( is_wp_error( $result ) ) {
    avro_menu_sync_log( 'Operation failed: ' . $result->get_error_message() );
    return $result;
}
```

## 9. Asset Management

### 9.1 Enqueue Scripts and Styles

```php
/**
 * Enqueue admin assets
 */
public function enqueue_admin_assets( $hook ) {
    // Only on plugin pages
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
    
    // Localize script
    wp_localize_script(
        'avro-menu-sync-admin',
        'avroMenuSync',
        array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'avro_menu_sync_ajax' ),
            'strings' => array(
                'confirm' => __( 'Are you sure?', 'avro-multisite-menu-sync' ),
                'success' => __( 'Operation completed.', 'avro-multisite-menu-sync' ),
            ),
        )
    );
}
add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
```

## 10. Version Control

### 10.1 .gitignore

```
# WordPress
wp-config.php
wp-content/uploads/
wp-content/cache/

# Plugin specific
*.log
.DS_Store
Thumbs.db

# Dependencies
/vendor/
/node_modules/

# Build files
*.min.css
*.min.js
*.map

# IDE
.idea/
.vscode/
*.sublime-*

# OS
.DS_Store
Thumbs.db
```

### 10.2 Version Bumping

Update version in:
1. Main plugin file header
2. Plugin constants
3. README.md
4. package.json
5. composer.json

## 11. Code Quality Tools

### 11.1 PHP CodeSniffer

```bash
# Install WPCS
composer require --dev wp-coding-standards/wpcs

# Run phpcs
./vendor/bin/phpcs --standard=WordPress includes/

# Fix automatically
./vendor/bin/phpcbf --standard=WordPress includes/
```

### 11.2 PHPStan

```bash
# Install PHPStan
composer require --dev phpstan/phpstan

# Run analysis
./vendor/bin/phpstan analyse includes/
```

## 12. Best Practices Summary

✅ **DO**:
- Follow WordPress coding standards
- Sanitize input, escape output
- Use nonces and capability checks
- Write comprehensive documentation
- Create unit and integration tests
- Use meaningful variable names
- Handle errors gracefully
- Optimize database queries
- Cache when appropriate
- Make code translatable

❌ **DON'T**:
- Use deprecated functions
- Hardcode values
- Ignore security best practices
- Skip error handling
- Leave debug code in production
- Use global variables unnecessarily
- Make direct database queries without $wpdb
- Forget to switch back after switch_to_blog()
- Ignore performance implications
- Skip code reviews
