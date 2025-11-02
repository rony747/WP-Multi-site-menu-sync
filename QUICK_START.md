# Quick Start Guide: Avro Multisite Menu Sync

## For Developers

### 1. Clone and Setup (5 minutes)

```bash
# Navigate to plugins directory
cd /path/to/wp-content/plugins/

# Clone repository (or create directory)
mkdir avro-multisite-menu-sync
cd avro-multisite-menu-sync

# Initialize Git
git init

# Install dependencies
composer install
npm install

# Copy documentation structure
# All docs are in /docs/ folder
```

### 2. Development Environment (10 minutes)

**Requirements**:
- Local WordPress multisite installation
- PHP 7.4+ with mysqli extension
- MySQL 5.6+
- Composer
- Node.js & npm

**Setup WordPress Multisite**:

Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('MULTISITE', true);
define('SUBDOMAIN_INSTALL', false);
define('DOMAIN_CURRENT_SITE', 'localhost');
define('PATH_CURRENT_SITE', '/');
define('SITE_ID_CURRENT_SITE', 1);
define('BLOG_ID_CURRENT_SITE', 1);
```

### 3. Start Development (15 minutes)

**Step 1: Create Main Plugin File**

Create `avro-multisite-menu-sync.php`:
```php
<?php
/**
 * Plugin Name: Avro Multisite Menu Sync
 * Description: Synchronize menus across multisite network
 * Version: 1.0.0
 * Network: true
 */

if (!defined('ABSPATH')) exit;

define('AVRO_MENU_SYNC_VERSION', '1.0.0');
define('AVRO_MENU_SYNC_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once AVRO_MENU_SYNC_PLUGIN_DIR . 'includes/class-menu-sync-core.php';

add_action('plugins_loaded', function() {
    if (!is_multisite()) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>Requires WordPress Multisite</p></div>';
        });
        return;
    }
    Menu_Sync_Core::get_instance();
});
```

**Step 2: Create Core Class**

Create `includes/class-menu-sync-core.php`:
```php
<?php
class Menu_Sync_Core {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        // Initialize plugin
        add_action('network_admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Menu Sync',
            'Menu Sync',
            'manage_network_options',
            'menu-sync',
            array($this, 'render_dashboard'),
            'dashicons-update'
        );
    }
    
    public function render_dashboard() {
        echo '<div class="wrap">';
        echo '<h1>Menu Sync Dashboard</h1>';
        echo '<p>Plugin is active!</p>';
        echo '</div>';
    }
}
```

**Step 3: Activate Plugin**

1. Go to Network Admin → Plugins
2. Network Activate "Avro Multisite Menu Sync"
3. Go to Network Admin → Menu Sync
4. Verify dashboard appears

### 4. Development Workflow

**Daily Workflow**:
```bash
# Start development
cd avro-multisite-menu-sync

# Create feature branch
git checkout -b feature/menu-extraction

# Make changes
# ... edit files ...

# Test changes
./vendor/bin/phpunit

# Check code standards
./vendor/bin/phpcs includes/

# Commit
git add .
git commit -m "feat: implement menu extraction"

# Build assets
npm run build
```

### 5. Testing Your Code

**Run Tests**:
```bash
# All tests
./vendor/bin/phpunit

# Specific test
./vendor/bin/phpunit tests/test-core.php

