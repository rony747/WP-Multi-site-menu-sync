# Testing Guide: Avro Multisite Menu Sync

## 1. Testing Strategy

### 1.1 Test Pyramid
- **Unit Tests** (70%) - Individual functions and methods
- **Integration Tests** (20%) - Component interactions
- **Manual Tests** (10%) - UI and end-to-end workflows

### 1.2 Test Environment Setup

**Requirements**:
- WordPress multisite installation
- PHPUnit 9.x
- WordPress Test Suite
- At least 3 sites in network

**Setup Script**:
```bash
#!/bin/bash
# Install WordPress test suite
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest

# Install PHPUnit
composer require --dev phpunit/phpunit ^9.0

# Run tests
./vendor/bin/phpunit
```

## 2. Unit Testing

### 2.1 Test Bootstrap

**tests/bootstrap.php**:
```php
<?php
// Load WordPress test environment
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
    require dirname( dirname( __FILE__ ) ) . '/avro-multisite-menu-sync.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
```

### 2.2 Core Tests

**tests/test-core.php**:
```php
<?php
class Test_Menu_Sync_Core extends WP_UnitTestCase {
    
    public function setUp(): void {
        parent::setUp();
        $this->core = Menu_Sync_Core::get_instance();
    }
    
    public function test_singleton_instance() {
        $instance1 = Menu_Sync_Core::get_instance();
        $instance2 = Menu_Sync_Core::get_instance();
        $this->assertSame( $instance1, $instance2 );
    }
    
    public function test_plugin_version() {
        $version = $this->core->get_version();
        $this->assertNotEmpty( $version );
        $this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+$/', $version );
    }
    
    public function test_multisite_requirement() {
        $this->assertTrue( is_multisite() );
    }
}
```

### 2.3 Engine Tests

**tests/test-engine.php**:
```php
<?php
class Test_Menu_Sync_Engine extends WP_UnitTestCase {
    
    private $engine;
    private $source_site;
    private $target_site;
    
    public function setUp(): void {
        parent::setUp();
        $this->engine = new Menu_Sync_Engine();
        
        // Create test sites
        $this->source_site = $this->factory->blog->create();
        $this->target_site = $this->factory->blog->create();
    }
    
    public function test_extract_menu() {
        switch_to_blog( $this->source_site );
        
        // Create test menu
        $menu_id = wp_create_nav_menu( 'Test Menu' );
        wp_update_nav_menu_item( $menu_id, 0, array(
            'menu-item-title' => 'Home',
            'menu-item-url' => home_url( '/' ),
            'menu-item-status' => 'publish',
        ) );
        
        restore_current_blog();
        
        // Extract menu
        $menu_data = $this->engine->extract_menu( $menu_id );
        
        $this->assertIsArray( $menu_data );
        $this->assertEquals( 'Test Menu', $menu_data['menu_name'] );
        $this->assertNotEmpty( $menu_data['items'] );
    }
    
    public function test_apply_menu() {
        // Create menu data
        $menu_data = array(
            'menu_name' => 'Synced Menu',
            'menu_slug' => 'synced-menu',
            'items' => array(
                array(
                    'title' => 'Test Link',
                    'url' => 'https://example.com',
                    'type' => 'custom',
                    'position' => 1,
                ),
            ),
        );
        
        // Apply to target site
        $result = $this->engine->apply_menu( $menu_data, $this->target_site );
        
        $this->assertTrue( $result );
        
        // Verify menu exists on target
        switch_to_blog( $this->target_site );
        $menu = wp_get_nav_menu_object( 'Synced Menu' );
        $this->assertNotFalse( $menu );
        restore_current_blog();
    }
    
    public function test_object_id_mapping() {
        // Create page on source site
        switch_to_blog( $this->source_site );
        $source_page_id = $this->factory->post->create( array(
            'post_type' => 'page',
            'post_title' => 'Test Page',
            'post_name' => 'test-page',
        ) );
        restore_current_blog();
        
        // Create same page on target site
        switch_to_blog( $this->target_site );
        $target_page_id = $this->factory->post->create( array(
            'post_type' => 'page',
            'post_title' => 'Test Page',
            'post_name' => 'test-page',
        ) );
        restore_current_blog();
        
        // Test mapping
        $mapped_id = $this->engine->map_object_id(
            $source_page_id,
            'page',
            $this->source_site,
            $this->target_site
        );
        
        $this->assertEquals( $target_page_id, $mapped_id );
    }
    
    public function test_conflict_resolution_override() {
        switch_to_blog( $this->target_site );
        
        // Create existing menu
        $existing_menu_id = wp_create_nav_menu( 'Test Menu' );
        
        restore_current_blog();
        
        // New menu data
        $menu_data = array(
            'menu_name' => 'Test Menu',
            'menu_slug' => 'test-menu',
            'items' => array(),
        );
        
        // Resolve with override
        $result = $this->engine->resolve_conflict(
            'override',
            $existing_menu_id,
            $menu_data
        );
        
        $this->assertTrue( $result );
    }
}
```

