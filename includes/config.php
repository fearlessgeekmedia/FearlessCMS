<?php
/**
 * Core configuration file
 */

// Get the document root and script filename
$script_filename = $_SERVER['SCRIPT_FILENAME'];
$document_root = $_SERVER['DOCUMENT_ROOT'];

// Calculate the project root
$project_root = dirname(dirname(__FILE__));

// Define root paths
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', $project_root);
}
if (!defined('CONTENT_DIR')) {
    define('CONTENT_DIR', PROJECT_ROOT . '/content');
}
// Allow config override via environment variable
$env_config_dir = getenv('FCMS_CONFIG_DIR');
if (!defined('CONFIG_DIR')) {
    define('CONFIG_DIR', $env_config_dir ? $env_config_dir : PROJECT_ROOT . '/config');
}
if (!defined('THEMES_DIR')) {
    define('THEMES_DIR', PROJECT_ROOT . '/themes');
}
if (!defined('PLUGINS_DIR')) {
    define('PLUGINS_DIR', PROJECT_ROOT . '/plugins');
}
$env_admin_config_dir = getenv('FCMS_ADMIN_CONFIG_DIR');
if (!defined('ADMIN_CONFIG_DIR')) {
    define('ADMIN_CONFIG_DIR', $env_admin_config_dir ? $env_admin_config_dir : PROJECT_ROOT . '/admin/config');
}
if (!defined('ADMIN_TEMPLATE_DIR')) {
    define('ADMIN_TEMPLATE_DIR', PROJECT_ROOT . '/admin/templates');
}
if (!defined('ADMIN_INCLUDES_DIR')) {
    define('ADMIN_INCLUDES_DIR', PROJECT_ROOT . '/admin/includes');
}

// Define base URL
$base_url = '';
if (isset($_SERVER['HTTP_HOST'])) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $base_url = $protocol . $_SERVER['HTTP_HOST'];

    // Load admin path from config
    $configFile = CONFIG_DIR . '/config.json';
    $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
    $adminPath = $config['admin_path'] ?? 'admin';

    if (strpos($script_filename, '/admin/') !== false) {
        $base_url .= '/' . $adminPath;
    }
}
if (!defined('BASE_URL')) {
    define('BASE_URL', $base_url);
}

// Create required directories if they don't exist
$requiredDirs = [
    CONTENT_DIR,
    CONFIG_DIR,
    THEMES_DIR,
    PLUGINS_DIR,
    ADMIN_CONFIG_DIR,
    ADMIN_TEMPLATE_DIR,
    ADMIN_INCLUDES_DIR
];

foreach ($requiredDirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Default users.json creation removed for security
// Use install.php to create the initial admin user

// Create default roles.json if it doesn't exist
$rolesFile = CONFIG_DIR . '/roles.json';
if (!file_exists($rolesFile)) {
    $defaultRoles = [
        'administrator' => [
            'label' => 'Administrator',
            'capabilities' => [
                'manage_users',
                'manage_themes',
                'manage_plugins',
                    'manage_updates',
                'manage_menus',
                'manage_widgets',
                'edit_content',
                'delete_content',
                'manage_roles'
            ]
        ],
        'editor' => [
            'label' => 'Editor',
            'capabilities' => [
                'edit_content',
                'delete_content'
            ]
        ]
    ];
    file_put_contents($rolesFile, json_encode($defaultRoles, JSON_PRETTY_PRINT));
}

// Create default menus.json if it doesn't exist
$menusFile = CONFIG_DIR . '/menus.json';
if (!file_exists($menusFile)) {
    $defaultMenus = [
        'main' => [
            'label' => 'Main Menu',
            'menu_class' => 'main-nav',
            'items' => [
                [
                    'label' => 'Home',
                    'url' => '/',
                    'item_class' => '',
                    'target' => ''
                ]
            ]
        ]
    ];
    file_put_contents($menusFile, json_encode($defaultMenus, JSON_PRETTY_PRINT));
}

// Create default widgets.json if it doesn't exist
$widgetsFile = ADMIN_CONFIG_DIR . '/widgets.json';
if (!file_exists($widgetsFile)) {
    $defaultWidgets = [
        'left-sidebar' => [
            'name' => 'Left Sidebar',
            'description' => 'Main left sidebar widget area',
            'type' => 'sidebar',
            'location' => 'left-sidebar'
        ],
        'right-sidebar' => [
            'name' => 'Right Sidebar',
            'description' => 'Main right sidebar widget area',
            'type' => 'sidebar',
            'location' => 'right-sidebar'
        ]
    ];
    file_put_contents($widgetsFile, json_encode($defaultWidgets, JSON_PRETTY_PRINT));
}

// Create default theme_options.json if it doesn't exist
$themeOptionsFile = CONFIG_DIR . '/theme_options.json';
if (!file_exists($themeOptionsFile)) {
    $defaultThemeOptions = [
        'logo' => '',
        'herobanner' => ''
    ];
    file_put_contents($themeOptionsFile, json_encode($defaultThemeOptions, JSON_PRETTY_PRINT));
}

// Create default config.json if it doesn't exist
$configFile = CONFIG_DIR . '/config.json';
if (!file_exists($configFile)) {
    $defaultConfig = [
        'site_name' => 'FearlessCMS',
        'site_description' => 'A fearless content management system',
        'site_keywords' => 'cms, content management, fearless',
        'site_author' => 'FearlessGeek',
        'site_version' => '0.1.0b',
        'admin_path' => 'admin'
    ];
    file_put_contents($configFile, json_encode($defaultConfig, JSON_PRETTY_PRINT));
}

// Session configuration is now handled in session.php to prevent headers already sent errors
