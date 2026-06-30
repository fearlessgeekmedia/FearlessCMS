<?php
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/config.php';

// Development mode check
if (!is_development_mode()) {
    $configFile = CONFIG_DIR . '/config.json';
    $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
    $adminPath = $config['admin_path'] ?? 'admin';
    header('Location: /' . $adminPath . '?action=dashboard');
    exit;
}

// Authentication check
if (!isLoggedIn()) {
    $configFile = CONFIG_DIR . '/config.json';
    $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
    $adminPath = $config['admin_path'] ?? 'admin';
    header('Location: /' . $adminPath . '?action=login');
    exit;
}

file_put_contents(__DIR__ . '/testdebug.log', date('c') . "\n", FILE_APPEND);
echo 'testdebug';
?>