### 2.4 Settings Tests

**tests/test-settings.php**:
```php
<?php
class Test_Menu_Sync_Settings extends WP_UnitTestCase {
    
    private $settings;
    
    public function setUp(): void {
        parent::setUp();
        $this->settings = new Menu_Sync_Settings();
    }
    
    public function test_get_default_settings() {
        $all = $this->settings->get_all();
        $this->assertIsArray( $all );
        $this->assertArrayHasKey( 'source_site_id', $all );
        $this->assertArrayHasKey( 'target_site_ids', $all );
    }
    
    public function test_update_setting() {
        $result = $this->settings->update( 'source_site_id', 2 );
        $this->assertTrue( $result );
        
        $value = $this->settings->get( 'source_site_id' );
        $this->assertEquals( 2, $value );
    }
    
    public function test_validate_settings() {
        $valid_settings = array(
            'source_site_id' => 1,
            'target_site_ids' => array( 2, 3 ),
            'sync_mode' => 'auto',
        );
        
        $result = $this->settings->validate( $valid_settings );
        $this->assertNotInstanceOf( 'WP_Error', $result );
        
        $invalid_settings = array(
            'source_site_id' => 'invalid',
            'sync_mode' => 'invalid_mode',
        );
        
        $result = $this->settings->validate( $invalid_settings );
        $this->assertInstanceOf( 'WP_Error', $result );
    }
}
```

### 2.5 Logger Tests

**tests/test-logger.php**:
```php
<?php
class Test_Menu_Sync_Logger extends WP_UnitTestCase {
    
    private $logger;
    
    public function setUp(): void {
        parent::setUp();
        $this->logger = new Menu_Sync_Logger();
    }
    
    public function test_log_entry() {
        $log_data = array(
            'source_site_id' => 1,
            'target_site_id' => 2,
            'menu_id' => 5,
            'menu_name' => 'Test Menu',
            'operation' => 'create',
            'status' => 'success',
            'message' => 'Menu synced successfully',
        );
        
        $log_id = $this->logger->log( $log_data );
        $this->assertIsInt( $log_id );
        $this->assertGreaterThan( 0, $log_id );
    }
    
    public function test_get_logs() {
        // Create test logs
        for ( $i = 0; $i < 5; $i++ ) {
            $this->logger->log( array(
                'source_site_id' => 1,
                'target_site_id' => 2,
                'menu_id' => $i,
                'status' => 'success',
            ) );
        }
        
        $logs = $this->logger->get_logs( array( 'limit' => 10 ) );
        $this->assertIsArray( $logs );
        $this->assertCount( 5, $logs );
    }
    
    public function test_cleanup_old_logs() {
        // Create old log
        $old_log_id = $this->logger->log( array(
            'source_site_id' => 1,
            'target_site_id' => 2,
            'status' => 'success',
        ) );
        
        // Manually update timestamp to 60 days ago
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'menu_sync_logs',
            array( 'timestamp' => date( 'Y-m-d H:i:s', strtotime( '-60 days' ) ) ),
            array( 'id' => $old_log_id )
        );
        
        $deleted = $this->logger->cleanup_old_logs( 30 );
        $this->assertEquals( 1, $deleted );
    }
}
```

## 3. Integration Testing

### 3.1 Full Sync Workflow Test

```php
<?php
class Test_Full_Sync_Workflow extends WP_UnitTestCase {
    
    public function test_complete_sync_workflow() {
        // Setup
        $source_site = $this->factory->blog->create();
        $target_site = $this->factory->blog->create();
        
        // Create menu on source
        switch_to_blog( $source_site );
        $menu_id = wp_create_nav_menu( 'Main Menu' );
        
        // Add menu items
        wp_update_nav_menu_item( $menu_id, 0, array(
            'menu-item-title' => 'Home',
            'menu-item-url' => home_url( '/' ),
            'menu-item-status' => 'publish',
        ) );
        
        restore_current_blog();
        
        // Configure settings
        $settings = new Menu_Sync_Settings();
        $settings->update( 'source_site_id', $source_site );
        $settings->update( 'target_site_ids', array( $target_site ) );
        
        // Perform sync
        $engine = new Menu_Sync_Engine();
        $result = $engine->sync_menu( $menu_id, array( $target_site ) );
        
        // Verify
        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'success', $result );
        
        // Check menu exists on target
        switch_to_blog( $target_site );
        $target_menu = wp_get_nav_menu_object( 'Main Menu' );
        $this->assertNotFalse( $target_menu );
        
        $items = wp_get_nav_menu_items( $target_menu->term_id );
        $this->assertNotEmpty( $items );
        
        restore_current_blog();
    }
}
```

