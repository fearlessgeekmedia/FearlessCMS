<?php
/**
 * Core configuration file
 */

// Define root paths
define('PROJECT_ROOT', dirname(__DIR__));
define('CONTENT_DIR', PROJECT_ROOT . '/content');
define('CONFIG_DIR', PROJECT_ROOT . '/config');
define('THEMES_DIR', PROJECT_ROOT . '/themes');
define('PLUGINS_DIR', PROJECT_ROOT . '/plugins');
define('ADMIN_CONFIG_DIR', PROJECT_ROOT . '/admin/config');
define('ADMIN_TEMPLATE_DIR', PROJECT_ROOT . '/admin/templates');
define('ADMIN_INCLUDES_DIR', PROJECT_ROOT . '/admin/includes');

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
        'site_version' => '1.0.0'
    ];
    file_put_contents($configFile, json_encode($defaultConfig, JSON_PRETTY_PRINT));
} 