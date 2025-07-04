# FearlessCMS File Permissions Guide

## Overview
This document outlines the recommended file permissions for FearlessCMS to function properly without permission errors.

## Permission Requirements

### Directories
All directories should have **777** permissions to allow:
- File creation and deletion
- Directory traversal
- Plugin operations (creating forms, uploads, etc.)

```bash
find /path/to/fearlesscms -type d -exec chmod 777 {} \;
```

### Files
All files should have **666** permissions to allow:
- Reading and writing by web server
- Log file creation and updates
- Configuration file modifications
- Content file editing

```bash
find /path/to/fearlesscms -type f -exec chmod 666 {} \;
```

## Critical Files and Directories

### Log Files
- `debug.log` - 666
- `content/forms/forms.log` - 666
- `error.log` - 666

### Configuration Files
- `config/users.json` - 666
- `config/roles.json` - 666
- `config/config.json` - 666
- `config/menus.json` - 666
- `config/widgets.json` - 666
- `config/plugins.json` - 666
- `config/active_plugins.json` - 666

### SEO Files
- `sitemap.xml` - 666
- `robots.txt` - 666

### Content Directory
- `content/` - 777 (directory)
- `content/_preview/` - 777 (directory)
- `content/forms/` - 777 (directory)
- All `.md` files in content - 666

### Upload Directories
- `uploads/` - 777 (directory)
- `admin/uploads/` - 777 (directory)

### Plugin Directories
- `plugins/` - 777 (directory)
- All plugin subdirectories - 777

### Theme Directories
- `themes/` - 777 (directory)
- All theme subdirectories - 777

## Quick Fix Commands

### For Development/Testing (Very Permissive)
```bash
# Set all directories to 777
find /path/to/fearlesscms -type d -exec chmod 777 {} \;

# Set all files to 666
find /path/to/fearlesscms -type f -exec chmod 666 {} \;
```

### For Production (More Secure)
```bash
# Set ownership to web server user
chown -R www-data:www-data /path/to/fearlesscms

# Set directories to 755
find /path/to/fearlesscms -type d -exec chmod 755 {} \;

# Set files to 644
find /path/to/fearlesscms -type f -exec chmod 644 {} \;

# Set specific writable files to 664
chmod 664 /path/to/fearlesscms/debug.log
chmod 664 /path/to/fearlesscms/sitemap.xml
chmod 664 /path/to/fearlesscms/robots.txt
chmod 664 /path/to/fearlesscms/config/users.json
chmod 664 /path/to/fearlesscms/content/forms/forms.log
```

## Common Permission Errors

### File Creation Errors
- **Error**: `mkdir(): Permission denied`
- **Solution**: Set directory permissions to 777 or ensure web server user owns the directory

### File Writing Errors
- **Error**: `file_put_contents(): Failed to open stream: Permission denied`
- **Solution**: Set file permissions to 666 or ensure web server user owns the file

### Directory Traversal Errors
- **Error**: `RecursiveDirectoryIterator::__construct(): Failed to open directory: Permission denied`
- **Solution**: Set directory permissions to 777 or ensure web server user can read the directory

## Security Considerations

### Development Environment
- Using 777/666 permissions is acceptable for development
- Allows maximum flexibility for testing and debugging

### Production Environment
- Use more restrictive permissions (755/644) when possible
- Set ownership to web server user (www-data, apache, nginx, etc.)
- Only set 664 permissions on files that need to be writable
- Consider using ACLs for more granular permission control

## Troubleshooting

### Check Current Permissions
```bash
ls -la /path/to/fearlesscms
find /path/to/fearlesscms -type d -ls
find /path/to/fearlesscms -type f -ls
```

### Check Web Server User
```bash
# For Apache
ps aux | grep apache
# For Nginx
ps aux | grep nginx
# For PHP-FPM
ps aux | grep php-fpm
```

### Test File Writing
```bash
# Test if web server can write to a file
sudo -u www-data touch /path/to/fearlesscms/test.txt
sudo -u www-data rm /path/to/fearlesscms/test.txt
```

## Notes
- These permissions are based on a root-owned web server setup
- Adjust ownership and permissions based on your specific web server configuration
- Always backup before changing permissions on production systems
- Consider using a deployment script to set correct permissions automatically
