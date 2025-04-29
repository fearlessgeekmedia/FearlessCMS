<?php
// router.php for PHP built-in server

if (php_sapi_name() == 'cli-server') {
    $url  = parse_url($_SERVER["REQUEST_URI"]);
    $path = $url["path"];
    $file = __DIR__ . $path;

    // Serve static files directly
    if (is_file($file)) {
        return false;
    }

    // Route /admin, /admin/, and /admin/anything to /admin/index.php
    if (preg_match('#^/admin($|/.*)#', $path)) {
        require __DIR__ . '/admin/index.php';
        exit;
    }
}

// All other requests go to the main site
require_once __DIR__ . '/index.php';
