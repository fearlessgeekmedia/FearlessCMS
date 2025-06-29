# FearlessCMS Architecture Documentation

## Overview

FearlessCMS is a lightweight, file-based content management system built in PHP. It features a modular architecture with plugin support, theme system, and three operational modes for different deployment scenarios.

## Core Architecture

### 1. Entry Points

#### `index.php` (Root)
- **Purpose**: Main frontend entry point
- **Functionality**: 
  - Routes requests to appropriate handlers
  - Loads themes and renders content
  - Processes markdown files with JSON frontmatter
  - Handles plugin hooks and content filtering

#### `router.php`
- **Purpose**: PHP built-in server router for development
- **Functionality**:
  - Routes `/admin/*` requests to admin interface
  - Serves static files from `/uploads/`
  - Handles session management for admin routes
  - Redirects unauthenticated users to login

#### `admin/index.php`
- **Purpose**: Main admin interface entry point
- **Functionality**:
  - Handles authentication and session management
  - Routes admin actions to appropriate handlers
  - Manages CMS mode restrictions
  - Loads admin templates and sections

### 2. Configuration System

#### `includes/config.php`
- **Purpose**: Core configuration and constants
- **Key Constants**:
  - `PROJECT_ROOT`: Base directory path
  - `CONTENT_DIR`: Content files location
  - `THEME_DIR`: Theme files location
  - `PLUGIN_DIR`: Plugin files location
  - `CONFIG_DIR`: Configuration files location
  - `ADMIN_CONFIG_DIR`: Admin-specific config location

#### `config/cms_mode.json`
- **Purpose**: CMS operational mode configuration
- **Modes**:
  - `full-featured`: Complete access to all features
  - `hosting-service-plugin`: Plugins available, no store access
  - `hosting-service-no-plugins`: No plugin management, pre-installed only

#### `config/config.json`
- **Purpose**: Site-wide configuration
- **Contains**: Site name, description, custom CSS/JS, admin path

### 3. Authentication System

#### `includes/auth.php`
- **Purpose**: User authentication and session management
- **Functions**:
  - `isLoggedIn()`: Check authentication status
  - `login()`: Authenticate user
  - `logout()`: End user session
  - Permission checking for admin actions

#### `config/admin/users.json`
- **Purpose**: User account storage
- **Format**: JSON array of user objects with hashed passwords and permissions

### 4. Content Management

#### `includes/functions.php`
- **Purpose**: Core utility functions
- **Functions**:
  - Content parsing and rendering
  - File system operations
  - URL generation and routing
  - Template processing

#### `includes/Parsedown.php`
- **Purpose**: Markdown parsing library
- **Functionality**: Converts markdown to HTML with custom extensions

#### Content Structure
```
content/
├── home.md          # Homepage content
├── about.md         # About page
├── blog_posts.json  # Blog post metadata
└── forms/           # Form submissions
```

### 5. Theme System

#### `includes/ThemeManager.php`
- **Purpose**: Theme management and rendering
- **Functionality**:
  - Discovers available themes
  - Loads theme configuration
  - Renders theme templates
  - Manages theme assets

#### Theme Structure
```
themes/
├── default/
│   ├── config.json      # Theme configuration
│   ├── assets/
│   │   └── style.css    # Theme styles
│   └── templates/
│       ├── page.html    # Page template
│       ├── home.html    # Homepage template
│       └── head.html    # Header template
```

#### `includes/TemplateRenderer.php`
- **Purpose**: Template processing and rendering
- **Functionality**:
  - Processes template variables
  - Handles template inheritance
  - Manages template caching

### 6. Plugin System

#### `includes/plugins.php`
- **Purpose**: Plugin framework and management
- **Key Features**:
  - Hook system for extending functionality
  - Admin section registration
  - Plugin loading and initialization
  - Permission system integration

#### Hook System
```php
// Available hooks
$GLOBALS['fcms_hooks'] = [
    'init' => [],              // Plugin initialization
    'before_content' => [],    // Before content rendering
    'after_content' => [],     // After content rendering
    'before_render' => [],     // Before template rendering
    'after_render' => [],      // After template rendering
    'route' => [],             // Custom routing
    'check_permission' => [],  // Permission checking
    'content' => []            // Content filtering
];
```

#### Plugin Structure
```
plugins/
├── seo/
│   ├── plugin.json       # Plugin metadata
│   ├── seo.php          # Main plugin file
│   └── admin/           # Admin interface files
├── forms/
│   ├── plugin.json
│   ├── forms.php
│   └── templates/
└── blog/
    ├── plugin.json
    └── blog.php
```

### 7. CMS Mode Management

