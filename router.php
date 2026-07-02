<?php
// Router for PHP development server
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Load config to get admin path
$configFile = __DIR__ . '/config/config.json';
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$adminPath = $config['admin_path'] ?? 'admin';

// Special handling for admin routes
if (strpos($uri, '/' . $adminPath) === 0) {
    // Handle login route separately to avoid redirect loops
    if ($uri === '/' . $adminPath . '/login') {
        // Ensure session is started before including login.php
        require_once __DIR__ . '/includes/session.php';
        require_once __DIR__ . '/admin/login.php';
        return true;
    }
    
    // All other admin routes go to admin/index.php
    require_once __DIR__ . '/admin/index.php';
    return true;
}

// Special handling for uploads - route to uploads.php handler
if (strpos($uri, '/uploads/') === 0) {
    require_once __DIR__ . '/uploads.php';
    return true;
}

// If the file exists, serve it directly
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Otherwise, route everything to index.php
require_once __DIR__ . '/index.php';
?>
