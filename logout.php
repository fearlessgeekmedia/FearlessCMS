<?php
session_start();
session_destroy();
require_once dirname(__DIR__) . '/includes/config.php';
$configFile = CONFIG_DIR . '/config.json';
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$adminPath = $config['admin_path'] ?? 'admin';
header('Location: /' . $adminPath . '/login');
exit; 