#### `includes/CMSModeManager.php`
- **Purpose**: Manages operational modes and permissions
- **Functionality**:
  - Mode switching and validation
  - Permission checking for different capabilities
  - Mode-specific feature restrictions

#### Available Permissions
- `can_manage_plugins`: Plugin management access
- `can_access_store`: Plugin store access
- `can_install_plugins`: Plugin installation
- `can_activate_plugins`: Plugin activation
- `can_deactivate_plugins`: Plugin deactivation
- `can_delete_plugins`: Plugin deletion

### 8. Admin Interface

#### Admin Structure
```
admin/
├── index.php              # Main admin entry point
├── login.php              # Login page
├── templates/             # Admin templates
│   ├── base.php          # Base admin template
│   ├── dashboard.php     # Dashboard template
│   ├── plugins.php       # Plugin management
│   └── themes.php        # Theme management
├── handlers/              # Action handlers
│   ├── plugin-handler.php # Plugin operations
│   ├── theme-handler.php  # Theme operations
│   └── widget-handler.php # Widget operations
└── config/               # Admin configuration
    ├── users.json        # User accounts
    ├── plugins.json      # Active plugins
    └── widgets.json      # Widget configuration
```

#### Admin Sections
- **Dashboard**: Overview and statistics
- **Content Management**: Page creation and editing
- **Plugin Management**: Plugin installation and configuration
- **Theme Management**: Theme selection and customization
- **Menu Management**: Navigation menu configuration
- **Widget Management**: Sidebar widget configuration
- **User Management**: User account administration

### 9. File Management

#### `admin/includes/filemanager.php`
- **Purpose**: File upload and management
- **Functionality**:
  - File upload handling
  - Directory browsing
  - File deletion and organization
  - Image processing and optimization

### 10. Widget System

#### `includes/WidgetManager.php`
- **Purpose**: Sidebar widget management
- **Functionality**:
  - Widget registration and rendering
  - Sidebar configuration
  - Widget data persistence

#### Widget Structure
```
config/
└── widgets.json          # Widget configuration
    {
        "sidebar": {
            "name": "Main Sidebar",
            "widgets": [
                {
                    "id": "unique_id",
                    "type": "text",
                    "title": "Widget Title",
                    "content": "Widget content"
                }
            ]
        }
    }
```

### 11. Menu System

#### `includes/MenuManager.php`
- **Purpose**: Navigation menu management
- **Functionality**:
  - Menu creation and editing
  - Menu rendering
  - Menu hierarchy management

#### Menu Structure
```
config/
└── menus.json           # Menu configuration
    {
        "main_menu": {
            "label": "Main Navigation",
            "items": [
                {
                    "label": "Home",
                    "url": "/",
                    "children": []
                }
            ]
        }
    }
```

## Data Flow

### 1. Frontend Request Flow
```
Request → router.php → index.php → ThemeManager → TemplateRenderer → Output
```

### 2. Admin Request Flow
```
Request → router.php → admin/index.php → Action Handler → Template → Output
```

### 3. Plugin Integration Flow
```
Plugin Load → Hook Registration → Admin Section Registration → Feature Integration
```

## Security Features

### 1. Authentication
- Session-based authentication
- Password hashing with bcrypt
- Permission-based access control

### 2. Input Validation
- File upload validation
- Content sanitization
- CSRF protection

### 3. File System Security
- Path traversal prevention
- File type restrictions
- Directory access controls

## Performance Considerations

### 1. Caching
- Template caching
- Plugin hook caching
- File system caching

### 2. Optimization
- Lazy loading of plugins
- Efficient file system operations
- Minimal database dependencies

## Extension Points

### 1. Plugin Development
- Hook system for custom functionality
- Admin section registration
- Custom content types
- Custom templates

### 2. Theme Development
- Template inheritance system
- Asset management
- Configuration options
- Custom styling

### 3. Custom Handlers
- Custom routing
- Custom admin sections
- Custom file processors

## Deployment Considerations

### 1. File Permissions
- Write access for uploads and config
- Read access for content and themes
- Execute access for PHP files

### 2. Server Configuration
- URL rewriting for clean URLs
- PHP configuration optimization
- File upload limits

### 3. Security Hardening
- HTTPS enforcement
- File access restrictions
- Error reporting configuration

## Development Workflow

### 1. Local Development
- PHP built-in server with router.php
- File-based configuration
- Direct file editing

### 2. Plugin Development
- Plugin directory structure
- Hook integration
- Admin interface development

### 3. Theme Development
- Template creation
- Asset compilation
- Configuration management

This architecture provides a solid foundation for a lightweight, extensible content management system with strong separation of concerns and clear extension points for customization and enhancement. 