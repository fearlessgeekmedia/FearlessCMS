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

## Storing Configuration Files Outside Webroot (Recommended)

For enhanced security, you can store your configuration files outside the web-accessible directory. FearlessCMS supports this via the `FCMS_CONFIG_DIR` environment variable.

**How to use:**

1. Move your `config/` directory to a secure location outside your webroot, e.g. `/etc/fearlesscms-config`.
2. Set the environment variable in your web server or shell:
   - **Apache:**
     ```
     SetEnv FCMS_CONFIG_DIR /etc/fearlesscms-config
     ```
   - **Nginx + PHP-FPM:**
     ```
     env[FCMS_CONFIG_DIR] = /etc/fearlesscms-config
     ```
   - **CLI/testing:**
     ```
     export FCMS_CONFIG_DIR=/etc/fearlesscms-config
     ```
3. FearlessCMS will automatically use this directory for all configuration files.

**Benefits:**
- Prevents direct web access to sensitive config files
- Allows for centralized config management in multi-site setups

**Note:** You can also set `FCMS_ADMIN_CONFIG_DIR` for admin-specific config files if needed.

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
- Store config files outside the webroot using `FCMS_CONFIG_DIR` when possible

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

## Advanced Security Hardening

### Access Control Lists (ACLs)
For more granular permission control, consider using ACLs:

```bash
# Install ACL tools
sudo apt install acl

# Set ACLs for fine-grained control
sudo setfacl -R -m u:www-data:rwx /path/to/fearlesscms/sessions/
sudo setfacl -R -m u:admin:rx /path/to/fearlesscms/sessions/
sudo setfacl -R -m g:developers:rx /path/to/fearlesscms/config/
sudo setfacl -R -d -m u:www-data:rwx /path/to/fearlesscms/sessions/

# View ACL settings
getfacl /path/to/fearlesscms/sessions/
```

### SELinux/AppArmor Integration

#### SELinux Configuration
```bash
# Check SELinux status
sestatus

# Set SELinux contexts for FearlessCMS
sudo setsebool -P httpd_can_network_connect 1
sudo semanage fcontext -a -t httpd_exec_t "/var/www/html/fearlesscms(/.*)?"
sudo restorecon -R /var/www/html/fearlesscms/

# Create custom SELinux policy for FearlessCMS
sudo ausearch -c 'httpd' --raw | audit2allow -M fearlesscms-policy
sudo semodule -i fearlesscms-policy.pp
```

#### AppArmor Profile
```bash
# Create AppArmor profile for FearlessCMS
sudo cat > /etc/apparmor.d/fearlesscms << 'EOF'
#include <tunables/global>

/var/www/html/fearlesscms/** {
  #include <abstractions/base>
  #include <abstractions/web-data>
  
  capability dac_override,
  capability setgid,
  capability setuid,
  
  /var/www/html/fearlesscms/** r,
  /var/www/html/fearlesscms/config/ rw,
  /var/www/html/fearlesscms/sessions/ rw,
  /var/www/html/fearlesscms/uploads/ rw,
  /var/www/html/fearlesscms/cache/ rw,
  
  /tmp/ r,
  /tmp/** rw,
  
  deny /etc/passwd r,
  deny /etc/shadow r,
  deny /root/ r,
  deny /home/*/ r,
}
EOF

# Load the profile
sudo apparmor_parser -r /etc/apparmor.d/fearlesscms
```

### Container Security (Docker)

#### Dockerfile Security
```dockerfile
# Use non-root user
FROM php:8.1-apache
RUN useradd -u 1001 -m fearlesscms
USER fearlesscms

# Set secure file permissions
COPY --chown=fearlesscms:fearlesscms . /var/www/html/
RUN chmod 700 /var/www/html/config /var/www/html/sessions
RUN chmod 600 /var/www/html/config/*.json

# Drop unnecessary capabilities
RUN apt-get update && apt-get install -y libcap2-bin
RUN setcap -r /usr/local/bin/php
```

