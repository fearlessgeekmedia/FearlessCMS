<?php
// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Include required files
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

echo "<h1>Login Test</h1>";

// Test 1: Check if users file exists
$users_file = CONFIG_DIR . '/users.json';
echo "<h2>Test 1: Users File</h2>";
echo "Looking for users file at: " . $users_file . "<br>";
if (file_exists($users_file)) {
    echo "✅ Users file exists<br>";
    $users = json_decode(file_get_contents($users_file), true);
    echo "Users data: <pre>" . print_r($users, true) . "</pre>";
} else {
    echo "❌ Users file not found<br>";
}

// Test 2: Check if roles file exists
$roles_file = CONFIG_DIR . '/roles.json';
echo "<h2>Test 2: Roles File</h2>";
echo "Looking for roles file at: " . $roles_file . "<br>";
if (file_exists($roles_file)) {
    echo "✅ Roles file exists<br>";
    $roles = json_decode(file_get_contents($roles_file), true);
    echo "Roles data: <pre>" . print_r($roles, true) . "</pre>";
} else {
    echo "❌ Roles file not found<br>";
}

// Test 3: Try to login with admin user
echo "<h2>Test 3: Login Test</h2>";
$username = 'admin';
$password = 'changeme123'; // Default password

echo "Attempting login with:<br>";
echo "Username: " . htmlspecialchars($username) . "<br>";
echo "Password: " . htmlspecialchars($password) . "<br>";

if (login($username, $password)) {
    echo "✅ Login successful<br>";
    echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";
} else {
    echo "❌ Login failed<br>";
}

// Test 4: Check permissions
echo "<h2>Test 4: Permission Test</h2>";
if (isset($_SESSION['username'])) {
    $permissions = [
        'manage_content',
        'manage_plugins',
        'manage_themes',
        'manage_menus',
        'manage_settings'
    ];
    
    foreach ($permissions as $permission) {
        $result = fcms_check_permission($_SESSION['username'], $permission);
        echo "Permission '$permission': " . ($result ? "✅" : "❌") . "<br>";
    }
} else {
    echo "❌ Not logged in, cannot test permissions<br>";
} 