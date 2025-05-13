<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Debug output
error_log("Login handler called");
error_log("POST data: " . print_r($_POST, true));
error_log("Session before login: " . print_r($_SESSION, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    error_log("Login attempt for user: " . $username);
    
    if (empty($username) || empty($password)) {
        error_log("Empty username or password");
        header('Location: /admin?action=login&error=empty_fields');
        exit;
    }
    
    if (login($username, $password)) {
        error_log("Login successful for user: " . $username);
        error_log("Session after login: " . print_r($_SESSION, true));
        header('Location: /admin?action=dashboard');
        exit;
    } else {
        error_log("Login failed for user: " . $username);
        header('Location: /admin?action=login&error=invalid_credentials');
        exit;
    }
}

// If we get here, something went wrong
error_log("Invalid request to login handler");
header('Location: /admin?action=login&error=invalid_request');
exit;
?>
