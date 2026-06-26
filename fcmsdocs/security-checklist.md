# FearlessCMS Security Deployment Checklist

This checklist ensures that FearlessCMS is deployed securely in production environments.

## 🔒 Pre-Deployment Security Setup

### 1. Environment Configuration
- [ ] Set `FCMS_DEBUG=false` in production environment
- [ ] Configure `FCMS_CONFIG_DIR` to a secure location outside web root
- [ ] Set up proper environment variables for sensitive data
- [ ] Ensure PHP error reporting is disabled in production (`display_errors=0`)

### 2. File System Security
- [ ] Set restrictive permissions on config directory: `chmod 700 config/`
- [ ] Secure configuration files: `chmod 600 config/*.json`
- [ ] Protect session directory: `chmod 700 sessions/`
- [ ] Set proper upload directory permissions: `chmod 755 uploads/`
- [ ] Ensure uploaded files have safe permissions: `chmod 644 uploads/*`

### 3. Web Server Configuration
- [ ] Enable HTTPS/SSL in production
- [ ] Configure SSL/TLS with strong ciphers (TLS 1.2+)
- [ ] Block direct access to sensitive directories:
  - [ ] `/config/`
  - [ ] `/sessions/`
  - [ ] `/backups/`
  - [ ] `/.git/` (if present)
- [ ] Set up proper error pages (don't expose system information)

## 🛡️ Initial Security Configuration

### 4. Admin Account Setup
- [ ] Run `install.php` to create initial admin account
- [ ] Use strong password (minimum 8 characters, mixed case, numbers)
- [ ] Delete or disable any default accounts
- [ ] Verify no default credentials remain in codebase

### 5. User Management
- [ ] Create user accounts with principle of least privilege
- [ ] Assign appropriate roles (admin, editor, author)
- [ ] Review and customize permission sets
- [ ] Set up user account policies

### 6. Content Security
- [ ] Review and configure allowed file upload types
- [ ] Set appropriate file size limits
- [ ] Test file upload security measures
- [ ] Configure content directory permissions

## 🔍 Security Validation Tests

### 7. Authentication Testing
- [ ] Test login rate limiting (try 6+ failed attempts)
- [ ] Verify CSRF protection on all forms
- [ ] Test session timeout functionality
- [ ] Verify logout properly destroys sessions
- [ ] Test password strength requirements

### 8. Access Control Testing
- [ ] Test path traversal protection (`../` attempts)
- [ ] Verify admin area requires authentication
- [ ] Test role-based access controls
- [ ] Verify file upload restrictions work

### 9. Security Headers Verification
Use browser developer tools or security scanner to verify:
- [ ] `X-Frame-Options: DENY`
- [ ] `X-Content-Type-Options: nosniff`
- [ ] `X-XSS-Protection: 1; mode=block`
- [ ] `Content-Security-Policy` is present
- [ ] `Referrer-Policy` is configured

## 📊 Monitoring and Logging Setup

### 10. Security Monitoring
- [ ] Set up log monitoring for:
  - [ ] Failed login attempts
  - [ ] Admin access patterns
  - [ ] File upload activities
  - [ ] Error logs
- [ ] Configure log rotation to prevent disk space issues
- [ ] Set up alerts for suspicious activities

### 11. Backup Security
- [ ] Configure regular backups
- [ ] Test backup restoration process
- [ ] Secure backup storage location
- [ ] Encrypt sensitive backup data

## 🔄 Ongoing Security Maintenance

### 12. Regular Security Tasks
- [ ] Schedule monthly security reviews
- [ ] Monitor for security updates
- [ ] Review user accounts and permissions quarterly
- [ ] Audit file uploads and content regularly
- [ ] Check for suspicious log entries weekly

### 13. Incident Response Plan
- [ ] Document incident response procedures
- [ ] Identify security contact persons
- [ ] Plan for security breach scenarios
- [ ] Test incident response procedures

## 🌐 Production Environment Security

### 14. Server Hardening
- [ ] Keep PHP updated to latest stable version
- [ ] Disable unnecessary PHP modules
- [ ] Configure PHP security settings:
  - [ ] `allow_url_fopen = Off`
  - [ ] `allow_url_include = Off`
  - [ ] `expose_php = Off`
  - [ ] `display_errors = Off`
- [ ] Keep web server software updated

### 15. Network Security
- [ ] Configure firewall rules (allow only necessary ports)
- [ ] Set up intrusion detection if available
- [ ] Use fail2ban or similar for brute force protection
- [ ] Consider CDN/DDoS protection for public sites

## ⚠️ Security Red Flags to Watch For

### Immediate Action Required If Found:
- [ ] Default passwords still in use
- [ ] Debug mode enabled in production
- [ ] Sensitive files accessible via web browser
- [ ] Error messages exposing system information
- [ ] Unprotected admin interfaces
- [ ] Missing HTTPS on login pages

## 📋 Quick Security Verification Commands

### File Permissions Check:
```bash
# Check config directory permissions
ls -la config/
# Should show: drwx------ (700)

# Check session directory permissions  
ls -la sessions/
# Should show: drwx------ (700)

# Check config file permissions
ls -la config/*.json
# Should show: -rw------- (600)
```

### Log Monitoring:
```bash
# Monitor failed logins
tail -f error.log | grep "Failed login"

# Check for suspicious file access
tail -f access.log | grep -E "(\.\.\/|config\/|sessions\/)"
```

## 🆘 Emergency Procedures

### If Security Breach Suspected:
1. **Immediate Actions:**
   - [ ] Change all admin passwords
   - [ ] Review recent admin access logs
   - [ ] Check for unauthorized file changes
   - [ ] Disable affected user accounts

2. **Investigation:**
   - [ ] Analyze log files for attack patterns
   - [ ] Check file integrity
   - [ ] Review recent uploads
   - [ ] Identify attack vectors

3. **Recovery:**
   - [ ] Restore from clean backup if necessary
   - [ ] Apply security patches
   - [ ] Update all passwords
   - [ ] Re-validate security configuration

## 📞 Security Contacts

- **System Administrator:** _[Add contact info]_
- **Security Team:** _[Add contact info]_
- **Hosting Provider:** _[Add contact info]_
- **Emergency Contact:** _[Add contact info]_

---

**Last Updated:** February 2026  
**Version:** 1.1  
**Review Date:** May 2026  

> **Note:** This checklist should be reviewed and updated regularly as the system evolves and new security best practices emerge.