### File Integrity Monitoring

#### AIDE (Advanced Intrusion Detection Environment)
```bash
# Install AIDE
sudo apt install aide

# Initialize AIDE database
sudo aideinit

# Create FearlessCMS-specific configuration
sudo cat >> /etc/aide/aide.conf << 'EOF'
# FearlessCMS monitoring rules
/var/www/html/fearlesscms/config f+p+u+g+s+m+c+md5+sha256
/var/www/html/fearlesscms/includes f+p+u+g+s+m+c+md5+sha256
/var/www/html/fearlesscms/admin f+p+u+g+s+m+c+md5+sha256
/var/www/html/fearlesscms/themes f+p+u+g+s+m+c+md5+sha256
/var/www/html/fearlesscms/plugins f+p+u+g+s+m+c+md5+sha256

# Exclude frequently changing directories
!/var/www/html/fearlesscms/sessions
!/var/www/html/fearlesscms/cache
!/var/www/html/fearlesscms/uploads
EOF

# Update AIDE database
sudo aide --update

# Schedule daily integrity checks
echo "0 3 * * * /usr/bin/aide --check" | sudo crontab -
```

#### Tripwire Configuration
```bash
# Install Tripwire
sudo apt install tripwire

# Configure for FearlessCMS
sudo cat > /etc/tripwire/twpol.txt << 'EOF'
# FearlessCMS Policy
(
  rulename = "FearlessCMS Core Files",
  severity = $(SIG_HI)
)
{
  /var/www/html/fearlesscms/config    -> $(SEC_CONFIG);
  /var/www/html/fearlesscms/includes  -> $(SEC_CRIT);
  /var/www/html/fearlesscms/admin     -> $(SEC_CRIT);
  /var/www/html/fearlesscms/base.php  -> $(SEC_CRIT);
  /var/www/html/fearlesscms/index.php -> $(SEC_CRIT);
}
EOF

# Initialize Tripwire
sudo tripwire --init
```

## Security Monitoring and Alerting

### Real-time File Monitoring
```bash
# Install inotify tools
sudo apt install inotify-tools

# Create monitoring script
sudo cat > /usr/local/bin/fcms-file-monitor.sh << 'EOF'
#!/usr/bin/env bash

WATCH_DIRS="/var/www/html/fearlesscms/config /var/www/html/fearlesscms/admin"
LOG_FILE="/var/log/fcms-file-changes.log"

inotifywait -m -r --format '%T %w%f %e' --timefmt '%Y-%m-%d %H:%M:%S' \
  $WATCH_DIRS -e modify,create,delete,move,attrib | \
  while read timestamp file event; do
    echo "$timestamp: $event on $file" >> $LOG_FILE
    
    # Alert on critical file changes
    if [[ "$file" =~ \.(php|json)$ ]] && [[ "$event" =~ (MODIFY|CREATE|DELETE) ]]; then
      echo "ALERT: Critical file change detected: $file ($event)" | \
        mail -s "FearlessCMS Security Alert" security@yourdomain.com
    fi
  done
EOF

chmod +x /usr/local/bin/fcms-file-monitor.sh

# Run as systemd service
sudo cat > /etc/systemd/system/fcms-monitor.service << 'EOF'
[Unit]
Description=FearlessCMS File Monitor
After=network.target

[Service]
Type=simple
User=root
ExecStart=/usr/local/bin/fcms-file-monitor.sh
Restart=always

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl enable fcms-monitor
sudo systemctl start fcms-monitor
```

### Log Aggregation and Analysis
```bash
# Install and configure rsyslog for centralized logging
sudo cat >> /etc/rsyslog.conf << 'EOF'
# FearlessCMS logging
local0.* /var/log/fearlesscms.log
local0.* @@logserver.yourdomain.com:514
EOF

# Configure logrotate
sudo cat > /etc/logrotate.d/fearlesscms << 'EOF'
/var/log/fearlesscms.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 640 root adm
    postrotate
        systemctl reload rsyslog
    endscript
}
EOF
```

