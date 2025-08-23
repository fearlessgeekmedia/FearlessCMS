# FearlessCMS Security Update and Patch Management Guide

## Table of Contents

1. [Overview](#overview)
2. [Update Classification](#update-classification)
3. [Patch Management Process](#patch-management-process)
4. [Security Update Procedures](#security-update-procedures)
5. [Automated Update Systems](#automated-update-systems)
6. [Testing and Validation](#testing-and-validation)
7. [Rollback Procedures](#rollback-procedures)
8. [Communication and Documentation](#communication-and-documentation)
9. [Compliance and Reporting](#compliance-and-reporting)

## Overview

This document outlines the comprehensive approach to security updates and patch management for FearlessCMS installations. It provides procedures, timelines, and best practices to ensure systems remain secure while maintaining stability and functionality.

### Objectives
- Maintain system security through timely updates
- Minimize security vulnerabilities and exposure windows
- Ensure update compatibility and system stability
- Provide clear procedures for emergency patching
- Maintain compliance with security standards
- Document all update activities for audit purposes

### Scope
This guide covers:
- FearlessCMS core updates
- PHP security patches
- Web server updates (Apache/Nginx)
- Operating system security updates
- Third-party plugin and theme updates
- Dependency management (Node.js packages)

## Update Classification

### Severity Levels

#### **CRITICAL (Emergency Patches)**
- **Criteria**:
  - Remotely exploitable vulnerabilities
  - Active exploitation in the wild
  - Complete system compromise potential
  - Data breach potential
- **Timeline**: Within 24 hours
- **Process**: Emergency patch procedure
- **Examples**: Remote code execution, authentication bypass

#### **HIGH (Priority Updates)**
- **Criteria**:
  - High-impact security vulnerabilities
  - Privilege escalation vulnerabilities
  - Significant data exposure risks
- **Timeline**: Within 72 hours
- **Process**: Expedited testing and deployment
- **Examples**: SQL injection, XSS with admin access

#### **MEDIUM (Scheduled Updates)**
- **Criteria**:
  - Moderate security improvements
  - Feature updates with security benefits
  - Non-critical bug fixes
- **Timeline**: Within 2 weeks
- **Process**: Standard testing cycle
- **Examples**: CSRF improvements, input validation enhancements

#### **LOW (Maintenance Updates)**
- **Criteria**:
  - Minor security improvements
  - Performance optimizations
  - Cosmetic fixes
- **Timeline**: Next maintenance window
- **Process**: Quarterly update cycle
- **Examples**: Security header improvements, logging enhancements

### Update Types

#### **Security Updates**
- Critical security patches
- Vulnerability fixes
- Security feature improvements
- Compliance updates

#### **Bug Fix Updates**
- Functionality corrections
- Stability improvements
- Performance fixes
- Compatibility updates

#### **Feature Updates**
- New functionality
- Enhanced capabilities
- User interface improvements
- Integration additions

#### **Major Version Updates**
- Significant architecture changes
- Breaking changes requiring migration
- Large feature sets
- Long-term support transitions

## Patch Management Process

### 1. Vulnerability Monitoring

#### Security Information Sources
```bash
# Automated monitoring setup
# Subscribe to security feeds
curl -s https://api.fearlesscms.org/security/feed.json | jq '.vulnerabilities[]'

# Monitor CVE databases
curl -s "https://services.nvd.nist.gov/rest/json/cves/1.0?keyword=php" | jq '.result.CVE_Items[]'

# Check for PHP security updates
curl -s https://www.php.net/releases/feed.php

# Monitor web server security
# Apache
curl -s https://httpd.apache.org/security/json/

# Nginx
curl -s https://nginx.org/en/security_advisories.rss
```

#### Automated Vulnerability Scanning
```bash
# Install vulnerability scanner
sudo apt install lynis rkhunter

# Create vulnerability scan script
cat > /usr/local/bin/fcms-vuln-scan.sh << 'EOF'
#!/usr/bin/env bash

SCAN_DATE=$(date +%Y%m%d_%H%M%S)
REPORT_DIR="/var/log/security-scans"
FCMS_PATH="/var/www/html/fearlesscms"

mkdir -p "$REPORT_DIR"

echo "FearlessCMS Vulnerability Scan - $SCAN_DATE" > "$REPORT_DIR/vuln-scan-$SCAN_DATE.log"

# Scan FearlessCMS installation
echo "Scanning FearlessCMS core..." >> "$REPORT_DIR/vuln-scan-$SCAN_DATE.log"
lynis audit system --auditor "Security Team" --report-file "$REPORT_DIR/lynis-$SCAN_DATE.log"

# Check for outdated packages
echo "Checking for outdated packages..." >> "$REPORT_DIR/vuln-scan-$SCAN_DATE.log"
apt list --upgradable 2>/dev/null >> "$REPORT_DIR/vuln-scan-$SCAN_DATE.log"

# Scan for malware
echo "Scanning for malware..." >> "$REPORT_DIR/vuln-scan-$SCAN_DATE.log"
clamscan -r "$FCMS_PATH" --log="$REPORT_DIR/malware-scan-$SCAN_DATE.log"

# Check file permissions
echo "Checking file permissions..." >> "$REPORT_DIR/vuln-scan-$SCAN_DATE.log"
find "$FCMS_PATH" -type f -perm /o+w >> "$REPORT_DIR/vuln-scan-$SCAN_DATE.log"

# Generate summary
echo "Vulnerability scan completed at $(date)" >> "$REPORT_DIR/vuln-scan-$SCAN_DATE.log"

# Send alert if issues found
if grep -q "WARNING\|ERROR\|CRITICAL" "$REPORT_DIR/vuln-scan-$SCAN_DATE.log"; then
    mail -s "FearlessCMS Vulnerability Scan Alert" security@yourdomain.com < "$REPORT_DIR/vuln-scan-$SCAN_DATE.log"
fi
EOF

chmod +x /usr/local/bin/fcms-vuln-scan.sh

# Schedule weekly scans
echo "0 2 * * 1 /usr/local/bin/fcms-vuln-scan.sh" | crontab -
```

### 2. Update Assessment

#### Impact Analysis Matrix
```
| Component      | Low Impact | Medium Impact | High Impact | Critical Impact |
|----------------|------------|---------------|-------------|-----------------|
| Core Files     | Feature    | Bug Fix       | Security    | Critical Vuln   |
| Configuration  | Cosmetic   | Functionality | Access      | Authentication  |
| Database       | Logging    | Performance   | Integrity   | Data Loss       |
| Dependencies   | Minor      | Compatibility | Security    | Major Vuln      |
```

#### Risk Assessment Process
```bash
# Create risk assessment script
cat > /usr/local/bin/fcms-risk-assessment.sh << 'EOF'
#!/usr/bin/env bash

UPDATE_TYPE="$1"
COMPONENT="$2"
VULNERABILITY_TYPE="$3"

case "$VULNERABILITY_TYPE" in
    "rce"|"remote_code_execution")
        RISK_LEVEL="CRITICAL"
        UPDATE_TIMELINE="24_hours"
        ;;
    "sqli"|"sql_injection"|"auth_bypass")
        RISK_LEVEL="HIGH"
        UPDATE_TIMELINE="72_hours"
        ;;
    "xss"|"csrf"|"info_disclosure")
        RISK_LEVEL="MEDIUM"
        UPDATE_TIMELINE="2_weeks"
        ;;
    *)
        RISK_LEVEL="LOW"
        UPDATE_TIMELINE="next_maintenance"
        ;;
esac

echo "Component: $COMPONENT"
echo "Update Type: $UPDATE_TYPE"
echo "Vulnerability: $VULNERABILITY_TYPE"
echo "Risk Level: $RISK_LEVEL"
echo "Timeline: $UPDATE_TIMELINE"

# Log assessment
echo "$(date): $COMPONENT - $VULNERABILITY_TYPE - $RISK_LEVEL - $UPDATE_TIMELINE" >> /var/log/update-assessments.log
EOF

chmod +x /usr/local/bin/fcms-risk-assessment.sh
```

### 3. Change Management

#### Change Request Template
```bash
# Create change request form
cat > /usr/local/bin/fcms-change-request.sh << 'EOF'
#!/usr/bin/env bash

echo "FearlessCMS Change Request Form"
echo "==============================="
echo
read -p "Change ID: " CHANGE_ID
read -p "Requestor: " REQUESTOR
read -p "Change Type (security/bug/feature): " CHANGE_TYPE
read -p "Component: " COMPONENT
read -p "Priority (critical/high/medium/low): " PRIORITY
read -p "Description: " DESCRIPTION
read -p "Business Justification: " JUSTIFICATION
read -p "Implementation Date: " IMPL_DATE
read -p "Rollback Plan: " ROLLBACK_PLAN

# Create change record
CHANGE_FILE="/var/log/change-requests/CR-$CHANGE_ID.txt"
mkdir -p /var/log/change-requests

cat > "$CHANGE_FILE" << CR_EOF
FearlessCMS Change Request
=========================
Change ID: $CHANGE_ID
Date Created: $(date)
Requestor: $REQUESTOR
Change Type: $CHANGE_TYPE
Component: $COMPONENT
Priority: $PRIORITY

Description:
$DESCRIPTION

Business Justification:
$JUSTIFICATION

Implementation Details:
- Planned Date: $IMPL_DATE
- Rollback Plan: $ROLLBACK_PLAN

Status: PENDING_APPROVAL

Approval Chain:
[ ] Security Team
[ ] Technical Lead
[ ] Change Advisory Board
[ ] Business Owner

Implementation Checklist:
[ ] Backup created
[ ] Testing completed
[ ] Documentation updated
[ ] Monitoring configured
[ ] Rollback tested
CR_EOF

echo "Change request created: $CHANGE_FILE"
echo "Please submit for approval through proper channels."
EOF

chmod +x /usr/local/bin/fcms-change-request.sh
```

## Security Update Procedures

### Emergency Patch Procedure (Critical Updates)

#### Phase 1: Immediate Response (0-2 hours)
```bash
# Emergency patch deployment script
cat > /usr/local/bin/fcms-emergency-patch.sh << 'EOF'
#!/usr/bin/env bash

PATCH_ID="$1"
PATCH_FILE="$2"

if [ $# -ne 2 ]; then
    echo "Usage: $0 <patch_id> <patch_file>"
    exit 1
fi

FCMS_PATH="/var/www/html/fearlesscms"
BACKUP_DIR="/backup/emergency-patches"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "Emergency Patch Deployment: $PATCH_ID"
echo "Timestamp: $TIMESTAMP"

# Create emergency backup
echo "Creating emergency backup..."
mkdir -p "$BACKUP_DIR"
tar -czf "$BACKUP_DIR/pre-patch-$PATCH_ID-$TIMESTAMP.tar.gz" -C "$FCMS_PATH" .

# Verify patch file
if [ ! -f "$PATCH_FILE" ]; then
    echo "ERROR: Patch file not found: $PATCH_FILE"
    exit 1
fi

# Apply patch
echo "Applying emergency patch..."
if [[ "$PATCH_FILE" == *.tar.gz ]]; then
    tar -xzf "$PATCH_FILE" -C "$FCMS_PATH"
elif [[ "$PATCH_FILE" == *.zip ]]; then
    unzip -o "$PATCH_FILE" -d "$FCMS_PATH"
elif [[ "$PATCH_FILE" == *.patch ]]; then
    patch -p1 -d "$FCMS_PATH" < "$PATCH_FILE"
else
    echo "ERROR: Unsupported patch format"
    exit 1
fi

# Fix permissions
chown -R www-data:www-data "$FCMS_PATH"
chmod 700 "$FCMS_PATH/config" "$FCMS_PATH/sessions"
chmod 600 "$FCMS_PATH/config"/*.json

# Test basic functionality
echo "Testing basic functionality..."
curl -f -s http://localhost/ > /dev/null
if [ $? -eq 0 ]; then
    echo "SUCCESS: Website responding"
else
    echo "ERROR: Website not responding - initiating rollback"
    tar -xzf "$BACKUP_DIR/pre-patch-$PATCH_ID-$TIMESTAMP.tar.gz" -C "$FCMS_PATH"
    exit 1
fi

# Log emergency patch
echo "$(date): Emergency patch $PATCH_ID applied successfully" >> /var/log/emergency-patches.log

echo "Emergency patch $PATCH_ID deployed successfully"
echo "Backup stored at: $BACKUP_DIR/pre-patch-$PATCH_ID-$TIMESTAMP.tar.gz"
EOF

chmod +x /usr/local/bin/fcms-emergency-patch.sh
```

#### Phase 2: Validation and Monitoring (2-24 hours)
```bash
# Post-patch validation script
cat > /usr/local/bin/fcms-post-patch-validation.sh << 'EOF'
#!/usr/bin/env bash

PATCH_ID="$1"
FCMS_PATH="/var/www/html/fearlesscms"

echo "Post-Patch Validation: $PATCH_ID"
echo "================================="

# Test checklist
TESTS_PASSED=0
TESTS_TOTAL=0

run_test() {
    local test_name="$1"
    local test_command="$2"
    
    ((TESTS_TOTAL++))
    echo -n "Testing $test_name... "
    
    if eval "$test_command" > /dev/null 2>&1; then
        echo "PASS"
        ((TESTS_PASSED++))
    else
        echo "FAIL"
    fi
}

# Core functionality tests
run_test "Website accessibility" "curl -f -s http://localhost/"
run_test "Admin interface" "curl -f -s http://localhost/admin/"
run_test "Login functionality" "curl -f -s http://localhost/login.php"
run_test "File permissions" "[ \$(stat -c '%a' $FCMS_PATH/config) = '700' ]"
run_test "Configuration integrity" "[ -f $FCMS_PATH/config/config.json ]"
run_test "Session handling" "[ -d $FCMS_PATH/sessions ]"
run_test "Upload functionality" "[ -w $FCMS_PATH/uploads ]"

# Security tests
run_test "Config file protection" "! curl -f -s http://localhost/config/config.json"
run_test "Session protection" "! curl -f -s http://localhost/sessions/"
run_test "Admin authentication" "curl -s http://localhost/admin/ | grep -q 'login'"

# Performance tests
RESPONSE_TIME=$(curl -o /dev/null -s -w '%{time_total}' http://localhost/)
if (( $(echo "$RESPONSE_TIME < 5.0" | bc -l) )); then
    echo "Performance test... PASS (${RESPONSE_TIME}s)"
    ((TESTS_PASSED++))
else
    echo "Performance test... FAIL (${RESPONSE_TIME}s)"
fi
((TESTS_TOTAL++))

# Results
echo ""
echo "Validation Results:"
echo "Tests Passed: $TESTS_PASSED/$TESTS_TOTAL"

if [ $TESTS_PASSED -eq $TESTS_TOTAL ]; then
    echo "STATUS: VALIDATION SUCCESSFUL"
    echo "$(date): Patch $PATCH_ID validation successful ($TESTS_PASSED/$TESTS_TOTAL)" >> /var/log/patch-validation.log
    exit 0
else
    echo "STATUS: VALIDATION FAILED"
    echo "$(date): Patch $PATCH_ID validation failed ($TESTS_PASSED/$TESTS_TOTAL)" >> /var/log/patch-validation.log
    exit 1
fi
EOF

chmod +x /usr/local/bin/fcms-post-patch-validation.sh
```

### Standard Update Procedure

#### Pre-Update Checklist
```bash
# Pre-update checklist script
cat > /usr/local/bin/fcms-pre-update-check.sh << 'EOF'
#!/usr/bin/env bash

FCMS_PATH="/var/www/html/fearlesscms"
CHECK_PASSED=0
CHECK_TOTAL=0

check_item() {
    local item_name="$1"
    local check_command="$2"
    
    ((CHECK_TOTAL++))
    echo -n "Checking $item_name... "
    
    if eval "$check_command" > /dev/null 2>&1; then
        echo "OK"
        ((CHECK_PASSED++))
    else
        echo "FAIL"
    fi
}

echo "FearlessCMS Pre-Update Checklist"
echo "================================="

# System health checks
check_item "Disk space (>1GB free)" "[ \$(df / | awk 'NR==2 {print \$4}') -gt 1048576 ]"
check_item "Memory usage (<80%)" "[ \$(free | awk 'NR==2{printf \"%.0f\", \$3/\$2*100}') -lt 80 ]"
check_item "System load (<2.0)" "[ \$(uptime | awk '{print \$10}' | cut -d, -f1) \< 2.0 ]"

# Service status checks
check_item "Apache/Nginx running" "systemctl is-active apache2 || systemctl is-active nginx"
check_item "PHP-FPM running" "systemctl is-active php7.4-fpm || systemctl is-active php8.0-fpm"
check_item "Database accessible" "mysql -e 'SELECT 1' 2>/dev/null || echo 'No DB'"

# FearlessCMS health checks
check_item "Website responding" "curl -f -s http://localhost/"
check_item "Admin interface accessible" "curl -f -s http://localhost/admin/"
check_item "Configuration files present" "[ -f $FCMS_PATH/config/config.json ]"
check_item "File permissions correct" "[ \$(stat -c '%a' $FCMS_PATH/config) = '700' ]"

# Backup verification
check_item "Recent backup available" "[ -f /backup/latest-backup.tar.gz ]"
check_item "Backup less than 24h old" "[ \$(find /backup -name 'latest-backup.tar.gz' -mtime -1 | wc -l) -gt 0 ]"

# Update readiness
check_item "No active users" "[ \$(who | wc -l) -lt 3 ]"
check_item "Maintenance window" "[ \$(date +%H) -ge 2 ] && [ \$(date +%H) -le 6 ]"

echo ""
echo "Pre-Update Check Results:"
echo "Checks Passed: $CHECK_PASSED/$CHECK_TOTAL"

if [ $CHECK_PASSED -eq $CHECK_TOTAL ]; then
    echo "STATUS: READY FOR UPDATE"
    exit 0
else
    echo "STATUS: NOT READY FOR UPDATE"
    echo "Please resolve failed checks before proceeding."
    exit 1
fi
EOF

chmod +x /usr/local/bin/fcms-pre-update-check.sh
```

#### Update Execution
```bash
# Standard update script
cat > /usr/local/bin/fcms-standard-update.sh << 'EOF'
#!/usr/bin/env bash

UPDATE_TYPE="$1"
UPDATE_SOURCE="$2"

if [ $# -ne 2 ]; then
    echo "Usage: $0 <security|feature|bug> <update_source>"
    exit 1
fi

FCMS_PATH="/var/www/html/fearlesscms"
BACKUP_DIR="/backup/updates"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
UPDATE_LOG="/var/log/fcms-updates.log"

echo "FearlessCMS Standard Update Process"
echo "==================================="
echo "Type: $UPDATE_TYPE"
echo "Source: $UPDATE_SOURCE"
echo "Timestamp: $TIMESTAMP"

# Log update start
echo "$(date): Starting $UPDATE_TYPE update from $UPDATE_SOURCE" >> "$UPDATE_LOG"

# Pre-update checks
echo "Running pre-update checks..."
if ! /usr/local/bin/fcms-pre-update-check.sh; then
    echo "ERROR: Pre-update checks failed"
    exit 1
fi

# Create backup
echo "Creating backup..."
mkdir -p "$BACKUP_DIR"
tar -czf "$BACKUP_DIR/pre-update-$TIMESTAMP.tar.gz" -C "$FCMS_PATH" .

# Put site in maintenance mode
echo "Enabling maintenance mode..."
touch "$FCMS_PATH/maintenance.lock"

# Download and verify update
echo "Downloading update..."
if [[ "$UPDATE_SOURCE" == http* ]]; then
    wget -O "/tmp/fcms-update-$TIMESTAMP.zip" "$UPDATE_SOURCE"
    UPDATE_FILE="/tmp/fcms-update-$TIMESTAMP.zip"
elif [ -f "$UPDATE_SOURCE" ]; then
    cp "$UPDATE_SOURCE" "/tmp/fcms-update-$TIMESTAMP.zip"
    UPDATE_FILE="/tmp/fcms-update-$TIMESTAMP.zip"
else
    echo "ERROR: Invalid update source"
    rm "$FCMS_PATH/maintenance.lock"
    exit 1
fi

# Verify update integrity (if checksums available)
if [ -f "$UPDATE_FILE.sha256" ]; then
    echo "Verifying update integrity..."
    if ! sha256sum -c "$UPDATE_FILE.sha256"; then
        echo "ERROR: Update integrity check failed"
        rm "$FCMS_PATH/maintenance.lock"
        exit 1
    fi
fi

# Apply update
echo "Applying update..."
if [[ "$UPDATE_FILE" == *.zip ]]; then
    unzip -o "$UPDATE_FILE" -d "$FCMS_PATH"
elif [[ "$UPDATE_FILE" == *.tar.gz ]]; then
    tar -xzf "$UPDATE_FILE" -C "$FCMS_PATH"
else
    echo "ERROR: Unsupported update format"
    rm "$FCMS_PATH/maintenance.lock"
    exit 1
fi

# Fix permissions
echo "Fixing permissions..."
chown -R www-data:www-data "$FCMS_PATH"
chmod 700 "$FCMS_PATH/config" "$FCMS_PATH/sessions"
chmod 600 "$FCMS_PATH/config"/*.json

# Run database migrations (if applicable)
if [ -f "$FCMS_PATH/migrate.php" ]; then
    echo "Running database migrations..."
    php "$FCMS_PATH/migrate.php"
fi

# Clear cache
echo "Clearing cache..."
rm -rf "$FCMS_PATH/cache"/*

# Disable maintenance mode
echo "Disabling maintenance mode..."
rm "$FCMS_PATH/maintenance.lock"

# Post-update validation
echo "Running post-update validation..."
if /usr/local/bin/fcms-post-patch-validation.sh "standard-update-$TIMESTAMP"; then
    echo "SUCCESS: Update completed successfully"
    echo "$(date): $UPDATE_TYPE update completed successfully" >> "$UPDATE_LOG"
else
    echo "ERROR: Post-update validation failed - initiating rollback"
    echo "$(date): $UPDATE_TYPE update failed validation - rolling back" >> "$UPDATE_LOG"
    
    # Rollback
    rm -rf "$FCMS_PATH"/*
    tar -xzf "$BACKUP_DIR/pre-update-$TIMESTAMP.tar.gz" -C "$FCMS_PATH"
    chown -R www-data:www-data "$FCMS_PATH"
    
    echo "Rollback completed"
    exit 1
fi

# Cleanup
rm -f "$UPDATE_FILE"

echo "Update process completed successfully"
EOF

chmod +x /usr/local/bin/fcms-standard-update.sh
```

## Automated Update Systems

### Update Monitoring Service
```bash
# Create update monitoring daemon
cat > /usr/local/bin/fcms-update-monitor.sh << 'EOF'
#!/usr/bin/env bash

CONFIG_FILE="/etc/fcms/update-monitor.conf"
PID_FILE="/var/run/fcms-update-monitor.pid"
LOG_FILE="/var/log/fcms-update-monitor.log"

# Default configuration
CHECK_INTERVAL=3600  # 1 hour
AUTO_SECURITY_UPDATES=true
AUTO_MINOR_UPDATES=false
AUTO_MAJOR_UPDATES=false
MAINTENANCE_WINDOW_START=2
MAINTENANCE_WINDOW_END=6

# Load configuration
if [ -f "$CONFIG_FILE" ]; then
    source "$CONFIG_FILE"
fi

log_message() {
    echo "$(date): $1" >> "$LOG_FILE"
}

check_updates() {
    log_message "Checking for updates..."
    
    # Check FearlessCMS core updates
    CURRENT_VERSION=$(php -r "include '/var/www/html/fearlesscms/version.php'; echo FCMS_VERSION;")
    LATEST_VERSION=$(curl -s https://api.fearlesscms.org/version/latest)
    
    if [ "$CURRENT_VERSION" != "$LATEST_VERSION" ]; then
        log_message "Update available: $CURRENT_VERSION -> $LATEST_VERSION"
        
        # Get update information
        UPDATE_INFO=$(curl -s "https://api.fearlesscms.org/version/$LATEST_VERSION/info")
        UPDATE_TYPE=$(echo "$UPDATE_INFO" | jq -r '.type')
        SECURITY_UPDATE=$(echo "$UPDATE_INFO" | jq -r '.security')
        
        # Determine if auto-update should proceed
        AUTO_UPDATE=false
        case "$UPDATE_TYPE" in
            "security")
                [ "$AUTO_SECURITY_UPDATES" = "true" ] && AUTO_UPDATE=true
                ;;
            "minor")
                [ "$AUTO_MINOR_UPDATES" = "true" ] && AUTO_UPDATE=true
                ;;
            "major")
                [ "$AUTO_MAJOR_UPDATES" = "true" ] && AUTO_UPDATE=true
                ;;
        esac
        
        if [ "$AUTO_UPDATE" = "true" ]; then
            # Check if in maintenance window
            CURRENT_HOUR=$(date +%H)
            if [ "$CURRENT_HOUR" -ge "$MAINTENANCE_WINDOW_START" ] && [ "$CURRENT_HOUR" -le "$MAINTENANCE_WINDOW_END" ]; then
                log_message "Initiating auto-update to $LATEST_VERSION"
                
                # Download update
                UPDATE_URL=$(echo "$UPDATE_INFO" | jq -r '.download_url')
                if /usr/local/bin/fcms-standard-update.sh "$UPDATE_TYPE" "$UPDATE_URL"; then
                    log_message "Auto-update completed successfully"
                    
                    # Send success notification
                    echo "FearlessCMS auto-update completed successfully: $CURRENT_VERSION -> $LATEST_VERSION" | \
                        mail -s "FearlessCMS Auto-Update Success" admin@yourdomain.com
                else
                    log_message "Auto-update failed"
                    
                    # Send failure notification
                    echo "FearlessCMS auto-update failed: $CURRENT_VERSION -> $LATEST_VERSION" | \
                        mail -s "FearlessCMS Auto-Update Failure" admin@yourdomain.com
                fi
            else
                log_message "Update available but outside maintenance window"
                
                # Schedule update for next maintenance window
                echo "FearlessCMS update available: $CURRENT_VERSION -> $LATEST_VERSION (Type: $UPDATE_TYPE)" | \
                    mail -s "FearlessCMS Update Available" admin@yourdomain.com
            fi
        else
            log_message "Manual update required for $UPDATE_TYPE update"
            
            # Send manual update notification
            echo "FearlessCMS manual update required: $CURRENT_VERSION -> $LATEST_VERSION (Type: $UPDATE_TYPE)" | \
                mail -s "FearlessCMS Manual Update Required" admin@yourdomain.com
        fi
    else
        log_message "System is up to date (version: $CURRENT_VERSION)"
    fi
    
    # Check for PHP security updates
    check_php_updates
    
    # Check for system security updates
    check_system_updates
}

check_php_updates() {
    log_message "Checking PHP security updates..."
    
    # Check for available PHP updates
    PHP_UPDATES=$(apt list --upgradable 2>/dev/null | grep php | grep -i security)
    
    if [ -n "$PHP_UPDATES" ]; then
        log_message "PHP security updates available"
        
        if [ "$AUTO_SECURITY_UPDATES" = "true" ]; then
            log_message "Applying PHP security updates..."
            apt update && apt upgrade -y php*
            systemctl restart php7.4-fpm php8.0-fpm 2>/dev/null
            log_message "PHP security updates applied"
        else
            echo "PHP security updates available:\n$PHP_UPDATES" | \
                mail -s "PHP Security Updates Available" admin@yourdomain.com
        fi
    fi
}

check_system_updates() {
    log_message "Checking system security updates..."
    
    # Check for security updates
    SECURITY_UPDATES=$(apt list --upgradable 2>/dev/null | grep -i security)
    
    if [ -n "$SECURITY_UPDATES" ]; then
        log_message "System security updates available"
        
        if [ "$AUTO_SECURITY_UPDATES" = "true" ]; then
            log_message "Applying system security updates..."
            DEBIAN_FRONTEND=noninteractive apt update
            DEBIAN_FRONTEND=noninteractive apt upgrade -y
            log_message "System security updates applied"
            
            # Check if reboot required
            if [ -f /var/run/reboot-required ]; then
                log_message "System reboot required after updates"
                echo "System reboot required after security updates" | \
                    mail -s "System Reboot Required" admin@yourdomain.com
            fi
        else
            echo "System security updates available:\n$SECURITY_UPDATES" | \
                mail -s "System Security Updates Available" admin@yourdomain.com
        fi
    fi
}

# Main monitoring loop
main_loop() {
    log_message "Update monitor started (PID: $$)"
    echo $$ > "$PID_FILE"
    
    while true; do
        check_updates
        sleep "$CHECK_INTERVAL"
    done
}

# Signal handlers
cleanup() {
    log_message "Update monitor stopping"
    rm -f "$PID_FILE"
    exit 0
}

trap cleanup TERM INT

# Start monitoring
case "$1" in
    start)
        if [ -f "$PID_FILE" ] && kill -0 "$(cat "$PID_FILE")" 2>/dev/null; then
            echo "Update monitor already running"
            exit 1
        fi
        main_loop &
        ;;
    stop)
        if [ -f "$PID_FILE" ]; then
            kill "$(cat "$PID_FILE")"
            rm -f "$PID_FILE"
            echo "Update monitor stopped"
        else
            echo "Update monitor not running"
        fi
        ;;
    status)
        if [ -f "$PID_FILE" ] && kill -0 "$(cat "$PID_FILE")" 2>/dev/null; then
            echo "Update monitor running (PID: $(cat "$PID_FILE"))"
        else
            echo "Update monitor not running"
        fi
        ;;
    check)
        check_updates
        ;;
    *)
        echo "Usage: $0 {start|stop|status|check}"
        exit 1
        ;;
esac
EOF

chmod +x /usr/local/bin/fcms-update-monitor.sh

# Create systemd service
cat > /etc/systemd/system/fcms-update-monitor.service << 'EOF'
[Unit]
Description=FearlessCMS Update Monitor
After=network.target

[Service]
Type=forking
ExecStart=/usr/local/bin/fcms-update-monitor.sh start
ExecStop=/usr/local/bin/fcms-update-monitor.sh stop
PIDFile=/var/run/fcms-update-monitor.pid
Restart=always
User=root

[Install]
WantedBy