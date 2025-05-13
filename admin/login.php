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

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: /admin?action=dashboard');
    exit;
}

// Process login attempt
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    error_log("Login attempt for user: " . $username);
    
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } else {
        if (login($username, $password)) {
            error_log("Login successful for user: " . $username);
            header('Location: /admin?action=dashboard');
            exit;
        } else {
            error_log("Login failed for user: " . $username);
            $error = 'Invalid username or password';
        }
    }
}

// Show login page directly
include ADMIN_TEMPLATE_DIR . '/login.php';
?> 