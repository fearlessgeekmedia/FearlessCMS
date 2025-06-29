# FearlessCMS Plugin Development Guide

FearlessCMS features a powerful plugin system that allows you to extend the functionality of your website. This guide will walk you through creating your own plugins.

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
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_settings':
                $settings = [
                    'option1' => trim($_POST['option1'] ?? ''),
                    'option2' => isset($_POST['option2'])
                ];
                your_plugin_save_settings($settings);
                $success_message = 'Settings saved successfully!';
                break;
        }
    }
    
    // Load current settings
    $settings = your_plugin_get_settings();
    
    // Start output buffer
    ob_start();
    ?>
    
    <?php if (isset($success_message)): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>
    
    <h2 class="text-2xl font-bold mb-6">Your Plugin Settings</h2>
    
    <form method="POST" class="space-y-6">
        <input type="hidden" name="action" value="save_settings">
        
        <div>
            <label class="block font-medium mb-1">Option 1</label>
            <input type="text" name="option1" 
                   value="<?= htmlspecialchars($settings['option1']) ?>" 
                   class="w-full border rounded px-3 py-2">
        </div>
        
        <div>
            <label class="block font-medium mb-1">Option 2</label>
            <input type="checkbox" name="option2" 
                   <?= $settings['option2'] ? 'checked' : '' ?>>
            <span class="ml-2">Enable this feature</span>
        </div>
        
        <div>
            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                Save Settings
            </button>
        </div>
    </form>
    
    <?php
    return ob_get_clean();
}
```

## Hooks and Filters

FearlessCMS provides a hook system similar to WordPress. Here are the available hooks:

### Available Hooks

- `init` - Called when the system initializes
- `before_content` - Called before content is processed
- `after_content` - Called after content is processed
- `before_render` - Called before the template is rendered
- `after_render` - Called after the template is rendered
- `route` - Called when handling URL routes
- `content` - Called to process content (filter)
- `check_permission` - Called to check user permissions

### Adding Hooks

```php
// Add a hook
fcms_add_hook('hook_name', 'your_callback_function');

// Add a filter
fcms_add_hook('content', 'your_content_filter');
```

### Hook Examples

```php
// Initialize plugin
fcms_add_hook('init', 'your_plugin_init');

// Process content
fcms_add_hook('content', function($content) {
    // Replace [shortcode] with content
    $content = preg_replace('/\[shortcode\]/', 'Replaced content', $content);
    return $content;
});

// Modify template before rendering
fcms_add_hook('before_render', function(&$template, $path = null) {
    // Change template based on path
    if ($path === 'special-page') {
        $template = 'special-template';
    }
});
```

## Route Handling

Plugins can handle custom URL routes:

```php
fcms_add_hook('route', function(&$handled, &$title, &$content, $path) {
    // Check if this is your plugin's route
    if (preg_match('#^your-plugin(?:/([^/]+))?$#', $path, $matches)) {
        $handled = true;
        $title = 'Your Plugin Page';
        
        if (!empty($matches[1])) {
            // Handle sub-route
            $sub_route = $matches[1];
            $content = your_plugin_handle_sub_route($sub_route);
        } else {
            // Handle main route
            $content = your_plugin_main_page();
        }
    }
});

function your_plugin_main_page() {
    return '<h1>Welcome to Your Plugin</h1><p>This is your plugin\'s main page.</p>';
}

function your_plugin_handle_sub_route($route) {
    return '<h1>Sub-route: ' . htmlspecialchars($route) . '</h1>';
}
```

## Content Processing

### Shortcodes

```php
fcms_add_hook('content', function($content) {
    // Process [your_shortcode] shortcodes
    $content = preg_replace_callback('/\[your_shortcode(.*?)\]/', function($matches) {
        $attributes = [];
        if (!empty($matches[1])) {
            // Parse attributes like [your_shortcode attr1="value1" attr2="value2"]
            preg_match_all('/(\w+)="([^"]*)"/', $matches[1], $attr_matches);
            for ($i = 0; $i < count($attr_matches[1]); $i++) {
                $attributes[$attr_matches[1][$i]] = $attr_matches[2][$i];
            }
        }
        
        return your_shortcode_handler($attributes);
    }, $content);
    
    return $content;
});

function your_shortcode_handler($attributes) {
    $text = $attributes['text'] ?? 'Default text';
    return '<div class="your-shortcode">' . htmlspecialchars($text) . '</div>';
}
```

## Security Considerations

### Input Validation

```php
function your_plugin_validate_input($input) {
    // Sanitize text input
    $input = trim($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    return $input;
}

function your_plugin_validate_file($file) {
    // Check file type
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed_types)) {
        return false;
    }
    
    // Check file size (5MB limit)
    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }
    
    return true;
}
```

## Testing Your Plugin

### Development Testing

1. Create your plugin directory in `plugins/`
2. Add your plugin to `config/active_plugins.json`
3. Test functionality in the admin interface
4. Check error logs for any issues

### Debugging

```php
// Add debug logging
error_log("Your Plugin: " . print_r($data, true));

// Enable error reporting during development
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Best Practices

1. **Follow naming conventions** - Use lowercase with hyphens for directory names
2. **Document your code** - Add comments and create README files
3. **Handle errors gracefully** - Always check for file existence and permissions
4. **Use constants** - Define paths and configuration values as constants
5. **Validate input** - Always sanitize and validate user input
6. **Test thoroughly** - Test all functionality before distribution
7. **Keep it simple** - Don't overcomplicate your plugin
8. **Follow security practices** - Implement proper security measures

## Advanced Examples

### Blog Plugin Pattern

The blog plugin demonstrates a complete plugin with:
- Admin interface for content management
- Custom routes for public pages
- File upload handling
- Rich text editing
- Data persistence

### SEO Plugin Pattern

The SEO plugin shows:
- Settings management
- Content processing
- Template modification
- Meta tag injection

### Forms Plugin Pattern

The forms plugin illustrates:
- Complex form handling
- Email integration
- File uploads
- Data validation
- Admin interface for form management

Study these existing plugins for advanced implementation patterns and best practices.

## Conclusion

The FearlessCMS plugin system provides a powerful and flexible way to extend your website's functionality. By following these guidelines and studying the existing plugins, you can create robust, secure, and maintainable plugins that integrate seamlessly with the CMS.

Remember to test thoroughly, document your code, and follow security best practices. The plugin system is designed to be simple yet powerful, allowing you to focus on your plugin's functionality rather than complex integration details. 