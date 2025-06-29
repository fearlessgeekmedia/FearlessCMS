# FearlessCMS Customization Overview

This document provides a comprehensive overview of all the customization options available in FearlessCMS, with references to detailed documentation for each area.

## Quick Reference

| Customization Area | Description | Documentation |
|-------------------|-------------|---------------|
| **Admin Path** | Change the default `/admin` path for security | [CMS Tweaks Guide](cms-tweaks-and-customizations.md#admin-path-customization) |
| **CMS Modes** | Configure operational modes (Full/Plugin/Restricted) | [CMS Tweaks Guide](cms-tweaks-and-customizations.md#cms-mode-configuration) |
| **Custom Code** | Add custom CSS and JavaScript | [CMS Tweaks Guide](cms-tweaks-and-customizations.md#custom-css-and-javascript) |
| **Theme Options** | Configure theme-specific settings | [Theme Options Guide](theme-options-guide.md) |
| **Site Settings** | Basic site configuration | [CMS Tweaks Guide](cms-tweaks-and-customizations.md#site-configuration) |
| **Plugin Management** | Install, activate, and configure plugins | [CMS Tweaks Guide](cms-tweaks-and-customizations.md#plugin-management) |
| **Menu System** | Create and manage navigation menus | [CMS Tweaks Guide](cms-tweaks-and-customizations.md#menu-and-widget-customization) |
| **Widget System** | Add and configure sidebar widgets | [CMS Tweaks Guide](cms-tweaks-and-customizations.md#menu-and-widget-customization) |
| **File Management** | Upload and organize files | [CMS Tweaks Guide](cms-tweaks-and-customizations.md#file-management) |
| **User Management** | Create and manage user accounts | [CMS Tweaks Guide](cms-tweaks-and-customizations.md#user-management) |
| **Theme Development** | Create custom themes | [Creating Themes](creating-themes.md) |
| **Plugin Development** | Develop custom plugins | [Plugin Development Guide](plugin-development-guide.md) |
| **Template System** | Customize page templates | [Theme Templates Reference](theme-templates-reference.md) |

## Configuration Files

### Main Configuration Files

| File | Purpose | Location |
|------|---------|----------|
| `config.json` | Main site configuration | `config/config.json` |
| `cms_mode.json` | CMS operational mode | `config/cms_mode.json` |
| `theme_options.json` | Theme-specific options | `config/theme_options.json` |
| `active_plugins.json` | Active plugin list | `config/active_plugins.json` |
| `menus.json` | Menu configurations | `config/menus.json` |
| `widgets.json` | Widget configurations | `config/widgets.json` |
| `users.json` | User accounts | `config/admin/users.json` |

### Main Configuration Structure

```json
{
    "active_theme": "theme_name",
    "site_name": "Your Site Name",
    "site_description": "Your site description",
    "admin_path": "admin",
    "custom_css": "your custom CSS",
    "custom_js": "your custom JavaScript",
    "store_url": "https://store-url.com/store.json"
}
```

## Quick Customization Examples

### 1. Change Admin Path

Edit `config/config.json`:
```json
{
    "admin_path": "your-secure-admin-path"
}
```

### 2. Add Custom CSS

Edit `config/config.json`:
```json
{
    "custom_css": "body { background-color: #f0f0f0; }"
}
```

### 3. Change CMS Mode

Edit `config/cms_mode.json`:
```json
{
    "mode": "hosting-service-plugin"
}
```

### 4. Configure Theme Options

Edit `config/theme_options.json`:
```json
{
    "primaryColor": "#007cba",
    "fontFamily": "Inter",
    "showSidebar": true
}
```

## Available CMS Modes

### Full Featured (Default)
- Complete access to all features
- Plugin store access
- Plugin installation and management
- All admin features enabled

### Hosting Service (Plugin Mode)
- Manage existing plugins only
- No plugin store access
- No plugin installation
- Plugin activation/deactivation allowed

### Hosting Service (No Plugin Management)
- No plugin management
- No store access
- Pre-installed plugins only
- Basic admin features

## Theme Customization Options

### Common Theme Options

| Option Type | Description | Example |
|-------------|-------------|---------|
| **image** | File upload for images | Logo, hero banner |
| **color** | Color picker | Primary color, accent color |
| **text** | Text input field | Site tagline, footer text |
| **textarea** | Multi-line text area | Custom CSS, custom JS |
| **select** | Dropdown selection | Font family, layout style |
| **boolean** | Checkbox/toggle | Show sidebar, enable features |
| **number** | Numeric input | Excerpt length, grid columns |
| **array** | Array of items | Social links, menu items |

### Theme Options Example

```json
{
    "options": {
        "logo": {
            "type": "image",
            "label": "Site Logo"
        },
        "primaryColor": {
            "type": "color",
            "label": "Primary Color",
            "default": "#007cba"
        },
        "fontFamily": {
            "type": "select",
            "label": "Font Family",
            "options": [
                {"value": "Inter", "label": "Inter"},
                {"value": "Roboto", "label": "Roboto"}
            ],
            "default": "Inter"
        },
        "showSidebar": {
            "type": "boolean",
            "label": "Show Sidebar",
            "default": true
        }
    }
}
```

## Plugin System

### Plugin Management

- **Installation**: From store or manual upload
- **Activation/Deactivation**: Through admin panel
- **Configuration**: Plugin-specific settings
- **Development**: Custom plugin creation

### Plugin Types

| Type | Description | Example |
|------|-------------|---------|
| **Content Plugins** | Modify content processing | SEO, markdown extensions |
| **Admin Plugins** | Add admin functionality | Analytics, backup tools |
| **Widget Plugins** | Create custom widgets | Social media, contact forms |
| **Template Plugins** | Modify template rendering | Custom layouts, features |

## Menu System

### Menu Configuration

```json
{
    "main": {
        "label": "Main Navigation",
        "menu_class": "main-menu",
        "items": [
            {
                "title": "Home",
                "url": "/",
                "target": "_self"
            },
            {
                "title": "About",
                "url": "/about",
                "target": "_self"
            }
        ]
    }
}
```

### Menu Features

- Multiple menu locations
- Custom CSS classes
- External/internal links
- Target window options
- Hierarchical structure

## Widget System

### Widget Configuration

```json
{
    "sidebar_main": [
        {
            "type": "text",
            "title": "About Widget",
            "content": "Your widget content here"
        },
        {
            "type": "menu",
            "title": "Navigation",
            "menu": "sidebar"
        }
    ]
}
```

### Widget Types

- **Text**: Simple text content
- **Menu**: Display menu items
- **Custom**: Plugin-generated widgets
- **HTML**: Raw HTML content

## File Management

### File Organization

| Directory | Purpose |
|-----------|---------|
| `uploads/` | General file uploads |
| `uploads/theme/` | Theme-specific assets |
| `content/` | Markdown content files |
| `themes/` | Theme files |
| `plugins/` | Plugin files |

### File Permissions

- **Directories**: 755
- **Files**: 644
- **Upload directory**: 755

## User Management

### User Roles

| Role | Permissions |
|------|-------------|
| **Administrator** | Full access to all features |
| **Editor** | Can edit content and manage themes |
| **Author** | Can create and edit content |

### User Configuration

```json
{
    "admin": {
        "username": "admin",
        "password": "hashed_password",
        "role": "administrator"
    }
}
```

## Advanced Customizations

### Custom Templates

- Create custom page templates
- Use template variables
- Conditional rendering
- Custom layouts

### Plugin Development

- Hook into CMS events
- Add admin sections
- Create custom widgets
- Modify content processing

### Theme Development

- Create custom themes
- Define theme options
- Custom CSS/JS
- Template customization

## Security Considerations

### Admin Security

1. **Change default admin path**
2. **Use strong passwords**
3. **Regular security updates**
4. **HTTPS implementation**
5. **File permissions**

### Best Practices

1. **Backup configuration files**
2. **Test in development environment**
3. **Document custom modifications**
4. **Version control important changes**
5. **Monitor for issues after deployment**

## Performance Optimization

### Caching

- Enable PHP opcache
- Use CDN for static assets
- Optimize images
- Minify CSS/JS

### Optimization Tips

1. **Optimize images** before upload
2. **Use efficient themes**
3. **Minimize plugin usage**
4. **Regular cleanup**
5. **Monitor performance**

## Troubleshooting

### Common Issues

| Issue | Solution |
|-------|----------|
| Admin path not working | Check `.htaccess` and config |
| Theme options not saving | Check file permissions |
| Plugins not loading | Verify plugin structure |
| Custom code not working | Check syntax and cache |

### Debug Mode

Enable debug mode in `config/config.json`:
```json
{
    "debug": true,
    "debug_log": "error.log"
}
```

## Documentation References

### Detailed Guides

- **[CMS Tweaks and Customizations](cms-tweaks-and-customizations.md)**: Complete customization guide
- **[Theme Options Guide](theme-options-guide.md)**: Theme customization details
- **[Creating Themes](creating-themes.md)**: Theme development guide
- **[Plugin Development Guide](plugin-development-guide.md)**: Plugin development
- **[Theme Templates Reference](theme-templates-reference.md)**: Template system reference
- **[Creating Plugins](creating-plugins.md)**: Plugin creation guide

### Additional Resources

- **[Theme Structure Standards](theme-structure-standards.md)**: Theme organization
- **[Modular Templates](modular-templates.md)**: Template modularity
- **[SASS Theme Guide](sass-theme-guide.md)**: Advanced styling
- **[Theme Development Workflow](theme-development-workflow.md)**: Development process

## Getting Help

### Support Resources

1. **Documentation**: Comprehensive guides in `~/fcmsdocs/`
2. **Error Logs**: Check `error.log` for issues
3. **Configuration Files**: Review JSON syntax
4. **File Permissions**: Ensure proper access rights
5. **Community**: Check for community resources

### Development Workflow

1. **Backup** existing configuration
2. **Test** changes in development environment
3. **Document** custom modifications
4. **Version control** important changes
5. **Monitor** for issues after deployment

## Conclusion

FearlessCMS provides extensive customization options through its modular architecture. From simple configuration changes to advanced plugin development, the system is designed to be flexible and user-friendly.

For specific customization needs, refer to the detailed documentation in the `~/fcmsdocs/` directory. Each guide provides comprehensive information, examples, and best practices for that particular area of customization.

Remember to always backup your configuration files before making changes and test customizations in a development environment before applying them to production. 