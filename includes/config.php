<?php

/**
 * FearlessCMS
 *
 * Copyright (C) 2026 FearlessCMS
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * As a special exception to the GNU General Public License version 2
 * ("GPL"), the copyright holder(s) of FearlessCMS give you permission
 * to link, load, or combine this software with independent modules
 * ("Themes" and "Plugins") that are not derived from or based on this
 * software, regardless of the license terms of those Themes or
 * Plugins, including proprietary or commercial licenses, and to
 * convey the resulting combination under terms of your choice,
 * provided that you also convey the corresponding source code of the
 * FearlessCMS portions under the terms of the GPL.
 *
 * This exception does not affect any of your obligations under the
 * GPL with respect to the FearlessCMS core software itself, and it
 * does not extend to modifications of FearlessCMS core files — only
 * to independent Theme and Plugin modules that interact with
 * FearlessCMS through its documented Theme/Plugin API.
 */

/**
 * Core configuration file
 */

// Load .env file if present
if (!function_exists('load_env_file')) {
    function load_env_file(string $path): void {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || strpos($trimmed, '#') === 0) {
                continue;
            }
            $parts = explode('=', $trimmed, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                if ($key !== '' && getenv($key) === false) {
                    putenv("$key=$value");
                }
            }
        }
    }
}

$envPath = dirname(__DIR__) . '/.env';
load_env_file($envPath);

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
