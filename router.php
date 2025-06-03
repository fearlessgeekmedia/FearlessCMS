<?php
// router.php for PHP built-in server

if (php_sapi_name() == 'cli-server') {
    $url  = parse_url($_SERVER["REQUEST_URI"]);
    $path = $url["path"];
    $file = __DIR__ . $path;

    error_log("Router: Processing request for path: " . $path);
    error_log("Router: Full URL: " . $_SERVER["REQUEST_URI"]);

    // Load configuration
    require_once __DIR__ . '/includes/config.php';
    $configFile = CONFIG_DIR . '/config.json';
    $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
    $adminPath = $config['admin_path'] ?? 'admin';

    // Serve static files directly
    if (is_file($file)) {
        error_log("Router: Serving static file: " . $file);
        return false;
    }

    // Start session for admin routes
    if (strpos($path, '/' . $adminPath) === 0) {
        error_log("Router: Admin route detected, starting session");
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        require_once __DIR__ . '/includes/auth.php';
        error_log("Router: Session state: " . print_r($_SESSION, true));
    }

    // Route /admin/login directly
    if ($path === '/' . $adminPath . '/login') {
        error_log("Router: Routing to login page");
        require __DIR__ . '/admin/login.php';  // Physical path remains 'admin'
        exit;
    }

    // Route /admin or /admin/ to login if not logged in
    if ($path === '/' . $adminPath || $path === '/' . $adminPath . '/') {
        error_log("Router: Checking login status for /" . $adminPath);
        if (!isLoggedIn()) {
            error_log("Router: Not logged in, redirecting to login");
            header('Location: /' . $adminPath . '/login');
            exit;
        }
        error_log("Router: Logged in, loading admin index");
        require __DIR__ . '/admin/index.php';  // Physical path remains 'admin'
        exit;
    }

    // Route /admin/anything else to /admin/index.php
    if (strpos($path, '/' . $adminPath . '/') === 0) {
        error_log("Router: Routing admin subpath to index.php");
        require __DIR__ . '/admin/index.php';  // Physical path remains 'admin'
        exit;
    }

    // If we get here, let the main index.php handle it
    require __DIR__ . '/index.php';
}
