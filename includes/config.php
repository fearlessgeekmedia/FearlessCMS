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
define('PROJECT_ROOT', $project_root);
define('CONTENT_DIR', PROJECT_ROOT . '/content');
// Allow config override via environment variable
$env_config_dir = getenv('FCMS_CONFIG_DIR');
define('CONFIG_DIR', $env_config_dir ? $env_config_dir : PROJECT_ROOT . '/config');
define('THEMES_DIR', PROJECT_ROOT . '/themes');
define('PLUGINS_DIR', PROJECT_ROOT . '/plugins');
$env_admin_config_dir = getenv('FCMS_ADMIN_CONFIG_DIR');
define('ADMIN_CONFIG_DIR', $env_admin_config_dir ? $env_admin_config_dir : PROJECT_ROOT . '/admin/config');
define('ADMIN_TEMPLATE_DIR', PROJECT_ROOT . '/admin/templates');
define('ADMIN_INCLUDES_DIR', PROJECT_ROOT . '/admin/includes');

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
define('BASE_URL', $base_url);

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

// Create default users.json if it doesn't exist
$usersFile = CONFIG_DIR . '/users.json';
if (!file_exists($usersFile)) {
    $defaultUsers = [
        [
            'id' => 'admin',
            'username' => 'admin',
            'password' => password_hash('admin', PASSWORD_DEFAULT),
            'role' => 'administrator'
        ]
    ];
    file_put_contents($usersFile, json_encode($defaultUsers, JSON_PRETTY_PRINT));
}

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
        'site_version' => '1.0.0',
        'admin_path' => 'admin'
    ];
    file_put_contents($configFile, json_encode($defaultConfig, JSON_PRETTY_PRINT));
}

// Configure session settings BEFORE any session starts
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', 3600); // 1 hour
    ini_set('session.cookie_lifetime', 0); // Session cookie
    ini_set('session.cookie_path', '/'); // Ensure cookie is available for all paths
} 