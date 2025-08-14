<?php
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

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Load configuration
$configFile = CONFIG_DIR . '/config.json';
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$adminPath = $config['admin_path'] ?? 'admin';

// Debug output
error_log("Login handler called");
error_log("POST data: " . print_r($_POST, true));
error_log("Session before login: " . print_r($_SESSION, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    error_log("Login attempt for user: " . $username);

    // Validate CSRF token
    if (!validate_csrf_token()) {
        error_log("CSRF token validation failed for user: " . $username);
        header('Location: /' . $adminPath . '?action=login&error=invalid_token');
        exit;
    }

    if (empty($username) || empty($password)) {
        error_log("Empty username or password");
        header('Location: /' . $adminPath . '?action=login&error=empty_fields');
        exit;
    }

    if (login($username, $password)) {
        error_log("Login successful for user: " . $username);
        error_log("Session after login: " . print_r($_SESSION, true));
        header('Location: /' . $adminPath . '?action=dashboard');
        exit;
    } else {
        error_log("Login failed for user: " . $username);
        header('Location: /' . $adminPath . '?action=login&error=invalid_credentials');
        exit;
    }
}

// If we get here, something went wrong
error_log("Invalid request to login handler");
header('Location: /' . $adminPath . '?action=login&error=invalid_request');
exit;
?>
