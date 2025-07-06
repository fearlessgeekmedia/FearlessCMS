# FearlessCMS Architecture Documentation

## Overview

FearlessCMS is a lightweight, file-based content management system built in PHP. It features a modular architecture with plugin support, theme system, and three operational modes for different deployment scenarios. The system prioritizes security, performance, and maintainability through proper file ownership and standard permissions.

## Core Architecture

### 1. Entry Points

#### `index.php` (Root)
- **Purpose**: Main frontend entry point
- **Functionality**: 
  - Initializes session management via `includes/session.php`
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
  - Initializes session management via `includes/session.php`
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
  - `hosting-service-plugins`: Plugins available, no store access
  - `hosting-service-no-plugins`: No plugin management, pre-installed only

#### `config/config.json`
- **Purpose**: Site-wide configuration
- **Contains**: Site name, description, admin path, and other core settings

### 3. Authentication & Session System

#### `includes/session.php`
- **Purpose**: Centralized session management and configuration
- **Functionality**:
  - Configures session settings before any session starts
  - Sets session save path to `/sessions` directory
  - Configures secure session cookies with proper path settings
  - Ensures session consistency across admin and frontend
- **Key Settings**:
  - Session save path: `/sessions` directory (owned by web server user)
  - Cookie path: `/` (available for all paths including `/admin`)
  - Cookie security: HttpOnly, SameSite=Lax
  - Session lifetime: 1 hour

#### `includes/auth.php`
- **Purpose**: User authentication and session management
- **Functions**:
  - `isLoggedIn()`: Check authentication status
  - `login()`: Authenticate user
  - `logout()`: End user session
  - Permission checking for admin actions
  - `createDefaultAdminUser()`: Creates default admin user if none exists

#### `config/users.json`
- **Purpose**: User account storage
- **Format**: JSON array of user objects with hashed passwords and permissions
- **Default Credentials**: Username: `admin`, Password: `admin`
- **Ownership**: Must be owned by web server user for proper access

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
├── home.md                    # Homepage content
├── about.md                   # About page
├── blog_posts.json           # Blog post metadata
├── forms/                    # Forms plugin data (owned by web server user)
│   ├── forms.log            # Forms log file
│   └── submissions/         # Form submissions directory
└── form_submissions/        # Form submissions (owned by web server user)
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
│   ├── config.json      # Theme configuration and options
│   ├── assets/
│   │   ├── style.css    # Theme styles
│   │   ├── images/      # Theme images
│   │   └── js/          # Theme JavaScript files
│   └── templates/
│       ├── page.html    # Page template
│       ├── home.html    # Homepage template
│       ├── header.html  # Header module
│       ├── footer.html  # Footer module
│       └── navigation.html # Navigation module
```

#### `includes/TemplateRenderer.php`
- **Purpose**: Template processing and rendering
- **Functionality**:
  - Processes template variables
  - Handles template inheritance
  - Manages modular template system
  - Processes theme options integration

#### Theme Options System
- **Purpose**: User-friendly theme customization without code editing
- **Features**:
  - Text, textarea, select, checkbox, color, image, and array field types
  - Integration with template variables via `{{themeOptions.key}}`
  - Modular template support for component-specific options
  - Configuration stored in `config.json` within theme directory

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

### 7. Database Plugin Architecture: MariaDB Connector Example

FearlessCMS supports database-backed plugins to extend the flat-file architecture. The MariaDB Connector plugin is the reference implementation, providing PDO-based MariaDB/MySQL connectivity for other plugins.

#### MariaDB Connector Plugin
- **Location:** `plugins/mariadb-connector/`
- **Purpose:** Allows plugins to use a shared PDO connection to a MariaDB/MySQL database, enabling advanced features (e.g., dynamic blogs, e-commerce, analytics).
- **Configuration:** Stores DB credentials in `content/mariadb-connector/config.json` via an admin UI.
- **Hooks Provided:**
  - `database_connect`: Returns a PDO connection (or null on failure).
  - `database_query`: Executes a query with parameters and returns a PDOStatement or false.
- **Admin Integration:** Registers a settings page under Plugins for DB config and connection testing.
- **Best Practice:** Other plugins should use `fcms_do_hook('database_connect')` and `fcms_do_hook('database_query', $query, $params)` for DB access, not direct connection code.

#### Plugin System Improvements
- **Hook System:**
  - `fcms_do_hook($hook, ...$args)`: By-value, returns first non-null result (for most plugin hooks).
  - `fcms_do_hook_ref($hook, &...$args)`: By-reference, for core hooks that require reference arguments (e.g., `route`, `before_render`).
  - `fcms_apply_filter($hook, $value, ...$args)`: For value filters.
- **Plugin Loader:** Only loads plugins listed in `admin/config/plugins.json`.
- **Admin Sections:** Plugins can register admin pages with `fcms_register_admin_section()`.

#### Extending the CMS
- Database plugins can provide new hooks for other plugins to use.
- Flat-file and database-backed plugins can coexist, allowing gradual migration or hybrid content models.

### 8. CMS Mode Management

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

### 9. Admin Interface

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
├── config/               # Admin configuration
│   ├── users.json        # User accounts
│   ├── plugins.json      # Active plugins
│   └── widgets.json      # Widget configuration
└── uploads/              # Admin file uploads (owned by web server user)
```

