# User Guide: Avro Multisite Menu Sync

## Introduction

Avro Multisite Menu Sync allows you to maintain consistent navigation menus across all sites in your WordPress multisite network. Update your menu once on the source site, and it automatically syncs to all target sites.

## Getting Started

### Step 1: Access Plugin Settings

1. Log in to **Network Admin**
2. Navigate to **Menu Sync** in the admin menu
3. Click on **Settings**

### Step 2: Configure Source Site

1. From the **Source Site** dropdown, select the site that will be your menu source
2. This is typically your main site (Site ID 1)
3. All menus will be synced FROM this site

### Step 3: Select Target Sites

1. Check the boxes next to sites that should receive menu updates
2. You can select multiple sites
3. Only checked sites will receive synced menus

### Step 4: Choose Sync Mode

**Auto Sync** (Recommended):
- Menus sync automatically when you save changes
- Best for keeping sites always in sync

**Manual Sync**:
- You control when menus sync
- Use the "Sync Now" button to trigger sync
- Best for testing or controlled updates

### Step 5: Set Conflict Resolution

**Override** (Recommended):
- Replaces existing menus on target sites
- Ensures complete consistency

**Skip**:
- Keeps existing menus, doesn't sync if menu exists
- Use when target sites have custom menus

**Merge**:
- Updates existing menu items
- Preserves items not in source menu

### Step 6: Save Settings

Click **Save Settings** to apply your configuration.

## Using the Plugin

### Automatic Sync (Auto Mode)

1. Go to your source site's admin
2. Navigate to **Appearance → Menus**
3. Edit your menu as normal
4. Click **Save Menu**
5. Plugin automatically syncs to target sites
6. Check the sync log to verify

### Manual Sync

1. Go to **Network Admin → Menu Sync → Dashboard**
2. View list of available menus
3. Click **Sync This Menu** for individual menu
4. Or click **Sync All Menus** to sync everything
5. Monitor progress in the sync log

### Viewing Sync Logs

1. Go to **Network Admin → Menu Sync → Logs**
2. View recent sync operations
3. Filter by:
   - Date range
   - Source/target site
   - Status (success/error)
   - Menu name
4. Click on log entry for details

## Understanding Menu Sync

### What Gets Synced

✅ **Synced**:
- Menu name and structure
- Menu items (pages, posts, custom links, categories)
- Item hierarchy (parent-child relationships)
- Item order
- Menu locations (if enabled)
- Custom CSS classes
- Link targets (_blank, etc.)
- Descriptions and titles

❌ **Not Synced**:
- Site-specific customizations (unless configured)
- Widget areas
- Theme-specific settings

### How Object Mapping Works

When syncing pages, posts, or categories:

1. Plugin finds matching content by slug
2. Uses target site's ID for the item
3. If no match found:
   - Converts to custom link (with warning)
   - Logs the issue

**Example**: 
- Source site has "About" page (ID: 10)
- Target site has "About" page (ID: 25)
- Synced menu uses ID 25 on target site

## Common Scenarios

### Scenario 1: New Multisite Setup

1. Create menus on main site
2. Configure plugin with all sites as targets
3. Run initial "Sync All Menus"
4. Enable auto sync for ongoing updates

### Scenario 2: Adding New Site

1. Create new site in network
2. Go to Menu Sync settings
3. Add new site to target sites list
4. Run manual sync to populate menus

### Scenario 3: Updating Main Menu

1. Edit menu on source site
2. Add/remove/reorder items
3. Save menu
4. Changes automatically sync (if auto mode)
5. Verify in sync logs

### Scenario 4: Site-Specific Menu

1. Use "Skip" conflict resolution
2. Create custom menu on specific site
3. That site keeps its custom menu
4. Other sites receive synced menu

## Troubleshooting

### Menus Not Syncing

**Check**:
- Plugin is network activated
- Source site is configured
- Target sites are selected
- Auto sync is enabled (if using auto mode)
- No PHP errors in debug log

**Solution**: Review sync logs for error messages

### Missing Menu Items

**Cause**: Referenced page/post doesn't exist on target site

**Solution**: 
- Create matching content on target sites
- Or accept custom link conversion
- Check sync log for details

### Wrong Menu Location

**Check**: "Sync Menu Locations" setting is enabled

**Solution**: 
- Enable location sync in settings
- Re-run sync
- Or manually assign menu locations on target sites

### Performance Issues

**Symptoms**: Slow sync, timeouts

**Solutions**:
- Reduce number of target sites per sync
- Increase PHP memory limit
- Use manual sync for large menus
- Contact hosting provider

## Best Practices

### 1. Plan Your Structure
- Decide on source site before starting
- Create consistent page structure across sites
- Use same slugs for matching content

### 2. Test First
- Start with manual sync mode
- Test on staging environment
- Verify results before enabling auto sync

### 3. Regular Monitoring
- Check sync logs weekly
- Address errors promptly
- Keep plugin updated

### 4. Backup Before Changes
- Backup database before major menu changes
- Test sync on non-production sites first
- Keep recent backup available

### 5. Document Custom Settings
- Note any site-specific customizations
- Document conflict resolution choices
- Keep settings documentation updated

## FAQ

**Q: Can I sync from multiple source sites?**
A: No, only one source site is supported. All target sites receive menus from this source.

**Q: Will this affect my existing menus?**
A: Depends on conflict resolution setting. "Override" replaces menus, "Skip" preserves them.

**Q: Can I exclude specific menus from sync?**
A: Currently, all menus from source site are synced. Use manual sync to control which menus sync.

**Q: What happens if I deactivate the plugin?**
A: Existing menus remain unchanged. Syncing stops. No data is deleted.

**Q: Can I sync to subsites only?**
A: Yes, select only subsites as targets, exclude main site if desired.

**Q: Does this work with custom menu items?**
A: Yes, custom links, categories, and custom post types are supported.

## Getting Help

- Review sync logs for error details
- Check documentation in `/docs/` folder
- Enable debug mode for detailed logging
- Contact support with log details

## Keyboard Shortcuts

- **Network Admin → Menu Sync**: Access plugin
- **Ctrl/Cmd + S**: Save settings (on settings page)
- **Esc**: Close modal dialogs