## Compliance and Auditing

### PCI DSS Compliance
```bash
# PCI DSS file permission requirements
# Requirement 2.2.4: Configure system security parameters

# Set restrictive permissions on cardholder data
chmod 600 /var/www/html/fearlesscms/config/payment.json
chown root:root /var/www/html/fearlesscms/config/payment.json

# Implement file access logging
sudo cat >> /etc/audit/rules.d/fearlesscms.rules << 'EOF'
# Monitor access to payment configuration
-w /var/www/html/fearlesscms/config/payment.json -p warx -k payment_access
-w /var/www/html/fearlesscms/plugins/payment/ -p warx -k payment_plugins
EOF

sudo service auditd restart
```

### GDPR Compliance
```bash
# Data protection file permissions
chmod 600 /var/www/html/fearlesscms/config/gdpr.json
chmod 700 /var/www/html/fearlesscms/data/personal/

# Implement data access logging
sudo cat >> /etc/audit/rules.d/gdpr.rules << 'EOF'
# Monitor access to personal data
-w /var/www/html/fearlesscms/data/personal/ -p warx -k gdpr_data_access
-w /var/www/html/fearlesscms/config/gdpr.json -p warx -k gdpr_config
EOF
```

### SOX Compliance
```bash
# Financial data security
chmod 600 /var/www/html/fearlesscms/config/financial.json
chown root:www-data /var/www/html/fearlesscms/config/financial.json

# Implement change control logging
sudo cat >> /etc/audit/rules.d/sox.rules << 'EOF'
# Monitor financial system changes
-w /var/www/html/fearlesscms/config/financial.json -p warx -k sox_financial
-w /var/www/html/fearlesscms/plugins/accounting/ -p warx -k sox_accounting
EOF
```

## Automated Security Validation

