# Getting Started with FearlessCMS

Welcome to FearlessCMS! This guide will walk you through the installation and initial setup process to get your CMS up and running.

## 📋 Table of Contents

- [System Requirements](#system-requirements)
- [Installation Methods](#installation-methods)
- [Initial Setup](#initial-setup)
- [Content Editing Modes](#content-editing-modes)
- [First Steps](#first-steps)
- [Configuration](#configuration)
- [Troubleshooting](#troubleshooting)

## 🖥️ System Requirements

### PHP Requirements
- **PHP Version**: 7.4 or higher (8.0+ recommended)
- **Required Extensions**:
  - `curl` - HTTP requests and API calls
  - `json` - JSON data processing
  - `mbstring` - Multibyte string handling
  - `phar` - PHAR archive support
  - `zip` - ZIP file handling
  - `openssl` - Encryption and SSL support

### Server Requirements
- **Web Server**: Apache, Nginx, or any PHP-compatible server
- **File Permissions**: Write access to project directory
- **Memory**: Minimum 128MB RAM (256MB+ recommended)
- **Disk Space**: At least 100MB free space

### Optional Dependencies
- **Node.js & npm**: Required for export functionality
- **MySQL/MariaDB**: For database plugins (optional)

## 🚀 Installation Methods

### Method 1: Automated Installer (Recommended)

The easiest way to install FearlessCMS is using the built-in installer:

1. **Download and Extract**
   ```bash
   # Download FearlessCMS
   wget https://github.com/your-repo/FearlessCMS/archive/main.zip
   unzip main.zip
   cd FearlessCMS-main
   ```

2. **Run the Installer**
   ```bash
   # Web-based installation
   # Navigate to install.php in your browser
   http://your-domain.com/install.php
   
   # Or use command-line installation
   php install.php --check
   php install.php --create-dirs
   php install.php --install-export-deps
   php install.php --create-admin=admin --password=yourpassword
   ```

3. **Complete Installation**
   - Create necessary directories
   - Install export dependencies
   - Create administrator account
   - Set up initial configuration

### Method 2: Manual Installation

For advanced users who prefer manual setup:

1. **Create Directory Structure**
   ```bash
   mkdir -p config admin/uploads uploads content sessions cache backups .fcms_updates
   ```

2. **Set Permissions**
   ```bash
   chmod 755 config admin/uploads uploads content sessions cache backups .fcms_updates
   ```

3. **Create Configuration Files**
   ```bash
   # Copy and modify example configuration files
   cp config.example/* config/
   ```

## ⚙️ Initial Setup

### 1. Directory Creation
The installer will create these essential directories:

```
FearlessCMS/
├── config/                 # Configuration files
├── admin/uploads/          # Admin file uploads
├── uploads/                # Public file uploads
├── content/                # Content files
├── sessions/               # Session storage
├── cache/                  # Cache files
├── backups/                # Backup storage
└── .fcms_updates/         # Update files
```

### 2. Configuration Files
Essential configuration files are created:

- `config/config.json` - Site configuration
- `config/users.json` - User accounts
- `config/cms_mode.json` - CMS operational mode
- `config/theme_options.json` - Theme settings

### 3. Administrator Account
Create your first admin account:

```bash
php install.php --create-admin=admin --password=securepassword123
```

**Security Note**: Use a strong, unique password!

## ✍️ Content Editing Modes

FearlessCMS supports **dual-mode content editing** to accommodate different user preferences and use cases:

### HTML Mode (Default)
- **Rich WYSIWYG Editor** - Full-featured visual editor with formatting toolbar
- **Code View Toggle** - Switch to raw HTML editing when needed
- **Advanced Layout Control** - Perfect for complex layouts and feature cards
- **No Escaping Issues** - Shortcodes work perfectly without special characters

### Markdown Support
- **External Markdown Editing** - Use your preferred Markdown editor (VS Code, Typora, etc.)
- **Version Control Friendly** - Easy to track changes and collaborate
- **Lightweight** - Fast editing for simple content
- **Backwards Compatible** - All existing Markdown content continues to work

### Key Benefits
- **User Choice** - Choose the content creation method that works best for your workflow
- **Backwards Compatibility** - Existing content works without conversion
- **Flexible Workflow** - Create HTML in CMS or upload Markdown from external editors
- **Mixed Content Support** - Both formats can coexist in the same site

For detailed information about content editing modes, see the [HTML Editor Guide](html-editor-guide.md).

## 🎯 First Steps

### 1. Access Your Site
- **Frontend**: Navigate to your domain
- **Admin Panel**: Go to `/admin` and log in

### 2. Choose CMS Mode
Select your operational mode in the admin panel:

- **Full Featured**: Complete control (self-hosted)
- **Hosting Service (Plugin Mode)**: Curated plugins, no store
- **Hosting Service (No Plugin Management)**: Restricted, pre-configured

### 3. Select a Theme
Choose from available themes or create your own:

- Navigate to Themes in admin panel
- Preview available themes
- Activate your preferred theme

### 4. Create Your First Content
- Use the Content Editor to create pages
- Add navigation menus
- Configure site settings

## 🔧 Configuration

### Site Configuration
Edit `config/config.json`:

```json
{
    "site_name": "My Awesome Site",
    "site_description": "A great website built with FearlessCMS",
    "admin_path": "admin",
    "default_theme": "default"
}
```

### CMS Mode Configuration
Edit `config/cms_mode.json`:

```json
{
    "mode": "full-featured"
}
```

**Available Modes**:
- `full-featured` - Complete access
- `hosting-service-plugins` - Plugin management only
- `hosting-service-no-plugins` - No plugin management

### Theme Options
Customize your theme in `config/theme_options.json`:

```json
{
    "logo": "/uploads/logo.png",
    "hero_banner": "/uploads/hero.jpg",
    "accent_color": "#007bff",
    "show_sidebar": true
}
```

## 🎨 Ad Area System

### Automatic Integration
The Ad Area System automatically integrates with all themes:

- **Full Featured Mode**: No ads displayed (clean experience)
- **Hosting Service Modes**: Professional ad area visible

### Customization
Edit `themes/ad-area.html` to customize:

- Ad content and messaging
- Visual design and styling
- Call-to-action buttons
- Feature highlights

### Testing
Test the ad area system:

1. Change CMS mode to hosting service mode
2. Navigate to any page
3. Verify ad area appears
4. Test across different themes

## 🔧 Troubleshooting

### Common Installation Issues

#### Permission Errors
```bash
# Check directory permissions
ls -la config/
ls -la uploads/

# Fix permissions
chmod 755 config uploads
chown www-data:www-data config uploads
```

#### PHP Extension Issues
```bash
# Check loaded extensions
php -m | grep -E "(curl|json|mbstring|phar|zip|openssl)"

# Install missing extensions (Ubuntu/Debian)
sudo apt install php-curl php-json php-mbstring php-phar php-zip php-openssl
```

#### Node.js Dependencies
```bash
# Check Node.js installation
node --version
npm --version

# Install if missing
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
sudo apt-get install -y nodejs
```

### Getting Help

1. **Check Error Logs**: Review PHP and web server error logs
2. **Verify Requirements**: Use `php install.php --check`
3. **Test Permissions**: Ensure write access to directories
4. **Community Support**: Ask questions in project forums

## 🛡️ Security Best Practices

### After Installation
1. **Delete Installer**: `rm install.php`
2. **Secure Permissions**: `chmod 644 config/*.json`
3. **Use HTTPS**: Enable SSL/TLS in production
4. **Regular Updates**: Keep PHP and dependencies updated

### Production Deployment
1. **Environment Variables**: Use `FCMS_CONFIG_DIR` for external config
2. **Firewall Rules**: Restrict admin access
3. **Backup Strategy**: Regular backups of config and content
4. **Monitoring**: Log access and error events

## 📚 Next Steps

### Learn More
- [CMS Modes Guide](cms-modes.md) - Understanding operational modes
- [Theme Development](theme-development-index.md) - Creating custom themes
- [Plugin Development](plugin-development-guide.md) - Extending functionality
- [Ad Area System](ad-area-system.md) - Customizing advertising

### Advanced Configuration
- [File Permissions Guide](file-permissions.md) - Security setup
- [Installation Script](install.md) - Advanced installation options
- [Configuration Guide](../config/README.md) - System configuration

## 🆘 Support

If you need help getting started:

1. **Check this guide** for common solutions
2. **Review error messages** for specific issues
3. **Verify system requirements** using the installer
4. **Ask the community** for additional support

---

**Welcome to FearlessCMS!** 🚀

*This documentation is maintained by the FearlessCMS community. Last updated: January 2024*
