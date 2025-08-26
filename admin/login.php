<?php

// Session is already started by index.php, so we don't need to start it again
// Just ensure we have access to the required functions

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Apply security headers
set_security_headers();

// Set appropriate error reporting for production
if (getenv('FCMS_DEBUG') === 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
}

// Load configuration
$configFile = CONFIG_DIR . '/config.json';
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$adminPath = $config['admin_path'] ?? 'admin';

// Generate CSRF token for the form
generate_csrf_token();

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    // Use JavaScript redirect instead of PHP headers to avoid "headers already sent" error
    echo '<script>window.location.href = "/' . $adminPath . '?action=dashboard";</script>';
    exit;
}

// Process login attempt
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Log login attempt for security monitoring
    error_log("Login attempt for user: " . $username . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

    // Validate CSRF token
    if (!validate_csrf_token()) {
        error_log("CSRF token validation failed for user: " . $username);
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } else {
        // Check rate limiting before attempting login
        if (!check_login_rate_limit($username)) {
            error_log("Rate limit exceeded for user: " . $username);
            $error = 'Too many login attempts. Please wait 15 minutes before trying again.';
        } elseif (login($username, $password)) {
            error_log("Successful login for user: " . $username . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            // Use JavaScript redirect instead of PHP headers to avoid "headers already sent" error
            echo '<script>window.location.href = "/' . $adminPath . '?action=dashboard";</script>';
            exit;
        } else {
            error_log("Failed login for user: " . $username . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            $error = 'Invalid username or password';
        }
    }
}

// Show login page directly
include ADMIN_TEMPLATE_DIR . '/login.php';
?>
