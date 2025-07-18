# CMS Modes

FearlessCMS now supports three different operational modes to accommodate various deployment scenarios:

## Mode 1: Full Featured (Default)

**Purpose**: Complete self-hosted installations with full access to all features.

**Permissions**:
- ✅ Plugin management (install, activate, deactivate, delete)
- ✅ Plugin store access
- ✅ All admin features

**Use Case**: Self-hosted installations where users have complete control over their CMS.

## Mode 2: Hosting Service (Plugin Mode)

**Purpose**: Hosting services where users can manage existing plugins but cannot access the store.

**Permissions**:
- ✅ Plugin management (activate, deactivate existing plugins)
- ✅ File management and uploads
- ✅ Content image uploads
- ❌ Plugin store access
- ❌ Plugin installation
- ❌ Plugin deletion

**Use Case**: Hosting services that pre-install a curated selection of plugins and allow users to activate/deactivate them.

## Mode 3: Hosting Service (No Plugin Management)

**Purpose**: Most restrictive mode for hosting services with pre-installed and activated plugins.

**Permissions**:
- ❌ Plugin management
- ❌ Plugin store access
- ❌ Plugin installation
- ❌ Plugin activation/deactivation
- ❌ Plugin deletion
- ❌ File management
- ❌ File uploads
- ❌ Content image uploads

**Use Case**: Hosting services where the owner pre-installs and activates specific plugins before activating this mode.

**Note**: In this mode, the "Plugins" menu item is renamed to "Additional Features" and shows only currently active plugins without management options. The "Files" menu item is hidden, and image uploads are disabled in the content editor. Users can still use external image URLs or upload theme-specific assets (logo, hero banner) through theme settings.

## How to Use

### Changing CMS Mode

To change the CMS mode, edit the configuration file directly:

1. Open `config/cms_mode.json` in your text editor
2. Change the mode value to one of the following:
   - `"full-featured"` (default)
   - `"hosting-service-plugins"`
   - `"hosting-service-no-plugins"`
3. Save the file

Example:
```json
{
    "mode": "hosting-service-plugins"
}
```

### Programmatic Access

```php
// Initialize the CMS mode manager
$cmsModeManager = new CMSModeManager();

// Get current mode
$currentMode = $cmsModeManager->getCurrentMode();

// Check permissions
if ($cmsModeManager->canAccessStore()) {
    // Show store interface
}

if ($cmsModeManager->canManagePlugins()) {
    // Show plugin management interface
}

if ($cmsModeManager->canActivatePlugins()) {
    // Allow plugin activation
}
```

### Configuration

The CMS mode is stored in `config/cms_mode.json`:

```json
{
    "mode": "full-featured"
}
```

## Implementation Details

### Files Added/Modified

- `includes/CMSModeManager.php` - Core mode management class
- `config/cms_mode.json` - Mode configuration file

### Modified Files

- `admin/index.php` - Added CMS mode manager initialization
- `admin/templates/plugins.php` - Added mode-based restrictions
- `admin/templates/store.php` - Added store access restrictions
- `admin/templates/base.php` - Added conditional navigation
- `admin/plugin-handler.php` - Added permission checks
- `admin/store-handler.php` - Added store access checks

### Security Considerations

- Mode changes require file system access
- All plugin operations respect the current mode
- Store access is completely blocked in restricted modes
- Navigation automatically hides restricted features
- Plugins menu is always accessible but shows "Additional Features" in no-plugins mode

## Migration Guide

### For Existing Installations

1. The system defaults to "Full Featured" mode
2. No changes are required for existing installations
3. Mode can be changed by editing `config/cms_mode.json`

### For Hosting Services

1. Install desired plugins before activating restricted modes
2. Activate required plugins
3. Edit `config/cms_mode.json` to set appropriate mode:
   - Use `"hosting-service-plugins"` if users should manage existing plugins
   - Use `"hosting-service-no-plugins"` for maximum restriction

## Troubleshooting

### Common Issues

1. **Store not accessible**: Check if current mode allows store access
2. **Plugin management disabled**: Verify current mode allows plugin management
3. **Navigation items missing**: Some items are hidden based on current mode

### Debugging

Check the current mode and permissions:

```php
$cmsModeManager = new CMSModeManager();
echo "Current Mode: " . $cmsModeManager->getCurrentMode();
echo "Can Access Store: " . ($cmsModeManager->canAccessStore() ? 'Yes' : 'No');
echo "Can Manage Plugins: " . ($cmsModeManager->canManagePlugins() ? 'Yes' : 'No');
```

## Future Enhancements

- Role-based permissions within modes
- Custom mode definitions
- Mode-specific theme restrictions
- Audit logging for mode changes 