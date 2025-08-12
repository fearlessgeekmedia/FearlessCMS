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
#!/bin/bash
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

### After Installation
1. **Delete the installer**: `rm install.php`
2. **Set proper permissions**: `chmod 644 config/*.json`
3. **Secure configuration**: Ensure config files are not publicly accessible
4. **Regular updates**: Keep PHP and dependencies updated

### Production Deployment
1. **Use HTTPS**: Always use SSL/TLS in production
2. **Firewall rules**: Restrict access to admin areas
3. **Backup strategy**: Regular backups of configuration and content
4. **Monitoring**: Log access and error events

### Access Control
1. **Strong passwords**: Use complex, unique passwords
2. **User management**: Regularly review user accounts
3. **Role-based access**: Implement appropriate permission levels
4. **Session security**: Configure secure session handling

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
