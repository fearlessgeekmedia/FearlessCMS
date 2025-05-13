<?php
// router.php for PHP built-in server

if (php_sapi_name() == 'cli-server') {
    $url  = parse_url($_SERVER["REQUEST_URI"]);
    $path = $url["path"];
    $file = __DIR__ . $path;

    error_log("Router: Processing request for path: " . $path);
    error_log("Router: Full URL: " . $_SERVER["REQUEST_URI"]);

    // Serve static files directly
    if (is_file($file)) {
        error_log("Router: Serving static file: " . $file);
        return false;
    }

    // Start session for admin routes
    if (strpos($path, '/admin') === 0) {
        error_log("Router: Admin route detected, starting session");
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        require_once __DIR__ . '/includes/config.php';
        require_once __DIR__ . '/includes/auth.php';
        error_log("Router: Session state: " . print_r($_SESSION, true));
    }

    // Route /admin/login directly
    if ($path === '/admin/login') {
        error_log("Router: Routing to login page");
        require __DIR__ . '/admin/login.php';
        exit;
    }

    // Route /admin or /admin/ to login if not logged in
    if ($path === '/admin' || $path === '/admin/') {
        error_log("Router: Checking login status for /admin");
        if (!isLoggedIn()) {
            error_log("Router: Not logged in, redirecting to login");
            header('Location: /admin/login');
            exit;
        }
        error_log("Router: Logged in, loading admin index");
        require __DIR__ . '/admin/index.php';
        exit;
    }

    // Route /admin/anything else to /admin/index.php
    if (preg_match('#^/admin/.*#', $path)) {
        error_log("Router: Checking login status for admin route: " . $path);
        if (!isLoggedIn()) {
            error_log("Router: Not logged in, redirecting to login");
            header('Location: /admin/login');
            exit;
        }
        error_log("Router: Logged in, loading admin index");
        require __DIR__ . '/admin/index.php';
        exit;
    }
}

// All other requests go to the main site
error_log("Router: Routing to main site");
require_once __DIR__ . '/index.php';