### Permission Validation Script
```bash
# Create automated permission checker
sudo cat > /usr/local/bin/fcms-security-check.sh << 'EOF'
#!/usr/bin/env bash

FCMS_PATH="/var/www/html/fearlesscms"
REPORT_FILE="/var/log/fcms-security-report.log"
ERROR_COUNT=0

echo "FearlessCMS Security Check - $(date)" > $REPORT_FILE
echo "==========================================" >> $REPORT_FILE

# Check critical directory permissions
check_dir_perms() {
    local dir="$1"
    local expected="$2"
    local actual=$(stat -c "%a" "$dir" 2>/dev/null)
    
    if [ "$actual" != "$expected" ]; then
        echo "ERROR: $dir has permissions $actual, expected $expected" >> $REPORT_FILE
        ((ERROR_COUNT++))
    else
        echo "OK: $dir has correct permissions ($actual)" >> $REPORT_FILE
    fi
}

# Check file permissions
check_file_perms() {
    local file="$1"
    local expected="$2"
    local actual=$(stat -c "%a" "$file" 2>/dev/null)
    
    if [ "$actual" != "$expected" ]; then
        echo "ERROR: $file has permissions $actual, expected $expected" >> $REPORT_FILE
        ((ERROR_COUNT++))
    else
        echo "OK: $file has correct permissions ($actual)" >> $REPORT_FILE
    fi
}

# Check ownership
check_ownership() {
    local path="$1"
    local expected_user="$2"
    local expected_group="$3"
    local actual_user=$(stat -c "%U" "$path" 2>/dev/null)
    local actual_group=$(stat -c "%G" "$path" 2>/dev/null)
    
    if [ "$actual_user" != "$expected_user" ] || [ "$actual_group" != "$expected_group" ]; then
        echo "ERROR: $path owned by $actual_user:$actual_group, expected $expected_user:$expected_group" >> $REPORT_FILE
        ((ERROR_COUNT++))
    else
        echo "OK: $path has correct ownership ($actual_user:$actual_group)" >> $REPORT_FILE
    fi
}

# Perform checks
check_dir_perms "$FCMS_PATH/config" "700"
check_dir_perms "$FCMS_PATH/sessions" "700"
check_dir_perms "$FCMS_PATH/uploads" "755"
check_file_perms "$FCMS_PATH/config/auth.json" "600"
check_file_perms "$FCMS_PATH/config/config.json" "600"
check_ownership "$FCMS_PATH/sessions" "www-data" "www-data"
check_ownership "$FCMS_PATH/config" "www-data" "www-data"

# Check for world-writable files
echo "" >> $REPORT_FILE
echo "Checking for world-writable files:" >> $REPORT_FILE
WORLD_WRITABLE=$(find "$FCMS_PATH" -type f -perm /o+w -not -path "*/uploads/*" 2>/dev/null)
if [ -n "$WORLD_WRITABLE" ]; then
    echo "ERROR: Found world-writable files:" >> $REPORT_FILE
    echo "$WORLD_WRITABLE" >> $REPORT_FILE
    ((ERROR_COUNT++))
else
    echo "OK: No unexpected world-writable files found" >> $REPORT_FILE
fi

# Check for suspicious files
echo "" >> $REPORT_FILE
echo "Checking for suspicious files:" >> $REPORT_FILE
SUSPICIOUS=$(find "$FCMS_PATH" -name "*.php" -exec grep -l "eval\|base64_decode\|shell_exec" {} \; 2>/dev/null)
if [ -n "$SUSPICIOUS" ]; then
    echo "WARNING: Found files with suspicious content:" >> $REPORT_FILE
    echo "$SUSPICIOUS" >> $REPORT_FILE
    ((ERROR_COUNT++))
else
    echo "OK: No suspicious files detected" >> $REPORT_FILE
fi

# Summary
echo "" >> $REPORT_FILE
echo "Security Check Summary:" >> $REPORT_FILE
echo "Total Errors: $ERROR_COUNT" >> $REPORT_FILE

if [ $ERROR_COUNT -gt 0 ]; then
    echo "SECURITY CHECK FAILED - $ERROR_COUNT errors found" >> $REPORT_FILE
    # Send alert email
    mail -s "FearlessCMS Security Check Failed" security@yourdomain.com < $REPORT_FILE
    exit 1
else
    echo "SECURITY CHECK PASSED - No errors found" >> $REPORT_FILE
    exit 0
fi
EOF

chmod +x /usr/local/bin/fcms-security-check.sh

# Schedule regular security checks
echo "0 6 * * * /usr/local/bin/fcms-security-check.sh" | crontab -
```

### Automated Remediation
```bash
# Create auto-fix script for common issues
sudo cat > /usr/local/bin/fcms-auto-fix.sh << 'EOF'
#!/usr/bin/env bash

FCMS_PATH="/var/www/html/fearlesscms"
WEB_USER="www-data"
LOG_FILE="/var/log/fcms-auto-fix.log"

echo "FearlessCMS Auto-Fix - $(date)" >> $LOG_FILE

# Fix directory permissions
fix_permissions() {
    echo "Fixing file permissions..." >> $LOG_FILE
    
    # Set directory permissions
    find "$FCMS_PATH" -type d -exec chmod 755 {} \; 2>> $LOG_FILE
    chmod 700 "$FCMS_PATH/config" "$FCMS_PATH/sessions" "$FCMS_PATH/backups" 2>> $LOG_FILE
    
    # Set file permissions
    find "$FCMS_PATH" -type f -exec chmod 644 {} \; 2>> $LOG_FILE
    chmod 600 "$FCMS_PATH/config"/*.json 2>> $LOG_FILE
    
    # Set ownership
    chown -R "$WEB_USER:$WEB_USER" "$FCMS_PATH/sessions" "$FCMS_PATH/config" "$FCMS_PATH/uploads" "$FCMS_PATH/cache" 2>> $LOG_FILE
    
    echo "Permissions fixed" >> $LOG_FILE
}

# Remove world-writable permissions
fix_world_writable() {
    echo "Removing world-writable permissions..." >> $LOG_FILE
    find "$FCMS_PATH" -type f -perm /o+w -not -path "*/uploads/*" -exec chmod o-w {} \; 2>> $LOG_FILE
    echo "World-writable permissions removed" >> $LOG_FILE
}

# Create missing .htaccess files
create_htaccess() {
    echo "Creating missing .htaccess files..." >> $LOG_FILE
    
    for dir in config sessions backups; do
        if [ -d "$FCMS_PATH/$dir" ] && [ ! -f "$FCMS_PATH/$dir/.htaccess" ]; then
            cat > "$FCMS_PATH/$dir/.htaccess" << 'HTACCESS_EOF'
Order deny,allow
Deny from all
HTACCESS_EOF
            echo "Created .htaccess in $dir" >> $LOG_FILE
        fi
    done
}

# Run fixes
fix_permissions
fix_world_writable
create_htaccess

echo "Auto-fix completed - $(date)" >> $LOG_FILE
EOF

chmod +x /usr/local/bin/fcms-auto-fix.sh
```

