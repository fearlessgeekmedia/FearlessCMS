<?php
// Load configuration
require_once dirname(__DIR__) . '/includes/config.php';
$configFile = CONFIG_DIR . '/config.json';
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$adminPath = $config['admin_path'] ?? 'admin';

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if (function_exists('session_destroy')) {
        session_destroy();
    }
    header('Location: /' . $adminPath . '/index.php');
    exit;
}
?>
