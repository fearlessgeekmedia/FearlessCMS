# CMS Modes Guide

FearlessCMS supports three different operational modes to accommodate various deployment scenarios. This guide explains how to use and configure these modes.

## 📋 Table of Contents

- [Overview](#overview)
- [Available Modes](#available-modes)
- [Changing Modes](#changing-modes)
- [Use Cases](#use-cases)
- [Configuration](#configuration)
- [Troubleshooting](#troubleshooting)

## 🎯 Overview

CMS modes allow you to control the level of access users have to plugin management and the plugin store. This is particularly useful for hosting services that want to provide different levels of functionality to their users.

### Key Benefits

- **Flexible Deployment**: Choose the right mode for your hosting scenario
- **Security**: Restrict access to sensitive operations
- **User Experience**: Provide appropriate interfaces based on user needs
- **Easy Management**: Change modes through the admin interface

## 🔧 Available Modes

### 1. Full Featured Mode (Default)

**Best for**: Self-hosted installations, development environments

**Permissions**:
- ✅ Plugin management (install, activate, deactivate, delete)
- ✅ Plugin store access
- ✅ All admin features
- ✅ Complete control over the CMS

**Use Case**: When you want users to have complete control over their CMS installation.

### 2. Hosting Service (Plugin Mode)

**Best for**: Hosting services with curated plugin selections

**Permissions**:
- ✅ Plugin management (activate, deactivate existing plugins)
- ❌ Plugin store access
- ❌ Plugin installation
- ❌ Plugin deletion

**Use Case**: When you want to pre-install a curated selection of plugins and allow users to manage them.

### 3. Hosting Service (No Plugin Management)

**Best for**: Highly restricted hosting environments

**Permissions**:
- ❌ Plugin management
- ❌ Plugin store access
- ❌ Plugin installation
- ❌ Plugin activation/deactivation
- ❌ Plugin deletion

**Use Case**: When you want to pre-install and activate specific plugins with no user modification allowed.

## 🔄 Changing Modes

### Through the Admin Interface

1. **Log into the Admin Panel**
   ```
   http://your-site.com/admin
   ```

2. **Navigate to CMS Mode**
   - Click on "CMS Mode" in the admin navigation menu
   - This will take you to the CMS Mode management page

3. **Select Your Desired Mode**
   - You'll see three cards representing each mode
   - Each card shows the mode name, description, and current status
   - The current mode will be highlighted in blue

4. **Change the Mode**
   - Click the radio button next to your desired mode
   - Click the "Change Mode" button
   - Confirm the change when prompted

5. **Verify the Change**
   - The page will refresh and show the new mode
   - Check that the permissions have updated correctly

### Programmatic Mode Changes

You can also change modes programmatically:

```php
// Initialize the CMS mode manager
$cmsModeManager = new CMSModeManager();

// Change to hosting service plugin mode
$cmsModeManager->setMode('hosting-service-plugins');

// Change to no plugin management mode
$cmsModeManager->setMode('hosting-service-no-plugins');

// Restore to full featured mode
$cmsModeManager->setMode('full-featured');
```

### Configuration File

The mode is stored in `config/cms_mode.json`:

```json
{
    "mode": "full-featured"
}
```

You can manually edit this file, but using the admin interface is recommended.

## 🎯 Use Cases

### For Self-Hosted Installations

**Recommended Mode**: Full Featured

```bash
# Default configuration - no changes needed
# Users have complete control over their installation
```

### For Hosting Services

**Step 1**: Install and configure desired plugins
```bash
# Install plugins you want to offer
# Configure them appropriately
# Test functionality
```

**Step 2**: Choose appropriate mode

**Option A**: Allow plugin management
```bash
# Set mode to "hosting-service-plugins"
# Users can activate/deactivate pre-installed plugins
# No access to store or installation
```

**Option B**: No plugin management
```bash
# Set mode to "hosting-service-no-plugins"
# Users cannot modify plugins at all
# Maximum security and stability
```

### For Development Environments

**Recommended Mode**: Full Featured

```bash
# Keep full access for development and testing
# Switch to restricted modes only for production
```

## ⚙️ Configuration

### Checking Current Mode

```php
$cmsModeManager = new CMSModeManager();
echo "Current Mode: " . $cmsModeManager->getCurrentMode();
echo "Mode Name: " . $cmsModeManager->getModeName();
echo "Mode Description: " . $cmsModeManager->getModeDescription();
```

### Checking Permissions

```php
$cmsModeManager = new CMSModeManager();

// Check specific permissions
if ($cmsModeManager->canAccessStore()) {
    echo "Store access is allowed";
}

if ($cmsModeManager->canManagePlugins()) {
    echo "Plugin management is allowed";
}

if ($cmsModeManager->canActivatePlugins()) {
    echo "Plugin activation is allowed";
}

if ($cmsModeManager->canDeactivatePlugins()) {
    echo "Plugin deactivation is allowed";
}

if ($cmsModeManager->canInstallPlugins()) {
    echo "Plugin installation is allowed";
}

if ($cmsModeManager->canDeletePlugins()) {
    echo "Plugin deletion is allowed";
}
```

### Getting All Permissions

```php
$cmsModeManager = new CMSModeManager();
$permissions = $cmsModeManager->getPermissionsSummary();

foreach ($permissions as $permission => $allowed) {
    echo "$permission: " . ($allowed ? 'Yes' : 'No') . "\n";
}
```

## 🔍 Troubleshooting

### Common Issues

#### Store Not Accessible

**Problem**: Users can't access the plugin store

**Solution**: Check the current mode
```php
$cmsModeManager = new CMSModeManager();
if (!$cmsModeManager->canAccessStore()) {
    echo "Store access is disabled in current mode: " . $cmsModeManager->getCurrentMode();
}
```

#### Plugin Management Disabled

**Problem**: Users can't manage plugins

**Solution**: Verify current mode allows plugin management
```php
$cmsModeManager = new CMSModeManager();
if (!$cmsModeManager->canManagePlugins()) {
    echo "Plugin management is disabled in current mode: " . $cmsModeManager->getCurrentMode();
}
```

#### Navigation Items Missing

**Problem**: Some admin menu items are not visible

**Solution**: This is expected behavior - items are hidden based on permissions
```php
// Check what's available in current mode
$cmsModeManager = new CMSModeManager();
echo "Can access store: " . ($cmsModeManager->canAccessStore() ? 'Yes' : 'No');
echo "Can manage plugins: " . ($cmsModeManager->canManagePlugins() ? 'Yes' : 'No');
```

### Debugging Mode Issues

Create a debug script to check mode status:

```php
<?php
require_once 'includes/config.php';
require_once 'includes/CMSModeManager.php';

$cmsModeManager = new CMSModeManager();

echo "=== CMS Mode Debug ===\n";
echo "Current Mode: " . $cmsModeManager->getCurrentMode() . "\n";
echo "Mode Name: " . $cmsModeManager->getModeName() . "\n";
echo "Mode Description: " . $cmsModeManager->getModeDescription() . "\n\n";

echo "Permissions:\n";
echo "- Can Manage Plugins: " . ($cmsModeManager->canManagePlugins() ? 'Yes' : 'No') . "\n";
echo "- Can Access Store: " . ($cmsModeManager->canAccessStore() ? 'Yes' : 'No') . "\n";
echo "- Can Install Plugins: " . ($cmsModeManager->canInstallPlugins() ? 'Yes' : 'No') . "\n";
echo "- Can Activate Plugins: " . ($cmsModeManager->canActivatePlugins() ? 'Yes' : 'No') . "\n";
echo "- Can Deactivate Plugins: " . ($cmsModeManager->canDeactivatePlugins() ? 'Yes' : 'No') . "\n";
echo "- Can Delete Plugins: " . ($cmsModeManager->canDeletePlugins() ? 'Yes' : 'No') . "\n";
?>
```

### Error Messages

| Error | Cause | Solution |
|-------|-------|----------|
| "Store access is disabled" | Current mode doesn't allow store access | Change to full-featured mode or accept the restriction |
| "Plugin management is disabled" | Current mode doesn't allow plugin management | Change to a mode that allows plugin management |
| "Invalid CMS mode" | Configuration file contains invalid mode | Check `config/cms_mode.json` and use valid mode name |

## 🔒 Security Considerations

### Mode Changes

- Mode changes require admin authentication
- Changes take effect immediately
- All operations respect the current mode

### Plugin Security

- In restricted modes, users cannot install potentially harmful plugins
- Pre-installed plugins are controlled by the hosting provider
- Plugin deletion is prevented in appropriate modes

### Store Security

- Store access can be completely disabled
- No external plugin sources in restricted modes
- Controlled plugin ecosystem

## 📚 Related Documentation

- [Admin Panel Guide](../admin/README.md) - General admin functionality
- [Plugin Development Guide](../plugins/README.md) - Creating plugins
- [Theme Development Guide](creating-themes.md) - Creating themes
- [Configuration Guide](../config/README.md) - System configuration

## 🆘 Getting Help

If you encounter issues with CMS modes:

1. **Check the troubleshooting section** above
2. **Verify your current mode** using the debug script
3. **Review the permissions** for your desired mode
4. **Check the configuration file** for syntax errors
5. **Ask the community** for additional support

---

**Happy mode management!** 🎛️

*This documentation is maintained by the FearlessCMS community. Last updated: January 2024*
