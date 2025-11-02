# Deployment Guide: Avro Multisite Menu Sync

## 1. Pre-Deployment Checklist

- [ ] All tests passing
- [ ] Code follows WordPress standards
- [ ] Security audit completed
- [ ] Version numbers updated
- [ ] Documentation updated
- [ ] Assets minified

## 2. Build Process

```bash
# build.sh
VERSION="1.0.0"
rm -rf build/
mkdir -p build/avro-multisite-menu-sync
rsync -av --exclude-from='.buildignore' ./ build/avro-multisite-menu-sync/
cd build/avro-multisite-menu-sync
composer install --no-dev --optimize-autoloader
npm run build
cd ..
zip -r avro-multisite-menu-sync-${VERSION}.zip avro-multisite-menu-sync/
```

## 3. Installation Methods

### Manual Installation
1. Upload zip to `/wp-content/plugins/`
2. Extract files
3. Network activate from Network Admin
4. Configure settings

### WP-CLI Installation
```bash
wp plugin install /path/to/plugin.zip --network
wp plugin activate avro-multisite-menu-sync --network
```

### Git Deployment
```bash
cd /path/to/wp-content/plugins/
git clone https://github.com/yourusername/avro-multisite-menu-sync.git
cd avro-multisite-menu-sync
composer install --no-dev
npm run build
```

## 4. Configuration

### Via Admin
1. Network Admin → Menu Sync → Settings
2. Select source site
3. Select target sites
4. Choose sync mode
5. Save settings

### Via WP-CLI
```bash
wp option update avro_menu_sync_source_site 1 --network
wp option update avro_menu_sync_target_sites '[2,3,4]' --network --format=json
```

## 5. Post-Deployment

### Verification
```bash
wp plugin list --network | grep avro-multisite-menu-sync
wp db query "SHOW TABLES LIKE '%menu_sync%'"
```

### Initial Sync
1. Navigate to Network Admin → Menu Sync
2. Review source menus
3. Click "Sync All Menus"
4. Monitor logs

## 6. Backup & Rollback

### Backup
```bash
tar -czf backup-$(date +%Y%m%d).tar.gz avro-multisite-menu-sync
mysqldump database wp_menu_sync_logs > backup.sql
```

### Rollback
```bash
wp plugin deactivate avro-multisite-menu-sync --network
mv avro-multisite-menu-sync avro-multisite-menu-sync-backup
unzip previous-version.zip
wp plugin activate avro-multisite-menu-sync --network
```

## 7. Server Requirements

**Minimum**:
- PHP 7.4+, MySQL 5.6+
- 256MB memory, 30s execution time

**Recommended**:
- PHP 8.0+, MySQL 8.0+
- 512MB memory, 60s execution time
- Object caching enabled

## 8. Monitoring

```bash
# Check status
wp plugin status avro-multisite-menu-sync --network

# Check errors
wp db query "SELECT * FROM wp_menu_sync_logs WHERE status='error' LIMIT 10"

# Cleanup old logs
wp eval "Menu_Sync_Logger::cleanup_old_logs(30);"
```

## 9. Troubleshooting

**Plugin won't activate**: Check multisite enabled, PHP version, permissions

**Sync fails**: Verify sites exist, check error logs, increase memory limits

**Performance issues**: Enable caching, increase PHP limits, optimize database
