# Avro Multisite Menu Sync

A WordPress multisite plugin that synchronizes navigation menus across multiple sites in a network.

## Overview

Avro Multisite Menu Sync enables network administrators to maintain consistent navigation menus across all sites in a WordPress multisite network. Define menus once on a source site and automatically or manually sync them to target sites.

## Features

- **Flexible Sync Modes**: Choose between automatic sync on menu save or manual sync on demand
- **Smart Object Mapping**: Automatically maps menu items (posts, pages, categories) by slug across sites
- **Conflict Resolution**: Multiple strategies (override, skip, merge) for handling existing menus
- **Menu Location Sync**: Optionally sync menu location assignments
- **Comprehensive Logging**: Track all sync operations with detailed audit trail
- **Network Admin Interface**: Easy-to-use dashboard for configuration and monitoring
- **Security First**: Built with WordPress security best practices
- **Extensible**: Hooks and filters for developers to customize behavior

## Requirements

- WordPress 5.8 or higher
- WordPress Multisite enabled
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Installation

1. Upload the plugin files to `/wp-content/plugins/avro-multisite-menu-sync/`
2. Network activate the plugin through the 'Plugins' menu in WordPress Network Admin
3. Configure settings in Network Admin → Menu Sync → Settings

## Quick Start

1. **Configure Settings**
   - Go to Network Admin → Menu Sync → Settings
   - Select your source site (where you maintain master menus)
   - Choose target sites to receive synced menus
   - Set sync mode (auto or manual)
   - Choose conflict resolution strategy
   - Save settings

2. **Sync Menus**
   - Go to Network Admin → Menu Sync → Dashboard
   - Click "Sync Now" on individual menus or "Sync All Menus"
   - Monitor progress and check logs

3. **Monitor Operations**
   - View sync history in Network Admin → Menu Sync → Logs
   - Check statistics and recent activity on dashboard

## Configuration

### Sync Modes

- **Automatic**: Menus sync automatically when saved on source site
- **Manual**: Menus sync only when triggered from dashboard

### Conflict Resolution

- **Override**: Replace existing menus completely
- **Skip**: Keep existing menus, don't sync if menu exists
- **Merge**: Update existing items, preserve items not in source

### Additional Options

- **Sync menu locations**: Assign menus to same theme locations as source
- **Preserve custom fields**: Keep custom menu item fields on target sites

## How It Works

1. Plugin extracts menu structure from source site
2. Maps menu items by slug to find matching content on target sites
3. Creates or updates menus on target sites based on conflict resolution
4. Optionally assigns menus to theme locations
5. Logs all operations for audit trail

## Object Mapping

The plugin intelligently maps menu items across sites:

- **Posts/Pages**: Matched by post slug
- **Categories/Terms**: Matched by term slug
- **Custom Links**: Copied as-is
- **Not Found**: Converted to custom links with original URL

## Uninstallation

When you uninstall the plugin:
- All plugin settings are removed
- Custom database table is dropped
- Synced menus remain on target sites (not deleted)

## Documentation

Comprehensive documentation is available in the `/docs/` directory:

- **ARCHITECTURE.md**: Technical architecture and design
- **API.md**: Hooks, filters, and developer reference
- **TESTING.md**: Testing guidelines and procedures
- **USER_GUIDE.md**: Detailed user instructions
- **DEVELOPMENT.md**: Development workflow and standards

## Security

The plugin implements WordPress security best practices:

- Capability checks (`manage_network_options`)
- Nonce verification on all forms
- Input sanitization and validation
- Output escaping
- SQL injection prevention with prepared statements
- XSS protection
- CSRF protection

## License

This plugin is licensed under the GPL v2 or later.
