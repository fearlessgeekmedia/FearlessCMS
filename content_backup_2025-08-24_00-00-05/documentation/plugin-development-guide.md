<!-- json {
    "title": "Plugin Development Guide",
    "template": "documentation"
} -->

# FearlessCMS Plugin Development Guide

FearlessCMS features a powerful plugin system that allows you to extend the functionality of your website. This guide will walk you through creating your own plugins.

## Table of Contents

1. [Plugin Structure](#plugin-structure)
2. [Basic Plugin Example](#basic-plugin-example)
3. [Plugin Configuration](#plugin-configuration)
4. [Admin Interface Integration](#admin-interface-integration)
5. [Hooks and Filters](#hooks-and-filters)
6. [Route Handling](#route-handling)
7. [Content Processing](#content-processing)
8. [Database Integration](#database-integration)
9. [Security Considerations](#security-considerations)
10. [Testing Your Plugin](#testing-your-plugin)
11. [Distribution](#distribution)

## Plugin Structure

A FearlessCMS plugin consists of the following structure:

```
plugins/
└── your-plugin-name/
    ├── plugin.json          # Plugin metadata
    ├── your-plugin-name.php # Main plugin file
    ├── includes/            # Additional PHP files
    ├── templates/           # HTML templates
    ├── assets/              # CSS, JS, images
    └── README.md            # Documentation
```

### Required Files

#### plugin.json
This file contains metadata about your plugin:

```json
{
    "name": "Your Plugin Name",
    "description": "A brief description of what your plugin does",
    "version": "1.0.0",
    "author": "Your Name",
    "main": "your-plugin-name.php",
    "features": [
        "Feature 1",
        "Feature 2"
    ]
}
```

#### Main Plugin File
The main PHP file should follow this naming convention: `your-plugin-name.php` (where `your-plugin-name` matches the directory name).

## Basic Plugin Example

Here's a simple plugin that adds a custom function:

```php
<?php
/*
Plugin Name: Example Plugin
Description: A simple example plugin for FearlessCMS
Version: 1.0
Author: Your Name
*/

// Define constants for your plugin
define('EXAMPLE_PLUGIN_DIR', PLUGIN_DIR . '/example-plugin');
define('EXAMPLE_PLUGIN_DATA_DIR', CONTENT_DIR . '/example-plugin-data');

// Initialize the plugin
function example_plugin_init() {
    // Create necessary directories
    if (!file_exists(EXAMPLE_PLUGIN_DATA_DIR)) {
        mkdir(EXAMPLE_PLUGIN_DATA_DIR, 0755, true);
    }
    
    // Register admin section
    fcms_register_admin_section('example', [
        'label' => 'Example Plugin',
        'menu_order' => 50,
        'parent' => 'plugins',
        'render_callback' => 'example_admin_page'
    ]);
    
    // Register hooks
    fcms_add_hook('content', 'example_process_content');
}

// Admin page callback
function example_admin_page() {
    ob_start();
    ?>
    <h2 class="text-2xl font-bold mb-6">Example Plugin Settings</h2>
    <p>This is your plugin's admin interface.</p>
    <?php
    return ob_get_clean();
}

// Content processing hook
function example_process_content($content) {
    // Process the content here
    return $content;
}

// Initialize the plugin
example_plugin_init();
```

## Plugin Configuration

### Constants

Define constants at the top of your main plugin file:

```php
// Plugin directory
define('YOUR_PLUGIN_DIR', PLUGIN_DIR . '/your-plugin-name');

// Data directory (for storing plugin data)
define('YOUR_PLUGIN_DATA_DIR', CONTENT_DIR . '/your-plugin-data');

// Configuration file
define('YOUR_PLUGIN_CONFIG', YOUR_PLUGIN_DATA_DIR . '/config.json');
```

### Settings Management

```php
function your_plugin_get_settings() {
    $defaults = [
        'option1' => 'default_value',
        'option2' => true,
        'option3' => []
    ];
    
    if (file_exists(YOUR_PLUGIN_CONFIG)) {
        $settings = json_decode(file_get_contents(YOUR_PLUGIN_CONFIG), true);
        if (is_array($settings)) {
            return array_merge($defaults, $settings);
        }
    }
    
    return $defaults;
}

function your_plugin_save_settings($settings) {
    return file_put_contents(YOUR_PLUGIN_CONFIG, json_encode($settings, JSON_PRETTY_PRINT));
}
```

## Admin Interface Integration

### Registering Admin Sections

```php
fcms_register_admin_section('your_plugin', [
    'label' => 'Your Plugin',
    'menu_order' => 50, // Lower numbers appear first
    'parent' => 'plugins', // Optional: makes it a submenu
    'render_callback' => 'your_plugin_admin_page'
]);
```

### Admin Page Implementation

```php
function your_plugin_admin_page() {
    $success_message = '';
    $error_message = '';
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_settings':
                $settings = [
                    'option1' => trim($_POST['option1'] ?? ''),
                    'option2' => isset($_POST['option2'])
                ];
                if (your_plugin_save_settings($settings)) {
                    $success_message = 'Settings saved successfully!';
                } else {
                    $error_message = 'Failed to save settings.';
                }
                break;
        }
    }
    
    // Load current settings
    $settings = your_plugin_get_settings();
    
    // Start output buffer
    ob_start();
    ?>
    
    <?php if ($success_message): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>
    
    <h2 class="text-2xl font-bold mb-6">Your Plugin Settings</h2>
    
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="save_settings">
        
        <div>
            <label class="block font-medium mb-1">Option 1</label>
            <input type="text" name="option1" 
                   value="<?= htmlspecialchars($settings['option1']) ?>" 
                   class="w-full border rounded px-3 py-2">
        </div>
        
        <div>
            <label class="flex items-center">
                <input type="checkbox" name="option2" 
                       <?= $settings['option2'] ? 'checked' : '' ?> 
                       class="mr-2">
                Option 2
            </label>
        </div>
        
        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
            Save Settings
        </button>
    </form>
    
    <?php
    return ob_get_clean();
}
```

## Hooks and Filters

FearlessCMS uses a hook system to allow plugins to extend functionality. There are three types of hooks:

### 1. Action Hooks (By-Value)
Use `fcms_do_hook()` for hooks that return values:

```php
// Register a hook
fcms_add_hook('my_custom_hook', 'my_callback_function');

// Call the hook
$result = fcms_do_hook('my_custom_hook', $param1, $param2);
```

### 2. Action Hooks (By-Reference)
Use `fcms_do_hook_ref()` for hooks that need to modify variables by reference:

```php
// Register a hook
fcms_add_hook('before_render', 'my_render_callback');

// Call the hook (in core code)
fcms_do_hook_ref('before_render', $content, $page_data);
```

### 3. Filter Hooks
Use `fcms_apply_filter()` for value filters:

```php
// Register a filter
fcms_add_hook('content', 'my_content_filter');

// Apply the filter
$filtered_content = fcms_apply_filter('content', $original_content);
```

### Available Core Hooks

- `init`: Plugin initialization
- `before_content`: Before content rendering
- `after_content`: After content rendering
- `before_render`: Before template rendering (by-reference)
- `after_render`: After template rendering
- `route`: Custom routing (by-reference)
- `check_permission`: Permission checking
- `content`: Content filtering
- `filter_admin_sections`: Admin section filtering

## Route Handling

Plugins can register custom routes:

```php
function my_plugin_route_handler(&$route, &$params) {
    if ($route === 'my-plugin') {
        // Handle the route
        $content = my_plugin_render_page();
        echo $content;
        return true; // Route handled
    }
    return false; // Route not handled
}

fcms_add_hook('route', 'my_plugin_route_handler');
```

## Content Processing

Plugins can modify content before it's displayed:

```php
function my_content_processor($content) {
    // Add custom processing here
    $content = str_replace('[custom_tag]', 'Custom Content', $content);
    return $content;
}

fcms_add_hook('content', 'my_content_processor');
```

## Database Integration

FearlessCMS supports database-backed plugins through the MariaDB Connector plugin.

### Using the MariaDB Connector

First, ensure the MariaDB Connector plugin is enabled, then use the provided hooks:

```php
// Get a database connection
$pdo = fcms_do_hook('database_connect');
if ($pdo) {
    // Use the connection
    $stmt = $pdo->query('SELECT * FROM my_table');
    $rows = $stmt->fetchAll();
}

// Execute a parameterized query
$stmt = fcms_do_hook('database_query', 'SELECT * FROM users WHERE id = ?', [1]);
if ($stmt) {
    $user = $stmt->fetch();
}
```

### Creating Database Tables

```php
function my_plugin_create_tables() {
    $pdo = fcms_do_hook('database_connect');
    if (!$pdo) return false;
    
    $sql = "CREATE TABLE IF NOT EXISTS my_plugin_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    try {
        $pdo->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("Failed to create table: " . $e->getMessage());
        return false;
    }
}
```

## Security Considerations

### Input Validation
Always validate and sanitize user input:

```php
function my_plugin_save_data($input) {
    // Validate input
    $title = trim($input['title'] ?? '');
    if (empty($title) || strlen($title) > 255) {
        return false;
    }
    
    // Sanitize content
    $content = htmlspecialchars($input['content'] ?? '', ENT_QUOTES, 'UTF-8');
    
    // Save to database
    $pdo = fcms_do_hook('database_connect');
    if ($pdo) {
        $stmt = $pdo->prepare('INSERT INTO my_table (title, content) VALUES (?, ?)');
        return $stmt->execute([$title, $content]);
    }
    
    return false;
}
```

### File Upload Security
If your plugin handles file uploads:

```php
function my_plugin_handle_upload($file) {
    // Check file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    // Check file size (e.g., 5MB limit)
    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }
    
    // Generate safe filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    
    // Move to safe location
    $upload_dir = YOUR_PLUGIN_DATA_DIR . '/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    return move_uploaded_file($file['tmp_name'], $upload_dir . $filename);
}
```

### Configuration Security
Never hardcode sensitive information like database credentials:

```php
// ❌ Bad - hardcoded credentials
$config = [
    'host' => 'localhost',
    'username' => 'myuser',
    'password' => 'mypassword'
];

// ✅ Good - load from configuration
$config = your_plugin_get_settings();
```

## Testing Your Plugin

### Development Testing
1. Enable your plugin in the admin interface
2. Test all functionality in a development environment
3. Check for PHP errors in the error log
4. Test with different CMS modes

### Error Handling
Always include proper error handling:

```php
function my_plugin_function() {
    try {
        // Your plugin logic here
        return true;
    } catch (Exception $e) {
        error_log("My Plugin Error: " . $e->getMessage());
        return false;
    }
}
```

### Debugging
Use error logging for debugging:

```php
error_log("My Plugin Debug: " . print_r($data, true));
```

## Distribution

### Plugin Package Structure
When distributing your plugin, include:

```
your-plugin-name/
├── plugin.json
├── your-plugin-name.php
├── README.md
├── CHANGELOG.md
└── LICENSE
```

### README.md Template
```markdown
# Your Plugin Name

Brief description of what your plugin does.

## Installation

1. Upload the plugin to `/plugins/your-plugin-name/`
2. Enable the plugin in the admin interface
3. Configure settings in the plugin admin page

## Features

- Feature 1
- Feature 2
- Feature 3

## Configuration

Describe configuration options and how to use them.

## Changelog

### 1.0.0
- Initial release
- Basic functionality

## Support

How users can get help or report issues.
```

## MariaDB Connector Plugin: Database Integration

The **MariaDB Connector** plugin enables FearlessCMS plugins to use a shared MariaDB/MySQL database connection, extending the flat-file CMS with robust database-backed features.

### What It Does
- Provides a PDO connection to MariaDB/MySQL for all plugins
- Centralizes DB credentials and connection management
- Enables advanced plugins (dynamic blogs, e-commerce, analytics, etc.)
- Allows hybrid flat-file and database-backed plugins to coexist

### Installation & Configuration
1. **Enable the plugin** in the admin Plugins section.
2. Go to **MariaDB Connector** under Plugins in the admin menu.
3. Enter your database credentials and save.
4. Use the 'Test Connection' button to verify connectivity.

Configuration is stored in `content/mariadb-connector/config.json`.

### How Plugins Use the Database
Other plugins should NOT create their own DB connections. Instead, use the provided hooks:

```php
// Get a PDO connection
$pdo = fcms_do_hook('database_connect');
if ($pdo) {
    // Use $pdo as usual
    $stmt = $pdo->query('SELECT * FROM my_table');
    $rows = $stmt->fetchAll();
}

// Execute a parameterized query
$stmt = fcms_do_hook('database_query', 'SELECT * FROM users WHERE id = ?', [1]);
if ($stmt) {
    $user = $stmt->fetch();
}
```

- `database_connect`: Returns a PDO object or null on failure
- `database_query`: Executes a query with parameters, returns PDOStatement or false

### Best Practices
- Always use the provided hooks for DB access
- Do not hardcode credentials or create new PDO instances
- Check for null/false return values to handle connection errors
- Use prepared statements for all queries

### Admin Integration
- The plugin adds a settings page for DB config and connection status
- Only users with plugin management permissions can change DB settings

### Hybrid Content Model
- Flat-file and database-backed plugins can be used together
- You can migrate content types gradually or mix both approaches

## Conclusion

This guide covers the essential aspects of plugin development for FearlessCMS. Remember to:

- Follow security best practices
- Use the hook system for extensibility
- Provide clear documentation
- Test thoroughly before distribution
- Consider using the MariaDB Connector for database-backed features

For more advanced examples, study the existing plugins in the `/plugins/` directory of your FearlessCMS installation.
