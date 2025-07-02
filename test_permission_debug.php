<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Start session
session_start();

echo "=== Permission Debug Test ===\n";

// Check current session
echo "Current session username: " . ($_SESSION['username'] ?? 'none') . "\n";
echo "Current session permissions: " . print_r($_SESSION['permissions'] ?? [], true) . "\n";

// Test permission check for current user
if (!empty($_SESSION['username'])) {
    $hasPermission = fcms_check_permission($_SESSION['username'], 'manage_users');
    echo "User '" . $_SESSION['username'] . "' has 'manage_users' permission: " . ($hasPermission ? 'YES' : 'NO') . "\n";
    
    // Check user data
    $usersFile = CONFIG_DIR . '/users.json';
    $users = json_decode(file_get_contents($usersFile), true);
    
    foreach ($users as $user) {
        if ($user['username'] === $_SESSION['username']) {
            echo "Current user data: " . print_r($user, true) . "\n";
            
            // Check role permissions
            if (isset($user['role'])) {
                $rolesFile = CONFIG_DIR . '/roles.json';
                $roles = json_decode(file_get_contents($rolesFile), true);
                if (isset($roles[$user['role']])) {
                    echo "Role '" . $user['role'] . "' permissions: " . print_r($roles[$user['role']]['permissions'], true) . "\n";
                    echo "Has manage_users in role permissions: " . (in_array('manage_users', $roles[$user['role']]['permissions']) ? 'YES' : 'NO') . "\n";
                }
            }
            break;
        }
    }
} else {
    echo "No user logged in\n";
}

echo "=== End Debug ===\n"; 