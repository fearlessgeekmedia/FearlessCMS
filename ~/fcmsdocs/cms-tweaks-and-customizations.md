# FearlessCMS Tweaks and Customizations Guide

This comprehensive guide covers all the tweaks and customizations you can make to FearlessCMS to tailor it to your specific needs.

## Table of Contents

1. [Admin Path Customization](#admin-path-customization)
2. [CMS Mode Configuration](#cms-mode-configuration)
3. [Custom CSS and JavaScript](#custom-css-and-javascript)
4. [Theme Options and Customization](#theme-options-and-customization)
5. [Site Configuration](#site-configuration)
6. [Plugin Management](#plugin-management)
7. [Menu and Widget Customization](#menu-and-widget-customization)
8. [File Management](#file-management)
9. [User Management](#user-management)
10. [Advanced Customizations](#advanced-customizations)

## Admin Path Customization

### Changing the Admin Path

By default, the admin panel is accessible at `/admin`. You can change this to any custom path for security purposes.

#### Method 1: Through Configuration File

1. Edit `config/config.json`:
```json
{
    "active_theme": "your_theme",
    "site_name": "Your Site",
    "site_description": "Your site description",
    "admin_path": "your-custom-admin-path",
    "custom_css": "",
    "custom_js": "",
    "store_url": "https://raw.githubusercontent.com/fearlessgeekmedia/FearlessCMS-Store/main/store.json"
}
```

2. Update your `.htaccess` file to handle the new path:
```apache
RewriteEngine On

# Handle custom admin section
RewriteCond %{REQUEST_URI} ^/your-custom-admin-path/?$
RewriteRule ^your-custom-admin-path/?$ admin/index.php [L]

# Handle custom admin login
RewriteCond %{REQUEST_URI} ^/your-custom-admin-path/login/?$
RewriteRule ^your-custom-admin-path/login/?$ admin/login.php [L]

# Handle custom admin logout
RewriteCond %{REQUEST_URI} ^/your-custom-admin-path/logout/?$
RewriteRule ^your-custom-admin-path/logout/?$ admin/logout.php [L]

# Handle custom admin actions
RewriteCond %{REQUEST_URI} ^/your-custom-admin-path/([^/]+)/?$
RewriteRule ^your-custom-admin-path/([^/]+)/?$ admin/index.php?action=$1 [L,QSA]

# Handle content pages
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]
```

#### Method 2: Through Admin Panel

1. Log into your admin panel
2. Navigate to **Site Settings**
3. Look for the admin path configuration option
4. Enter your desired custom path
5. Save the changes

### Security Considerations

- Choose a path that's not easily guessable
- Avoid common paths like `/admin`, `/administrator`, `/manage`
- Consider using a random string or your company name
- Update any bookmarks or links that reference the old admin path

## CMS Mode Configuration

FearlessCMS supports three different operational modes to accommodate various deployment scenarios.

### Available Modes

#### 1. Full Featured (Default)
- **Purpose**: Complete self-hosted installations
- **Permissions**: Full access to all features
- **Use Case**: Self-hosted installations with complete control

#### 2. Hosting Service (Plugin Mode)
- **Purpose**: Hosting services with plugin management
- **Permissions**: Can manage existing plugins, no store access
- **Use Case**: Hosting services with curated plugin selection

#### 3. Hosting Service (No Plugin Management)
- **Purpose**: Most restrictive mode
- **Permissions**: No plugin management, pre-installed plugins only
- **Use Case**: Hosting services with pre-configured plugins

### Changing CMS Mode

#### Through Admin Panel

1. Log into your admin panel
2. Navigate to **CMS Mode** in the admin menu
3. Select the desired mode from the options
4. Click **Change Mode**
5. Confirm the change when prompted

#### Through Configuration File

Edit `config/cms_mode.json`:
```json
{
    "mode": "hosting-service-plugin"
}
```

### Mode-Specific Features

#### Full Featured Mode
- Plugin installation from store
- Plugin activation/deactivation
- Plugin deletion
- Store access
- All admin features

#### Hosting Service (Plugin Mode)
- Plugin activation/deactivation (existing plugins only)
- No plugin installation
- No store access
- No plugin deletion

#### Hosting Service (No Plugin Management)
- No plugin management
- No store access
- Pre-installed plugins only
- Basic admin features

## Custom CSS and JavaScript

### Adding Custom CSS

#### Method 1: Through Admin Panel

1. Log into your admin panel
2. Navigate to **Dashboard** or **Site Settings**
3. Find the **Custom Code** section
4. Enter your CSS in the **Custom CSS** textarea
5. Click **Save Custom Code**

#### Method 2: Through Configuration File

Edit `config/config.json`:
```json
{
    "active_theme": "your_theme",
    "site_name": "Your Site",
    "custom_css": "body { background-color: #f0f0f0; } .header { color: #333; }",
    "custom_js": ""
}
```

### Adding Custom JavaScript

#### Method 1: Through Admin Panel

1. Log into your admin panel
2. Navigate to **Dashboard** or **Site Settings**
3. Find the **Custom Code** section
4. Enter your JavaScript in the **Custom JavaScript** textarea
5. Click **Save Custom Code**

#### Method 2: Through Configuration File

Edit `config/config.json`:
```json
{
    "active_theme": "your_theme",
    "site_name": "Your Site",
    "custom_css": "",
    "custom_js": "console.log('Custom script loaded!'); document.addEventListener('DOMContentLoaded', function() { // Your code here });"
}
```

### Custom Code Examples

#### CSS Examples

```css
/* Change site colors */
:root {
    --primary-color: #your-color;
    --secondary-color: #your-color;
}

/* Custom styling for specific elements */
.site-header {
    background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
}

/* Responsive design adjustments */
@media (max-width: 768px) {
    .main-navigation {
        display: none;
    }
}
```

#### JavaScript Examples

```javascript
// Add smooth scrolling
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
        });
    });
});

// Add custom analytics
window.addEventListener('load', function() {
    // Your analytics code here
    console.log('Page loaded');
});

// Custom form validation
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        // Your validation logic here
    });
});
```

## Theme Options and Customization

### Theme-Specific Options

Each theme can define its own customization options. These appear in the admin panel under **Themes**.

#### Common Theme Options

- **Logo**: Upload a custom logo
- **Hero Banner**: Set a hero image
- **Primary Color**: Choose the main brand color
- **Font Family**: Select typography
- **Show Sidebar**: Toggle sidebar visibility
- **Custom CSS**: Theme-specific custom styles

### Configuring Theme Options

1. Log into your admin panel
2. Navigate to **Themes**
3. Select your active theme
4. Configure the available options
5. Click **Save Options**

### Theme Options Configuration

Theme options are defined in each theme's `config.json` file:

```json
{
    "name": "Your Theme",
    "version": "1.0.0",
    "options": {
        "logo": {
            "type": "image",
            "label": "Logo"
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
                {"value": "sans-serif", "label": "Sans Serif"},
                {"value": "serif", "label": "Serif"}
            ],
            "default": "sans-serif"
        }
    }
}
```

### Option Types

- **image**: File upload for images
- **color**: Color picker
- **text**: Text input field
- **textarea**: Multi-line text area
- **select**: Dropdown selection
- **boolean**: Checkbox/toggle
- **number**: Numeric input

## Site Configuration

### Basic Site Settings

#### Site Name and Description

Edit `config/config.json`:
```json
{
    "site_name": "Your Custom Site Name",
    "site_description": "Your site tagline or description"
}
```

#### Active Theme

```json
{
    "active_theme": "your-theme-name"
}
```

### Store Configuration

#### Custom Store URL

```json
{
    "store_url": "https://your-custom-store-url.com/store.json"
}
```

### Configuration File Structure

The main configuration file (`config/config.json`) contains:

```json
{
    "active_theme": "theme_name",
    "site_name": "Site Name",
    "site_description": "Site Description",
    "admin_path": "admin",
    "custom_css": "your custom CSS",
    "custom_js": "your custom JavaScript",
    "store_url": "store URL"
}
```

## Plugin Management

### Plugin Activation/Deactivation

#### Through Admin Panel

1. Navigate to **Plugins** in the admin menu
2. Find the plugin you want to manage
3. Click **Activate** or **Deactivate**
4. Confirm the action

#### Through Configuration File

Edit `config/active_plugins.json`:
```json
[
    "plugin-name-1",
    "plugin-name-2"
]
```

### Plugin Installation

#### From Store (Full Featured Mode)

1. Navigate to **Store** in the admin menu
2. Browse available plugins
3. Click **Install** on desired plugins
4. Follow installation prompts

#### Manual Installation

1. Download plugin files
2. Upload to `plugins/` directory
3. Activate through admin panel

### Plugin Configuration

Each plugin may have its own configuration options accessible through the admin panel.

## Menu and Widget Customization

### Menu Management

#### Creating Menus

1. Navigate to **Menus** in the admin panel
2. Click **Create New Menu**
3. Add menu items with URLs and labels
4. Save the menu

#### Menu Configuration

Menus are stored in `config/menus.json`:
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

### Widget Management

#### Adding Widgets

1. Navigate to **Widgets** in the admin panel
2. Select a sidebar
3. Add widgets from available options
4. Configure widget settings
5. Save changes

#### Widget Configuration

Widgets are stored in `config/widgets.json`:
```json
{
    "sidebar_main": [
        {
            "type": "text",
            "title": "About Widget",
            "content": "Your widget content here"
        }
    ]
}
```

## File Management

### Uploading Files

#### Through Admin Panel

1. Navigate to **Files** in the admin panel
2. Click **Upload File**
3. Select files to upload
4. Files are stored in `uploads/` directory

#### File Organization

- **uploads/**: General file uploads
- **uploads/theme/**: Theme-specific assets
- **content/**: Markdown content files
- **themes/**: Theme files
- **plugins/**: Plugin files

### File Permissions

Ensure proper file permissions:
- Directories: 755
- Files: 644
- Upload directory: 755

## User Management

### Creating Users

#### Through Admin Panel

1. Navigate to **Users** in the admin panel
2. Click **Add New User**
3. Enter username and password
4. Set user role
5. Save user

#### User Configuration

Users are stored in `config/admin/users.json`:
```json
{
    "admin": {
        "username": "admin",
        "password": "hashed_password",
        "role": "administrator"
    }
}
```

### User Roles

- **Administrator**: Full access to all features
- **Editor**: Can edit content and manage themes
- **Author**: Can create and edit content

## Advanced Customizations

### Custom Templates

#### Creating Custom Page Templates

1. Create a new template file in your theme's `templates/` directory
2. Use the template system variables
3. Reference the template in your content

#### Template Variables

Available variables in templates:
- `{{title}}`: Page title
- `{{content}}`: Page content
- `{{siteName}}`: Site name
- `{{themeOptions}}`: Theme options
- `{{menu}}`: Menu data
- `{{sidebar}}`: Widget data

### Custom Hooks and Filters

#### Plugin Development

Create custom plugins to extend functionality:
- Content filters
- Admin hooks
- Custom widgets
- Template modifications

### Database Customization

#### Custom Data Storage

- Use JSON files for simple data
- Create custom configuration files
- Extend with plugins for complex data

### Performance Optimization

#### Caching

- Enable PHP opcache
- Use CDN for static assets
- Optimize images
- Minify CSS/JS

#### Security Enhancements

- Change default admin path
- Use strong passwords
- Regular security updates
- HTTPS implementation

## Troubleshooting

### Common Issues

#### Admin Path Not Working

1. Check `.htaccess` configuration
2. Verify `config/config.json` settings
3. Clear browser cache
4. Check server rewrite rules

#### Theme Options Not Saving

1. Check file permissions
2. Verify JSON syntax
3. Check disk space
4. Review error logs

#### Plugins Not Loading

1. Check plugin file structure
2. Verify plugin configuration
3. Check for syntax errors
4. Review plugin dependencies

### Debug Mode

Enable debug mode by adding to `config/config.json`:
```json
{
    "debug": true,
    "debug_log": "error.log"
}
```

### Error Logs

Check error logs for issues:
- PHP error log
- Server error log
- Application error log

## Best Practices

### Security

1. Change default admin path
2. Use strong, unique passwords
3. Keep software updated
4. Regular backups
5. HTTPS implementation

### Performance

1. Optimize images
2. Minify CSS/JS
3. Use caching
4. CDN for static assets
5. Regular maintenance

### Maintenance

1. Regular backups
2. Monitor error logs
3. Update themes and plugins
4. Clean unused files
5. Database optimization

## Conclusion

FearlessCMS provides extensive customization options to tailor your website to your specific needs. From simple configuration changes to advanced plugin development, the system is designed to be flexible and user-friendly.

For more advanced customizations, consider:
- Plugin development
- Theme creation
- Custom template development
- API integration
- Third-party service integration

Remember to always backup your configuration files before making changes, and test customizations in a development environment before applying them to production. 