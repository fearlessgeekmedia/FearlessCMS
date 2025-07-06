<?php
// Simulate an edit user request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'edit_user';
$_POST['username'] = 'bob';
$_POST['new_username'] = 'bob_updated';
$_POST['new_password'] = 'newpassword123';
$_POST['user_role'] = 'editor';
$_POST['permissions'] = ['manage_content', 'manage_themes'];

// Include the admin index file to test the flow
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Start session
session_start();
$_SESSION['username'] = 'admin';

echo "Testing edit user functionality...\n";
echo "POST action: " . $_POST['action'] . "\n";
echo "POST username: " . $_POST['username'] . "\n";
echo "POST new_username: " . $_POST['new_username'] . "\n";
echo "POST user_role: " . $_POST['user_role'] . "\n";
echo "POST permissions: " . implode(', ', $_POST['permissions']) . "\n";

// Test the user management logic directly
$usersFile = CONFIG_DIR . '/users.json';
$users = json_decode(file_get_contents($usersFile), true);

echo "Before edit: " . count($users) . " users\n";
foreach ($users as $user) {
    echo "- " . $user['username'] . " (role: " . $user['role'] . ")\n";
}

// Find the user to edit
$userIndex = array_search($_POST['username'], array_column($users, 'username'));

if ($userIndex === false) {
    echo "User not found\n";
} else {
    echo "User found at index: " . $userIndex . "\n";
    
    // Check if it's the last admin user
    $adminCount = count(array_filter($users, fn($u) => $u['role'] === 'administrator'));
    if ($users[$userIndex]['role'] === 'administrator' && $adminCount <= 1 && $_POST['user_role'] !== 'administrator') {
        echo "Cannot modify the last admin user\n";
    } else {
        // Update the user
        if (!empty($_POST['new_username'])) {
            $users[$userIndex]['username'] = $_POST['new_username'];
        }
        if (!empty($_POST['new_password'])) {
            $users[$userIndex]['password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        }
        $users[$userIndex]['role'] = $_POST['user_role'];
        
        // Handle permissions
        if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
            $users[$userIndex]['permissions'] = $_POST['permissions'];
        } else {
            $users[$userIndex]['permissions'] = [];
        }
        
        if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT))) {
            echo "User updated successfully\n";
        } else {
            echo "Failed to update user\n";
        }
    }
}

echo "After edit: " . count($users) . " users\n";
foreach ($users as $user) {
    echo "- " . $user['username'] . " (role: " . $user['role'] . ")\n";
}
?> 