# With coverage
./vendor/bin/phpunit --coverage-html coverage/
```

**Manual Testing**:
1. Create test menu on Site 1
2. Add various menu items
3. Test sync to Site 2
4. Verify menu appears correctly
5. Check logs for errors

## For Users

### 1. Installation (2 minutes)

**Option A: Upload Plugin**
1. Download plugin zip file
2. Go to Network Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose zip file
5. Click "Install Now"
6. Click "Network Activate"

**Option B: Manual Upload**
1. Upload folder to `/wp-content/plugins/`
2. Go to Network Admin → Plugins
3. Find "Avro Multisite Menu Sync"
4. Click "Network Activate"

### 2. Configuration (3 minutes)

1. Go to **Network Admin → Menu Sync → Settings**

2. **Select Source Site**:
   - Choose site that has your master menus
   - Usually Site ID 1 (main site)

3. **Select Target Sites**:
   - Check boxes for sites that should receive menus
   - Can select multiple sites

4. **Choose Sync Mode**:
   - **Auto**: Menus sync automatically when saved
   - **Manual**: You control when to sync

5. **Set Conflict Resolution**:
   - **Override**: Replace existing menus (recommended)
   - **Skip**: Keep existing menus
   - **Merge**: Update items, keep extras

6. Click **Save Settings**

### 3. First Sync (2 minutes)

**Initial Setup**:
1. Go to **Network Admin → Menu Sync → Dashboard**
2. Review list of menus from source site
3. Click **"Sync All Menus"** button
4. Wait for sync to complete
5. Check **Logs** tab to verify success

**Verify Results**:
1. Go to any target site admin
2. Navigate to **Appearance → Menus**
3. Verify menus appear
4. Check menu items are correct

### 4. Daily Use

**Auto Mode** (Recommended):
1. Edit menus on source site as normal
2. Click "Save Menu"
3. Changes automatically sync
4. Check logs if needed

**Manual Mode**:
1. Edit menus on source site
2. Save changes
3. Go to Network Admin → Menu Sync
4. Click "Sync Now" for specific menu
5. Or click "Sync All" for all menus

## Common Tasks

### Add New Menu
1. Create menu on source site
2. Add menu items
3. Save menu
4. Sync automatically (auto mode) or manually

### Update Existing Menu
1. Edit menu on source site
2. Add/remove/reorder items
3. Save menu
4. Changes sync to targets

### Add New Site to Network
1. Create new site in network
2. Go to Menu Sync settings
3. Check box for new site
4. Save settings
5. Run manual sync

### Troubleshoot Sync Issues
1. Go to Menu Sync → Logs
2. Find failed sync entry
3. Read error message
4. Common fixes:
   - Ensure target site exists
   - Check referenced pages exist
   - Verify permissions
   - Review error log

## Next Steps

### For Developers
- Read [Technical Specification](docs/TECHNICAL_SPEC.md)
- Review [Development Guidelines](docs/DEVELOPMENT_GUIDELINES.md)
- Check [API Reference](docs/API_REFERENCE.md)
- Follow [Development Roadmap](docs/DEVELOPMENT_ROADMAP.md)

### For Users
- Read [User Guide](docs/USER_GUIDE.md)
- Review [FAQ section](docs/USER_GUIDE.md#faq)
- Check sync logs regularly
- Report issues with log details

## Getting Help

### Documentation
- `/docs/` folder has complete documentation
- README.md for overview
- Each doc covers specific topic

### Support
- Check logs for error details
- Review troubleshooting section
- Enable debug mode for details
- Contact support with logs

## Tips & Tricks

### Development Tips
- Use `WP_DEBUG` for development
- Test on fresh multisite install
- Create test sites for experimentation
- Use version control (Git)
- Follow WordPress coding standards

### Usage Tips
- Start with manual sync mode
- Test on staging first
- Backup before major changes
- Monitor logs regularly
- Keep plugin updated

### Performance Tips
- Limit number of target sites
- Use object caching
- Increase PHP memory limit
- Optimize database
- Run manual sync during off-peak hours

## Quick Reference

### File Structure
```
avro-multisite-menu-sync/
├── avro-multisite-menu-sync.php  # Main file
├── includes/                      # PHP classes
├── assets/                        # CSS/JS
├── templates/                     # Admin pages
├── docs/                          # Documentation
└── tests/                         # Unit tests
```

### Key Classes
- `Menu_Sync_Core` - Main plugin class
- `Menu_Sync_Engine` - Sync logic
- `Menu_Sync_Admin` - Admin interface
- `Menu_Sync_Settings` - Configuration
- `Menu_Sync_Logger` - Logging

### Important Hooks
- `avro_menu_sync_before_sync` - Before sync
- `avro_menu_sync_after_sync` - After sync
- `avro_menu_sync_menu_item` - Modify item
- `avro_menu_sync_source_menu` - Modify source

### WP-CLI Commands (Future)
```bash
wp menu-sync status
wp menu-sync sync --menu=5
wp menu-sync sync --all
wp menu-sync logs --limit=10
```

## Checklist

### Development Setup
- [ ] WordPress multisite installed
- [ ] Plugin directory created
- [ ] Dependencies installed
- [ ] Git initialized
- [ ] Main plugin file created
- [ ] Core class created
- [ ] Plugin activates successfully

### First Deployment
- [ ] Code tested locally
- [ ] Tests passing
- [ ] Documentation complete
- [ ] Version numbers updated
- [ ] Plugin packaged
- [ ] Uploaded to server
- [ ] Network activated
- [ ] Settings configured
- [ ] Initial sync completed
- [ ] Results verified

### Daily Development
- [ ] Feature branch created
- [ ] Code written
- [ ] Tests added
- [ ] Code standards checked
- [ ] Changes committed
- [ ] Pull request created
- [ ] Code reviewed
- [ ] Merged to develop
