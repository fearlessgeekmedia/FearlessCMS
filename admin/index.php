<?php
// admin/index.php
session_start();

// Configuration
define('ADMIN_CONFIG_DIR', __DIR__ . '/config');
define('ADMIN_TEMPLATE_DIR', __DIR__ . '/templates');

// Create config directory if it doesn't exist
if (!file_exists(ADMIN_CONFIG_DIR)) {
    mkdir(ADMIN_CONFIG_DIR, 0755, true);
}

// Initialize users file if it doesn't exist
$usersFile = ADMIN_CONFIG_DIR . '/users.json';
if (!file_exists($usersFile)) {
    $defaultAdmin = [
        'username' => 'admin',
        // Default password: "changeme123"
        'password' => password_hash('changeme123', PASSWORD_DEFAULT)
    ];
    file_put_contents($usersFile, json_encode([$defaultAdmin]));
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $users = json_decode(file_get_contents($usersFile), true);
    $user = array_filter($users, fn($u) => $u['username'] === $username);
    
    if ($user && password_verify($password, reset($user)['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['username'] = $username;
        // Fixed: Use absolute path for admin redirect
        header('Location: /admin/index.php');
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    // Fixed: Use absolute path for admin redirect
    header('Location: /admin/index.php');
    exit;
}

// Prevent main site routing from affecting admin
unset($_GET['page']);

// Display appropriate template based on login status
if (!isLoggedIn()) {
    $template = file_get_contents(ADMIN_TEMPLATE_DIR . '/login.html');
    $template = str_replace('{{error}}', $error ?? '', $template);
} else {
    $template = file_get_contents(ADMIN_TEMPLATE_DIR . '/dashboard.html');
    
    // Get list of content files
    $contentFiles = glob('../content/*.md');
    $contentList = '';
    foreach ($contentFiles as $file) {
        $filename = basename($file);
        $contentList .= "<li class='py-2 px-4 hover:bg-gray-100'>
            <a href='?edit=" . urlencode($filename) . "'>$filename</a>
        </li>";
    }
    
    $template = str_replace('{{content_list}}', $contentList, $template);
    $template = str_replace('{{username}}', htmlspecialchars($_SESSION['username']), $template);
    
    // Remove any template conditional blocks that aren't implemented yet
    $pattern = '/\{\{if_([a-z_]+)\}\}.*?\{\{\/if_\1\}\}/s';
    $template = preg_replace($pattern, '', $template);
    
    // Remove any remaining template variables
    $template = preg_replace('/\{\{[^}]+\}\}/', '', $template);
}

echo $template;