#### Admin Sections
- **Dashboard**: Overview and statistics
- **Content Management**: Page creation and editing
- **Plugin Management**: Plugin installation and configuration
- **Theme Management**: Theme selection and customization
- **Menu Management**: Navigation menu configuration
- **Widget Management**: Sidebar widget configuration
- **User Management**: User account administration

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

### 1. Authentication & Session Management
- Session-based authentication with centralized configuration
- Password hashing with bcrypt
- Permission-based access control
- Secure session cookies with HttpOnly and SameSite attributes
- Unified session storage across admin and frontend
- Session path configuration to prevent redirect loops

### 2. File System Security
- **Proper Ownership**: All writable directories and files owned by web server user
- **Standard Permissions**: 755 for directories, 644 for files (no overly permissive 777/666)
- **Path Traversal Prevention**: Strict path validation and sanitization
- **File Type Restrictions**: Controlled file upload types and extensions
- **Directory Access Controls**: Restricted access to sensitive directories

### 3. Input Validation
- File upload validation with type and size restrictions
- Content sanitization and XSS prevention
- CSRF protection for admin actions
- SQL injection prevention through parameterized queries

### 4. Critical Security Directories
- `sessions/`: Session file storage (755, owned by web server user)
- `content/forms/`: Forms plugin data (755, owned by web server user)
- `content/form_submissions/`: Form submissions (755, owned by web server user)
- `config/`: Configuration files (755, owned by web server user)
- `uploads/`: File uploads (755, owned by web server user)
- `admin/uploads/`: Admin file uploads (755, owned by web server user)

## Performance Considerations

### 1. Caching
- Template caching for improved rendering performance
- Plugin hook caching to reduce overhead
- File system caching for frequently accessed content
- Session file optimization

### 2. Optimization
- Lazy loading of plugins and themes
- Efficient file system operations with proper permissions
- Minimal database dependencies (file-based by default)
- Optimized template rendering with modular system

### 3. Asset Management
- Theme asset organization and optimization
- Static file serving through web server
- Image optimization and compression
- JavaScript and CSS minification support

## Extension Points

### 1. Plugin Development
- Hook system for custom functionality
- Admin section registration
- Custom content types and processors
- Custom templates and themes
- Database integration through MariaDB connector

### 2. Theme Development
- Template inheritance system with modular components
- Asset management and organization
- Theme options for user customization
- Custom styling with CSS/SASS support
- JavaScript integration for interactivity

### 3. Custom Handlers
- Custom routing and URL handling
- Custom admin sections and interfaces
- Custom file processors and content types
- Custom authentication and permission systems

## Deployment Considerations

### 1. File Permissions and Ownership
- **Web Server User Identification**: Determine correct web server user (www-data, apache, http, nginx)
- **Proper Ownership**: Set ownership of writable directories to web server user
- **Standard Permissions**: Use 755 for directories, 644 for files
- **Security**: Avoid overly permissive 777/666 permissions
- **Verification**: Test file operations with web server user

### 2. Server Configuration
- URL rewriting for clean URLs and SEO
- PHP configuration optimization for performance
- File upload limits and security settings
- HTTPS enforcement for production environments

### 3. Security Hardening
- HTTPS enforcement with proper SSL/TLS configuration
- File access restrictions and directory protection
- Error reporting configuration (disabled in production)
- Regular security audits and updates

### 4. Environment-Specific Configuration
- **Development**: Debug mode enabled, detailed error reporting
- **Production**: Optimized performance, minimal error output, security hardening
- **Maintenance**: Maintenance mode for updates and migrations

## Development Workflow

### 1. Local Development
- PHP built-in server with router.php for development
- File-based configuration for easy version control
- Direct file editing with proper permissions
- Development mode for debugging and testing

### 2. Plugin Development
- Plugin directory structure and organization
- Hook integration and system extension
- Admin interface development and integration
- Testing and validation procedures

### 3. Theme Development
- Template creation with modular system
- Asset compilation and optimization
- Theme options configuration and testing
- Responsive design and cross-browser testing

### 4. Testing and Quality Assurance
- Cross-browser compatibility testing
- Mobile responsiveness validation
- Performance testing and optimization
- Security testing and vulnerability assessment

This architecture provides a solid foundation for a lightweight, extensible content management system with strong separation of concerns, clear extension points for customization, and robust security practices through proper file ownership and standard permissions. 