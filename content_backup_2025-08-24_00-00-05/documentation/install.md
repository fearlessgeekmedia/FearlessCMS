<!-- json {
    "title": "Installation Guide",
    "template": "documentation"
} -->

# Installation Script Guide

The `install.php` script is a comprehensive installation and setup tool for FearlessCMS that provides both web-based and command-line interfaces for initializing your CMS installation.

## 📋 Table of Contents

- [Overview](#overview)
- [Security Features](#security-features)
- [Web Interface](#web-interface)
- [Command Line Interface](#command-line-interface)
- [Directory Structure](#directory-structure)
- [Configuration](#configuration)
- [Troubleshooting](#troubleshooting)
- [Security Best Practices](#security-best-practices)

## 🎯 Overview

The installation script (`install.php`) is designed to:

- Create necessary directory structure
- Set up configuration files
- Install export dependencies (Node.js packages)
- Create administrator accounts
- Validate system requirements
- Provide security features like CSRF protection and rate limiting

## 🔒 Security Features

### CSRF Protection
- Generates and validates CSRF tokens for all POST requests
- Prevents cross-site request forgery attacks
- Session-based token generation

### Rate Limiting
- Prevents brute force attacks on admin creation
- Configurable attempt limits and time windows
- Session-based rate limiting

### Command Whitelisting
- Only allows specific, safe commands to execute
- Prevents arbitrary command execution
- Validates working directory paths

### Input Validation
- Username format validation (3-32 characters, alphanumeric + underscore + hyphen)
- Password strength requirements
- Directory path validation

## 🌐 Web Interface

### Access
Navigate to `install.php` in your web browser to access the web-based installation interface.

### Available Actions

#### 1. Create Directories
Creates the essential directory structure:
- `config/` - Configuration files
- `admin/uploads/` - Admin file uploads
- `uploads/` - Public file uploads
- `content/` - Content files
- `sessions/` - Session storage
- `cache/` - Cache files
- `backups/` - Backup storage
- `.fcms_updates/` - Update files

#### 2. Install Export Dependencies
Installs Node.js packages required for the export functionality:
- `fs-extra` - Enhanced file system operations
- `handlebars` - Template engine
- `marked` - Markdown parser

#### 3. Create Administrator Account
Creates the first administrator user with full system access.

## 💻 Command Line Interface

The script supports various CLI options for automated installation and server management.

### Basic Usage
```bash
php install.php [options]
```

### Available Options

#### System Check
```bash
php install.php --check
```
Displays:
- PHP version information
- Required extension status
- Directory existence and permissions
- Project root path

#### Create Directories
```bash
php install.php --create-dirs
```
Creates all necessary directories and initializes configuration files.

#### Install Export Dependencies
```bash
php install.php --install-export-deps
```
Installs Node.js dependencies for the export functionality.

#### Create Administrator Account
```bash
php install.php --create-admin=username --password=password
```
Creates an administrator account with the specified credentials.

**Alternative password input methods:**
```bash
# Read password from file
php install.php --create-admin=username --password-file=/path/to/password.txt

# Interactive password input
php install.php --create-admin=username
```

### CLI Examples

#### Complete Automated Installation
```bash
# Check system requirements
php install.php --check

# Create directory structure
php install.php --create-dirs

# Install dependencies
php install.php --install-export-deps

# Create admin account
php install.php --create-admin=admin --password=securepassword123
```

#### Server Setup Script
```bash
#!/usr/bin/env bash
echo "Setting up FearlessCMS..."

# Check system
php install.php --check

# Create directories
php install.php --create-dirs

# Install dependencies
php install.php --install-export-deps

# Create admin (password from environment variable)
php install.php --create-admin=admin --password="$ADMIN_PASSWORD"

echo "Installation complete!"
```

## 📁 Directory Structure

The installer creates the following directory structure:

```
FearlessCMS/
├── config/                 # Configuration files
├── admin/
│   └── uploads/           # Admin file uploads
├── uploads/                # Public file uploads
├── content/                # Content files
├── sessions/               # Session storage
├── cache/                  # Cache files
├── backups/                # Backup storage
└── .fcms_updates/         # Update files
```

## ⚙️ Configuration

### Environment Variables
- `FCMS_CONFIG_DIR` - Custom configuration directory path

### Default Configuration
The installer automatically creates default configuration files when `includes/config.php` is included.

### Security Configuration
- CSRF token generation
- Rate limiting settings
- Command whitelist validation

## 🔧 Troubleshooting

### Common Issues

#### Permission Errors
```bash
# Check directory permissions
ls -la config/
ls -la uploads/

# Fix permissions if needed
chmod 755 config/
chmod 755 uploads/
```

#### Node.js Dependencies
```bash
# Check Node.js installation
node --version
npm --version

# Install Node.js if missing
# Ubuntu/Debian
sudo apt install nodejs npm

# CentOS/RHEL
sudo yum install nodejs npm

# macOS
brew install node
```

#### PHP Extensions
Ensure these PHP extensions are loaded:
- `curl` - HTTP requests
- `json` - JSON processing
- `mbstring` - Multibyte string handling
- `phar` - PHAR archives
- `zip` - ZIP file handling
- `openssl` - Encryption and SSL

### Error Messages

#### "Command not allowed for security reasons"
- The command is not in the whitelist
- Use only the supported installation commands

#### "Invalid working directory"
- Working directory is outside project root
- Ensure you're running from the FearlessCMS directory

#### "Failed to create directory"
- Check parent directory permissions
- Ensure sufficient disk space
- Verify user has write permissions

## 🛡️ Security Best Practices

### Critical Post-Installation Security Steps

#### 1. Immediate Security Actions
```bash
# Delete the installer (CRITICAL)
rm install.php

# Secure configuration files
chmod 600 config/*.json
chmod 700 config/

# Secure session directory
chmod 700 sessions/

# Set restrictive permissions on sensitive files
find . -name "*.log" -exec chmod 600 {} \;
```

#### 2. File System Security Hardening
```bash
# Create .htaccess files to block access to sensitive directories
cat > config/.htaccess << 'EOF'
Order deny,allow
Deny from all
EOF

cat > sessions/.htaccess << 'EOF'
Order deny,allow
Deny from all
EOF

cat > backups/.htaccess << 'EOF'
Order deny,allow
Deny from all
EOF

# Secure file ownership (replace 'www-data' with your web server user)
chown -R www-data:www-data config/ sessions/ uploads/ cache/ backups/
```

#### 3. Environment Security Configuration
```bash
# Set security environment variables
export FCMS_DEBUG=false
export FCMS_SECURE_MODE=true
export FCMS_CONFIG_DIR=/secure/path/outside/webroot

# Add to your web server configuration or .env file
echo "FCMS_DEBUG=false" >> .env
echo "FCMS_SECURE_MODE=true" >> .env
```

### Production Deployment Security

#### 1. SSL/TLS Configuration
```apache
# Apache SSL configuration
<VirtualHost *:443>
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    SSLProtocol all -SSLv2 -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256
    SSLHonorCipherOrder on
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</VirtualHost>
```

```nginx
# Nginx SSL configuration
server {
    listen 443 ssl http2;
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
}
```

#### 2. Security Headers Configuration
```apache
# Apache security headers
Header always set X-Frame-Options "DENY"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'"
Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
```

```nginx
# Nginx security headers
add_header X-Frame-Options "DENY" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'" always;
add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;
```

#### 3. Directory Access Restrictions
```apache
# Apache directory restrictions
<DirectoryMatch "/(config|sessions|backups|\.git)">
    Order deny,allow
    Deny from all
</DirectoryMatch>

<Files "*.json">
    Order deny,allow
    Deny from all
</Files>

<Files "*.log">
    Order deny,allow
    Deny from all
</Files>
```

```nginx
# Nginx directory restrictions
location ~ /(config|sessions|backups|\.git) {
    deny all;
    return 404;
}

location ~ \.(json|log)$ {
    deny all;
    return 404;
}
```

#### 4. PHP Security Configuration
```ini
# Add to php.ini or .htaccess
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
allow_url_fopen = Off
allow_url_include = Off
enable_dl = Off
file_uploads = On
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 30
max_input_time = 30
memory_limit = 128M
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.cookie_samesite = Strict
```

### Advanced Security Configuration

#### 1. Firewall Rules
```bash
# UFW (Ubuntu/Debian)
ufw allow 22    # SSH
ufw allow 80    # HTTP
ufw allow 443   # HTTPS
ufw enable

# Restrict admin access by IP (optional)
ufw allow from YOUR_IP_ADDRESS to any port 80
ufw allow from YOUR_IP_ADDRESS to any port 443
```

#### 2. Fail2Ban Configuration
```bash
# Install fail2ban
sudo apt install fail2ban

# Create FearlessCMS jail
cat > /etc/fail2ban/jail.local << 'EOF'
[fearlesscms]
enabled = true
port = http,https
filter = fearlesscms
logpath = /path/to/fearlesscms/error.log
maxretry = 5
bantime = 3600
findtime = 600
EOF

# Create filter
cat > /etc/fail2ban/filter.d/fearlesscms.conf << 'EOF'
[Definition]
failregex = ^.*Failed login attempt.*from <HOST>.*$
            ^.*Suspicious activity detected.*from <HOST>.*$
ignoreregex =
EOF
```

#### 3. Security Monitoring Setup
```bash
# Create security monitoring script
cat > /usr/local/bin/fcms-security-monitor.sh << 'EOF'
#!/usr/bin/env bash

FCMS_PATH="/path/to/fearlesscms"
LOG_FILE="/var/log/fcms-security.log"

# Monitor failed logins
tail -f "$FCMS_PATH/error.log" | grep -i "failed login" >> "$LOG_FILE" &

# Monitor suspicious file access
tail -f /var/log/apache2/access.log | grep -E "(\.\.\/|config\/|sessions\/)" >> "$LOG_FILE" &

# Monitor unusual upload activity
inotifywait -m -r "$FCMS_PATH/uploads/" -e create,modify --format '%w%f %e %T' --timefmt '%Y-%m-%d %H:%M:%S' >> "$LOG_FILE" &
EOF

chmod +x /usr/local/bin/fcms-security-monitor.sh

# Add to crontab for automatic startup
echo "@reboot /usr/local/bin/fcms-security-monitor.sh" | crontab -
```

### Access Control & Authentication

#### 1. Strong Password Requirements
- **Minimum length**: 12 characters (16+ recommended)
- **Complexity**: Mixed case, numbers, symbols
- **Uniqueness**: Different from other passwords
- **Regular updates**: Change every 90 days

#### 2. Two-Factor Authentication Setup
```php
// Enable 2FA in config/auth.json
{
    "require_2fa": true,
    "2fa_methods": ["totp", "backup_codes"],
    "session_timeout": 1800
}
```

#### 3. Role-Based Access Control
```json
// Configure roles in config/roles.json
{
    "admin": {
        "permissions": ["all"],
        "ip_restrictions": ["192.168.1.0/24"]
    },
    "editor": {
        "permissions": ["content_edit", "file_upload"],
        "max_upload_size": "5MB"
    },
    "author": {
        "permissions": ["content_create", "content_edit_own"],
        "max_upload_size": "2MB"
    }
}
```

### Security Validation & Testing

#### 1. Security Checklist Verification
```bash
# Run the security validation script
curl -s https://raw.githubusercontent.com/fearlesscms/security-tools/main/validate.sh | bash

# Or manual checks:
# Check file permissions
find . -type f -perm /o+w -exec ls -la {} \;

# Check for sensitive files in web root
find . -name "*.json" -o -name "*.log" -o -name "*.sql" | grep -v node_modules

# Verify SSL configuration
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com
```

#### 2. Penetration Testing
```bash
# Basic security scan with nikto
nikto -h https://yourdomain.com

# SQL injection testing with sqlmap
sqlmap -u "https://yourdomain.com/login.php" --forms --batch --level=3

# SSL/TLS testing
testssl.sh yourdomain.com
```

### Backup Security

#### 1. Encrypted Backups
```bash
# Create encrypted backup script
cat > /usr/local/bin/fcms-backup.sh << 'EOF'
#!/usr/bin/env bash

FCMS_PATH="/path/to/fearlesscms"
BACKUP_PATH="/secure/backup/location"
DATE=$(date +%Y%m%d_%H%M%S)
GPG_RECIPIENT="backup@yourdomain.com"

# Create compressed archive
tar -czf "/tmp/fcms_backup_$DATE.tar.gz" -C "$FCMS_PATH" .

# Encrypt backup
gpg --encrypt --recipient "$GPG_RECIPIENT" --output "$BACKUP_PATH/fcms_backup_$DATE.tar.gz.gpg" "/tmp/fcms_backup_$DATE.tar.gz"

# Clean up temporary file
rm "/tmp/fcms_backup_$DATE.tar.gz"

# Remove backups older than 30 days
find "$BACKUP_PATH" -name "fcms_backup_*.tar.gz.gpg" -mtime +30 -delete
EOF

chmod +x /usr/local/bin/fcms-backup.sh

# Schedule daily backups
echo "0 2 * * * /usr/local/bin/fcms-backup.sh" | crontab -
```

### Incident Response Preparation

#### 1. Security Contact Information
```bash
# Create security contacts file
cat > security-contacts.txt << 'EOF'
Emergency Security Contact: security@yourdomain.com
System Administrator: admin@yourdomain.com
Hosting Provider: support@hostingprovider.com
Legal Counsel: legal@yourdomain.com
EOF
```

#### 2. Incident Response Scripts
```bash
# Emergency lockdown script
cat > /usr/local/bin/fcms-lockdown.sh << 'EOF'
#!/usr/bin/env bash

# Disable all user logins except admin
touch /path/to/fearlesscms/maintenance.lock

# Block suspicious IPs (example)
# iptables -A INPUT -s SUSPICIOUS_IP -j DROP

# Create incident log
echo "$(date): Emergency lockdown activated" >> /var/log/fcms-incidents.log

echo "FearlessCMS has been locked down. Check incident logs for details."
EOF

chmod +x /usr/local/bin/fcms-lockdown.sh
```

### Regular Security Maintenance

#### 1. Security Update Schedule
- **Daily**: Monitor security logs
- **Weekly**: Check for failed login attempts
- **Monthly**: Update dependencies and review user accounts
- **Quarterly**: Security configuration review
- **Annually**: Full security audit and penetration testing

#### 2. Automated Security Checks
```bash
# Create security check script
cat > /usr/local/bin/fcms-security-check.sh << 'EOF'
#!/usr/bin/env bash

echo "FearlessCMS Security Check Report - $(date)"
echo "================================================"

# Check file permissions
echo "Checking file permissions..."
if [ -r config/auth.json ]; then
    echo "WARNING: config/auth.json is world-readable"
fi

# Check for default passwords
echo "Checking for default passwords..."
if grep -q "changeme\|admin123\|password" config/*.json; then
    echo "WARNING: Default passwords detected"
fi

# Check SSL certificate
echo "Checking SSL certificate..."
if command -v openssl >/dev/null; then
    openssl s_client -connect localhost:443 -servername $(hostname) </dev/null 2>/dev/null | openssl x509 -noout -dates
fi

echo "Security check completed."
EOF

chmod +x /usr/local/bin/fcms-security-check.sh

# Run weekly security checks
echo "0 9 * * 1 /usr/local/bin/fcms-security-check.sh" | crontab -
```

## 📚 Related Documentation

- [Installation and Setup](gettingstarted.md)
- [File Permissions Guide](file-permissions.md)
- [CMS Modes Guide](cms-modes.md)
- [Configuration Guide](../config/README.md)

## 🆘 Getting Help

If you encounter issues during installation:

1. **Check system requirements** using `--check` option
2. **Review error messages** for specific issues
3. **Verify permissions** on directories and files
4. **Check PHP error logs** for additional details
5. **Consult the community** for support

---

**Happy installing!** 🚀

*This documentation is maintained by the FearlessCMS community. Last updated: January 2024*