## 4. Manual Testing Checklist

### 4.1 Installation & Activation
- [ ] Plugin activates without errors
- [ ] Multisite requirement check works
- [ ] Database tables created
- [ ] Default settings initialized
- [ ] Admin menu appears in Network Admin

### 4.2 Configuration
- [ ] Can select source site
- [ ] Can select multiple target sites
- [ ] Settings save correctly
- [ ] Validation works for invalid input
- [ ] Settings page displays correctly

### 4.3 Menu Synchronization
- [ ] Simple menu syncs correctly
- [ ] Nested menu items maintain hierarchy
- [ ] Custom links sync properly
- [ ] Page links map to correct pages
- [ ] Post links map to correct posts
- [ ] Category links map correctly
- [ ] Menu locations assign correctly

### 4.4 Conflict Resolution
- [ ] Override mode replaces existing menu
- [ ] Skip mode preserves existing menu
- [ ] Merge mode updates items correctly
- [ ] Conflicts logged properly

### 4.5 Error Handling
- [ ] Missing source menu handled gracefully
- [ ] Invalid site IDs rejected
- [ ] Missing target objects logged
- [ ] Database errors caught
- [ ] User sees appropriate error messages

### 4.6 Performance
- [ ] Syncs complete in reasonable time
- [ ] Large menus (100+ items) sync successfully
- [ ] Multiple sites (10+) sync successfully
- [ ] No timeout errors
- [ ] Memory usage acceptable

### 4.7 Logging
- [ ] Successful syncs logged
- [ ] Failed syncs logged
- [ ] Log entries contain all data
- [ ] Logs display correctly in admin
- [ ] Old logs cleanup works

### 4.8 Security
- [ ] Only super admins can access
- [ ] Nonces verified on all forms
- [ ] Input sanitized properly
- [ ] Output escaped correctly
- [ ] SQL injection prevented

## 5. Test Data Setup

### 5.1 Create Test Sites

```php
// Create 5 test sites
for ( $i = 1; $i <= 5; $i++ ) {
    wpmu_create_blog(
        'example.com',
        '/site' . $i,
        'Test Site ' . $i,
        1
    );
}
```

### 5.2 Create Test Menus

```php
function create_test_menu( $site_id ) {
    switch_to_blog( $site_id );
    
    $menu_id = wp_create_nav_menu( 'Test Menu' );
    
    // Add various item types
    wp_update_nav_menu_item( $menu_id, 0, array(
        'menu-item-title' => 'Home',
        'menu-item-url' => home_url( '/' ),
        'menu-item-status' => 'publish',
    ) );
    
    $page_id = wp_insert_post( array(
        'post_title' => 'About',
        'post_type' => 'page',
        'post_status' => 'publish',
    ) );
    
    wp_update_nav_menu_item( $menu_id, 0, array(
        'menu-item-title' => 'About',
        'menu-item-object' => 'page',
        'menu-item-object-id' => $page_id,
        'menu-item-type' => 'post_type',
        'menu-item-status' => 'publish',
    ) );
    
    restore_current_blog();
    
    return $menu_id;
}
```

## 6. Running Tests

### 6.1 Run All Tests

```bash
./vendor/bin/phpunit
```

### 6.2 Run Specific Test Class

```bash
./vendor/bin/phpunit tests/test-engine.php
```

### 6.3 Run With Coverage

```bash
./vendor/bin/phpunit --coverage-html coverage/
```

### 6.4 Run With Verbose Output

```bash
./vendor/bin/phpunit --verbose
```

## 7. Continuous Integration

### 7.1 GitHub Actions Workflow

**.github/workflows/tests.yml**:
```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:5.7
        env:
          MYSQL_ROOT_PASSWORD: root
        ports:
          - 3306:3306
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
          extensions: mysqli, mbstring
      
      - name: Install dependencies
        run: composer install
      
      - name: Install WordPress tests
        run: bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 latest
      
      - name: Run tests
        run: ./vendor/bin/phpunit
```

## 8. Test Coverage Goals

- **Overall**: 80%+
- **Core classes**: 90%+
- **Critical paths**: 100%
- **Edge cases**: Covered

## 9. Debugging Tests

### 9.1 Enable Debug Output

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

### 9.2 Use var_dump in Tests

```php
public function test_something() {
    $result = $this->engine->sync_menu( 1, array( 2 ) );
    var_dump( $result );
    $this->assertTrue( true );
}
```

### 9.3 Use PHPUnit Debugging

```bash
./vendor/bin/phpunit --debug
```
