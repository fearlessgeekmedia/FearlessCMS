<?php
// Simulate a delete user request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'delete_user';
$_POST['username'] = 'bob';

// Include the admin index file to test the flow
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Start session
session_start();
$_SESSION['username'] = 'admin';

echo "Testing delete user functionality...\n";
echo "POST action: " . $_POST['action'] . "\n";
echo "POST username: " . $_POST['username'] . "\n";

// Test the user management logic directly
$usersFile = CONFIG_DIR . '/users.json';
$users = json_decode(file_get_contents($usersFile), true);

echo "Before deletion: " . count($users) . " users\n";
foreach ($users as $user) {
    echo "- " . $user['username'] . "\n";
}

// Find the user to delete
$userIndex = array_search($_POST['username'], array_column($users, 'username'));

if ($userIndex === false) {
    echo "User not found\n";
} else {
    echo "User found at index: " . $userIndex . "\n";
    
    // Check if it's the last admin user
    $adminCount = count(array_filter($users, fn($u) => $u['role'] === 'administrator'));
    if ($users[$userIndex]['role'] === 'administrator' && $adminCount <= 1) {
        echo "Cannot delete the last admin user\n";
    } else {
        // Delete the user
        array_splice($users, $userIndex, 1);
        if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT))) {
            echo "User deleted successfully\n";
        } else {
            echo "Failed to delete user\n";
        }
    }
}

echo "After deletion: " . count($users) . " users\n";
foreach ($users as $user) {
    echo "- " . $user['username'] . "\n";
}
?> 