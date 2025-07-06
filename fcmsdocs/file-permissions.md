# FearlessCMS File Permissions Guide

## Overview
This document outlines the recommended file permissions for FearlessCMS to function properly without permission errors. The recommended approach is to use proper ownership with standard permissions rather than overly permissive 777/666 permissions.

## Recommended Approach: Proper Ownership

### Web Server User Identification
First, identify your web server user:
```bash
# For Apache
ps aux | grep apache
# For Nginx with PHP-FPM
ps aux | grep php-fpm
# For other setups
ps aux | grep -E "(apache|nginx|httpd)"
```

Common web server users:
- `www-data` (Ubuntu/Debian)
- `apache` (CentOS/RHEL)
- `http` (Arch Linux)
- `nginx` (some setups)

### Setting Proper Ownership
Set ownership to your web server user (replace `http` with your actual web server user):

```bash
# Set ownership for all critical directories and files
sudo chown -R http:http /path/to/fearlesscms/sessions/
sudo chown -R http:http /path/to/fearlesscms/content/forms/
sudo chown -R http:http /path/to/fearlesscms/content/form_submissions/
sudo chown -R http:http /path/to/fearlesscms/config/
sudo chown -R http:http /path/to/fearlesscms/uploads/
sudo chown -R http:http /path/to/fearlesscms/admin/uploads/
sudo chown http:http /path/to/fearlesscms/sitemap.xml
sudo chown http:http /path/to/fearlesscms/robots.txt
sudo chown http:http /path/to/fearlesscms/debug.log
```

### Setting Proper Permissions
Use standard permissions with proper ownership:

```bash
# Set directories to 755
sudo chmod 755 /path/to/fearlesscms/sessions/
sudo chmod 755 /path/to/fearlesscms/content/forms/
sudo chmod 755 /path/to/fearlesscms/content/form_submissions/
sudo chmod 755 /path/to/fearlesscms/config/
sudo chmod 755 /path/to/fearlesscms/uploads/
sudo chmod 755 /path/to/fearlesscms/admin/uploads/

# Set files to 644
sudo chmod 644 /path/to/fearlesscms/sitemap.xml
sudo chmod 644 /path/to/fearlesscms/robots.txt
sudo chmod 644 /path/to/fearlesscms/debug.log
```

## Critical Files and Directories

### Session Management
- `sessions/` - 755 (directory, owned by web server user)
- All session files in `sessions/` - 644 (owned by web server user)

### Forms Plugin
- `content/forms/` - 755 (directory, owned by web server user)
- `content/form_submissions/` - 755 (directory, owned by web server user)
- `content/forms/forms.log` - 644 (owned by web server user)

### Configuration Files
- `config/` - 755 (directory, owned by web server user)
- All `.json` files in `config/` - 644 (owned by web server user)

### SEO Files
- `sitemap.xml` - 644 (owned by web server user)
- `robots.txt` - 644 (owned by web server user)

### Upload Directories
- `uploads/` - 755 (directory, owned by web server user)
- `admin/uploads/` - 755 (directory, owned by web server user)

### Debug and Log Files
- `debug.log` - 644 (owned by web server user)
- `error.log` - 644 (owned by web server user)

## Quick Fix Commands

