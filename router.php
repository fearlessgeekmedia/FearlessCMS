<?php
// Router for PHP development server
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Special handling for admin routes - always go through main index.php
if (strpos($uri, '/admin') === 0) {
    require_once __DIR__ . '/index.php';
    return true;
}

// If the file exists, serve it directly
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Otherwise, route everything to index.php
require_once __DIR__ . '/index.php';
?>
