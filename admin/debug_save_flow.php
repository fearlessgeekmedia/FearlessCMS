<?php
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/config.php';

// Authentication check
if (!isLoggedIn()) {
    $configFile = CONFIG_DIR . '/config.json';
    $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
    $adminPath = $config['admin_path'] ?? 'admin';
    header('Location: /' . $adminPath . '?action=login');
    exit;
}

// CSRF token validation for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validate_csrf_token()) {
    die('Invalid security token. Please refresh the page and try again.');
}

// For non-POST requests, redirect to admin dashboard
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $configFile = CONFIG_DIR . '/config.json';
    $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
    $adminPath = $config['admin_path'] ?? 'admin';
    header('Location: /' . $adminPath . '?action=dashboard');
    exit;
}

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Save Flow Debug Script</h1>";

echo "<h2>POST Request Received</h2>";
echo "<pre>POST data: " . htmlspecialchars(print_r($_POST, true)) . "</pre>";

if (isset($_POST['action']) && $_POST['action'] === 'save_content') {
    $fileName = $_POST['path'] ?? '';
    $content = $_POST['content'] ?? '';
    
    // Validate path using the security function
    $filePath = validate_content_path($fileName);
    if ($filePath === false) {
        echo "<p><strong>Error:</strong> Invalid file path - path traversal detected</p>";
        exit;
    }
    
    // Only allow .md files
    if (!str_ends_with($filePath, '.md')) {
        $filePath .= '.md';
    }
    
    echo "<h3>Processing Save Request</h3>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($fileName) . "</p>";
    echo "<p><strong>Content Length:</strong> " . strlen($content) . "</p>";
    echo "<p><strong>Full Path:</strong> " . htmlspecialchars($filePath) . "</p>";
    
    if (file_exists($filePath)) {
        echo "<p><strong>File exists:</strong> Yes</p>";
        echo "<p><strong>Current content length:</strong> " . strlen(file_get_contents($filePath)) . "</p>";
    } else {
        echo "<p><strong>File exists:</strong> No</p>";
    }
    
    // Simulate the save
    if (file_put_contents($filePath, $content) !== false) {
        echo "<p><strong>Save result:</strong> Success</p>";
        echo "<p><strong>New content length:</strong> " . strlen(file_get_contents($filePath)) . "</p>";
        
        // Test the redirect
        $redirectPath = str_replace('.md', '', $fileName);
        $timestamp = time();
        $redirectUrl = '?action=edit_content&path=' . urlencode($redirectPath) . '&saved=1&_t=' . $timestamp;
        
        echo "<h3>Redirect Test</h3>";
        echo "<p><strong>Redirect URL:</strong> " . htmlspecialchars($redirectUrl) . "</p>";
        echo "<p><a href='" . htmlspecialchars($redirectUrl) . "'>Click here to test redirect</a></p>";
        
    } else {
        echo "<p><strong>Save result:</strong> Failed</p>";
    }
}

echo "<hr>";
echo "<p><a href='index.php'>Back to Admin</a></p>";
?>