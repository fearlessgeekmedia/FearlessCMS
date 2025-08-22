<?php
// Check if session extension is loaded
if (!extension_loaded('session') || !function_exists('session_start')) {
    error_log("Warning: Session functionality not available in logout");
    header('Location: /admin/login');
    exit;
}

// Session should already be started by session.php
if (function_exists('session_destroy')) {
    session_destroy();
}
require_once dirname(__DIR__) . '/includes/config.php';
$configFile = CONFIG_DIR . '/config.json';
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$adminPath = $config['admin_path'] ?? 'admin';
header('Location: /' . $adminPath . '/login');
exit; 