## Disaster Recovery

### Permission Backup and Restore
```bash
# Create permission backup script
sudo cat > /usr/local/bin/fcms-backup-permissions.sh << 'EOF'
#!/usr/bin/env bash

FCMS_PATH="/var/www/html/fearlesscms"
BACKUP_DIR="/backup/permissions"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p "$BACKUP_DIR"

# Backup permissions and ownership
echo "Backing up FearlessCMS permissions - $DATE" > "$BACKUP_DIR/permissions_$DATE.txt"
find "$FCMS_PATH" -exec stat -c "%n %a %U %G" {} \; >> "$BACKUP_DIR/permissions_$DATE.txt"

# Backup ACLs if present
if command -v getfacl >/dev/null; then
    getfacl -R "$FCMS_PATH" > "$BACKUP_DIR/acls_$DATE.txt" 2>/dev/null
fi

# Backup SELinux contexts if present
if command -v getenforce >/dev/null && [ "$(getenforce)" != "Disabled" ]; then
    ls -Z "$FCMS_PATH" -R > "$BACKUP_DIR/selinux_$DATE.txt" 2>/dev/null
fi

echo "Permission backup completed: $BACKUP_DIR/permissions_$DATE.txt"
EOF

# Create permission restore script
sudo cat > /usr/local/bin/fcms-restore-permissions.sh << 'EOF'
#!/usr/bin/env bash

if [ $# -ne 1 ]; then
    echo "Usage: $0 <backup_file>"
    exit 1
fi

BACKUP_FILE="$1"

if [ ! -f "$BACKUP_FILE" ]; then
    echo "Backup file not found: $BACKUP_FILE"
    exit 1
fi

echo "Restoring permissions from $BACKUP_FILE"

while IFS=' ' read -r file perms user group; do
    if [ -e "$file" ]; then
        chmod "$perms" "$file" 2>/dev/null
        chown "$user:$group" "$file" 2>/dev/null
        echo "Restored: $file ($perms $user:$group)"
    fi
done < "$BACKUP_FILE"

echo "Permission restore completed"
EOF

chmod +x /usr/local/bin/fcms-backup-permissions.sh
chmod +x /usr/local/bin/fcms-restore-permissions.sh

# Schedule daily permission backups
echo "0 1 * * * /usr/local/bin/fcms-backup-permissions.sh" | crontab -
```

## Notes
- This approach is more secure than using 777/666 permissions
- Always backup before changing permissions on production systems
- The web server user must have read/write access to specific directories
- Consider using deployment scripts to automate permission setup
- Regular permission audits help maintain security
- Implement monitoring and alerting for permission changes
- Use principle of least privilege for all file access
- Consider compliance requirements for your industry
- Test security measures in staging environment first
- Document all security configurations and procedures
