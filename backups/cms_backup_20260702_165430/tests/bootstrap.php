<?php

/**
 * Test bootstrap for FearlessCMS
 *
 * Sets up a temporary filesystem and the constants/superglobals
 * that the CMS code expects, so tests run without a web server.
 */

// Composer autoloader (loads Pest/PHPUnit)
require_once __DIR__ . '/../vendor/autoload.php';

// --- Build a disposable test fixture directory ---
define('FCMS_TEST_DIR', sys_get_temp_dir() . '/fearlesscms_tests_' . getmypid());

// Clean up from any previous run
if (is_dir(FCMS_TEST_DIR)) {
    (new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(FCMS_TEST_DIR, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    ))->rewind();
    foreach (new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(FCMS_TEST_DIR, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    ) as $item) {
        $item->isDir() ? rmdir($item) : unlink($item);
    }
    rmdir(FCMS_TEST_DIR);
}

mkdir(FCMS_TEST_DIR, 0755, true);
mkdir(FCMS_TEST_DIR . '/content', 0755, true);
mkdir(FCMS_TEST_DIR . '/config', 0755, true);
mkdir(FCMS_TEST_DIR . '/themes/default/templates', 0755, true);
mkdir(FCMS_TEST_DIR . '/plugins', 0755, true);
mkdir(FCMS_TEST_DIR . '/admin/config', 0755, true);
mkdir(FCMS_TEST_DIR . '/admin/templates', 0755, true);
mkdir(FCMS_TEST_DIR . '/admin/includes', 0755, true);
mkdir(FCMS_TEST_DIR . '/cache', 0755, true);
mkdir(FCMS_TEST_DIR . '/sessions', 0755, true);
mkdir(FCMS_TEST_DIR . '/uploads', 0755, true);

// Minimal default theme templates so ThemeManager doesn't throw
file_put_contents(FCMS_TEST_DIR . '/themes/default/templates/page.html', '<html><body>{{content}}</body></html>');
file_put_contents(FCMS_TEST_DIR . '/themes/default/templates/404.html', '<html><body>404</body></html>');

// Minimal config
file_put_contents(FCMS_TEST_DIR . '/config/config.json', json_encode([
    'site_name' => 'TestCMS',
    'site_description' => 'Test site',
    'admin_path' => 'admin',
    'active_theme' => 'default',
], JSON_PRETTY_PRINT));

// Default roles
file_put_contents(FCMS_TEST_DIR . '/config/roles.json', json_encode([
    'administrator' => [
        'label' => 'Administrator',
        'capabilities' => ['manage_users', 'manage_themes', 'manage_plugins', 'edit_content', 'delete_content'],
    ],
    'editor' => [
        'label' => 'Editor',
        'capabilities' => ['edit_content', 'delete_content'],
    ],
], JSON_PRETTY_PRINT));

// Empty menus
file_put_contents(FCMS_TEST_DIR . '/config/menus.json', json_encode([
    'main' => [
        'label' => 'Main Menu',
        'menu_class' => 'main-nav',
        'items' => [
            ['label' => 'Home', 'url' => '/', 'class' => '', 'target' => ''],
        ],
    ],
], JSON_PRETTY_PRINT));

// --- Define CMS constants pointing at the fixture ---
define('PROJECT_ROOT', FCMS_TEST_DIR);
define('CONTENT_DIR', FCMS_TEST_DIR . '/content');
define('CONFIG_DIR', FCMS_TEST_DIR . '/config');
define('THEMES_DIR', FCMS_TEST_DIR . '/themes');
define('PLUGINS_DIR', FCMS_TEST_DIR . '/plugins');
define('ADMIN_CONFIG_DIR', FCMS_TEST_DIR . '/admin/config');
define('ADMIN_TEMPLATE_DIR', FCMS_TEST_DIR . '/admin/templates');
define('ADMIN_INCLUDES_DIR', FCMS_TEST_DIR . '/admin/includes');
define('BASE_URL', 'http://localhost');

// --- Fake superglobals so includes don't blow up ---
$_SERVER['SCRIPT_FILENAME'] = FCMS_TEST_DIR . '/index.php';
$_SERVER['DOCUMENT_ROOT'] = FCMS_TEST_DIR;
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';

// Start a session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_save_path(FCMS_TEST_DIR . '/sessions');
    @session_start();
}

// --- Clean up fixture on shutdown ---
register_shutdown_function(function () {
    $dir = FCMS_TEST_DIR;
    if (!is_dir($dir)) {
        return;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
        $item->isDir() ? @rmdir($item) : @unlink($item);
    }
    @rmdir($dir);
});
