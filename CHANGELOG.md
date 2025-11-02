# Changelog

All notable changes to Avro Multisite Menu Sync will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned Features
- WP-CLI commands for sync operations
- REST API endpoints
- Scheduled sync operations
- Menu templates
- Import/export functionality
- Bidirectional sync
- Selective menu item sync

## [1.0.0] - 2024-01-01

### Added
- Initial release
- Core menu synchronization functionality
- Network admin interface with dashboard, settings, and logs pages
- Auto and manual sync modes
- Conflict resolution strategies (override, skip, merge)
- Object ID mapping for posts, pages, and categories
- Menu location synchronization
- Comprehensive logging system
- Sync statistics and reporting
- Security features (nonces, capability checks, input sanitization)
- Internationalization support
- WordPress hooks and filters for extensibility
- Admin notices for sync operations
- AJAX-powered sync operations

### Features
- Sync menus from source site to multiple target sites
- Preserve menu hierarchy and structure
- Support for all menu item types (pages, posts, custom links, categories, tags)
- Custom field preservation options
- Detailed sync logs with filtering
- Network-wide settings management
- Batch processing for large networks
- Error handling and recovery
- Performance optimization with caching

### Security
- Network admin capability requirements
- Nonce verification on all forms
- Input sanitization and output escaping
- SQL injection prevention with prepared statements
- XSS protection

### Documentation
- Complete technical specification
- Development guidelines
- API reference
- Testing guide
- Deployment guide
- User guide
- Architecture documentation

### Developer Features
- Extensible hook system
- Public API for custom integrations
- Well-documented code
- Unit test framework
- PHPUnit test suite
- WordPress coding standards compliance

## Version History

### Version Numbering
- **Major version** (X.0.0): Breaking changes, major features
- **Minor version** (1.X.0): New features, backwards compatible
- **Patch version** (1.0.X): Bug fixes, minor improvements

### Upgrade Notes

#### From 0.x to 1.0.0
- First stable release
- No upgrade path from beta versions
- Fresh installation recommended

## Future Roadmap

### Version 1.1.0 (Planned)
- WP-CLI integration
- Bulk operations improvements
- Enhanced error reporting
- Performance optimizations

### Version 1.2.0 (Planned)
- REST API endpoints
- Webhook support
- Advanced filtering options
- Menu diff viewer

### Version 2.0.0 (Future)
- Bidirectional sync
- Menu versioning
- Rollback functionality
- Advanced conflict resolution

## Support

For bug reports and feature requests, please refer to the documentation or contact support.

## Links

- [Documentation](docs/)
- [GitHub Repository](#)
- [Support Forum](#)
- [Website](#)
