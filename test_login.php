<?php
// Simple test login handler
require_once 'includes/session.php';
require_once 'includes/auth.php';

// Set content type for JSON responses
header('Content-Type: application/json');

// Handle token request
if ($_GET['action'] === 'get_token') {
    $token = generate_csrf_token();
    echo json_encode([
        'token' => $token,
        'session_id' => session_id(),
        'session_status' => function_exists('session_status') ? session_status() : 'unknown'
    ]);
    exit;
}

// Handle login attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (!validate_csrf_token()) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid security token',
            'debug' => [
                'post_token' => $csrf_token,
                'session_token' => $_SESSION['csrf_token'] ?? 'NOT SET',
                'session_id' => session_id(),
                'cookies' => $_COOKIE
            ]
        ]);
        exit;
    }
    
    // Simple validation
    if (empty($username) || empty($password)) {
        echo json_encode([
            'success' => false,
            'error' => 'Username and password are required'
        ]);
        exit;
    }
    
    // For testing, accept any non-empty credentials
    echo json_encode([
        'success' => true,
        'message' => 'Login successful (test mode)',
        'debug' => [
            'username' => $username,
            'session_id' => session_id(),
            'csrf_token_valid' => true
        ]
    ]);
    exit;
}

// Default response
echo json_encode([
    'success' => false,
    'error' => 'Invalid request method'
]);
?> 