### Complete Permission Fix (Recommended)
```bash
# Replace 'http' with your actual web server user
WEB_USER="http"

# Set ownership for all writable locations
sudo chown -R $WEB_USER:$WEB_USER /path/to/fearlesscms/sessions/
sudo chown -R $WEB_USER:$WEB_USER /path/to/fearlesscms/content/forms/
sudo chown -R $WEB_USER:$WEB_USER /path/to/fearlesscms/content/form_submissions/
sudo chown -R $WEB_USER:$WEB_USER /path/to/fearlesscms/config/
sudo chown -R $WEB_USER:$WEB_USER /path/to/fearlesscms/uploads/
sudo chown -R $WEB_USER:$WEB_USER /path/to/fearlesscms/admin/uploads/
sudo chown $WEB_USER:$WEB_USER /path/to/fearlesscms/sitemap.xml
sudo chown $WEB_USER:$WEB_USER /path/to/fearlesscms/robots.txt
sudo chown $WEB_USER:$WEB_USER /path/to/fearlesscms/debug.log

# Set proper permissions
sudo chmod 755 /path/to/fearlesscms/sessions/
sudo chmod 755 /path/to/fearlesscms/content/forms/
sudo chmod 755 /path/to/fearlesscms/content/form_submissions/
sudo chmod 755 /path/to/fearlesscms/config/
sudo chmod 755 /path/to/fearlesscms/uploads/
sudo chmod 755 /path/to/fearlesscms/admin/uploads/
sudo chmod 644 /path/to/fearlesscms/sitemap.xml
sudo chmod 644 /path/to/fearlesscms/robots.txt
sudo chmod 644 /path/to/fearlesscms/debug.log
```

### Legacy Approach (Not Recommended)
If you must use overly permissive permissions for development:

```bash
# Set all directories to 777 (NOT recommended for production)
find /path/to/fearlesscms -type d -exec chmod 777 {} \;

# Set all files to 666 (NOT recommended for production)
find /path/to/fearlesscms -type f -exec chmod 666 {} \;
```

## Common Permission Errors and Solutions

### Session Errors
- **Error**: `session_start(): open(...) failed: Permission denied`
- **Solution**: Set `sessions/` directory ownership to web server user with 755 permissions

### Forms Plugin Errors
- **Error**: `mkdir(): Permission denied in plugins/forms/forms.php`
- **Solution**: Create `content/form_submissions/` directory and set ownership to web server user

### File Writing Errors
- **Error**: `file_put_contents(): Failed to open stream: Permission denied`
- **Solution**: Set file ownership to web server user with 644 permissions

### Configuration Errors
- **Error**: `Failed to save configuration`
- **Solution**: Set `config/` directory ownership to web server user with 755 permissions

## Security Best Practices

### Development Environment
- Use proper ownership (web server user) with standard permissions (755/644)
- Avoid overly permissive 777/666 permissions
- Test with the actual web server user

### Production Environment
- Always use proper ownership with standard permissions
- Never use 777/666 permissions
- Consider using ACLs for more granular control
- Regularly audit file permissions

### Verification Commands
```bash
# Check current permissions
ls -la /path/to/fearlesscms/sessions/
ls -la /path/to/fearlesscms/content/forms/
ls -la /path/to/fearlesscms/config/
ls -la /path/to/fearlesscms/uploads/

# Test web server write access
sudo -u $WEB_USER touch /path/to/fearlesscms/test.txt
sudo -u $WEB_USER rm /path/to/fearlesscms/test.txt
```

## Troubleshooting

### Check Web Server User
```bash
# For Apache
ps aux | grep apache
# For Nginx with PHP-FPM
ps aux | grep php-fpm
# For other setups
ps aux | grep -E "(apache|nginx|httpd|http)"
```

### Verify Permissions
```bash
# Check ownership and permissions
ls -la /path/to/fearlesscms/sessions/ content/forms/ config/ uploads/ sitemap.xml robots.txt debug.log
```

### Test File Operations
```bash
# Test if web server can write to critical locations
sudo -u $WEB_USER touch /path/to/fearlesscms/sessions/test.txt
sudo -u $WEB_USER touch /path/to/fearlesscms/content/forms/test.txt
sudo -u $WEB_USER touch /path/to/fearlesscms/config/test.txt
sudo -u $WEB_USER rm /path/to/fearlesscms/sessions/test.txt
sudo -u $WEB_USER rm /path/to/fearlesscms/content/forms/test.txt
sudo -u $WEB_USER rm /path/to/fearlesscms/config/test.txt
```

## Notes
- This approach is more secure than using 777/666 permissions
- Always backup before changing permissions on production systems
- The web server user must have read/write access to specific directories
- Consider using deployment scripts to automate permission setup
- Regular permission audits help maintain security
