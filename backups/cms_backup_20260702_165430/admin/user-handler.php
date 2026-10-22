<?php
/**
 * User Management Handler for FearlessCMS
 * Handles adding, editing, and deleting users
 */

// Define the users file path if not already defined
if (!isset($usersFile)) {
    $usersFile = CONFIG_DIR . '/users.json';
}

// Define the saveUsers function if not already defined
if (!function_exists('saveUsers')) {
    function saveUsers($users) {
        global $usersFile;
        if (empty($usersFile)) {
            $usersFile = CONFIG_DIR . '/users.json';
        }
        return file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT)) !== false;
    }
}

// Handle user management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to perform this action';
    } else {
        switch ($_POST['action']) {
            case 'add_user':
                if (!fcms_check_permission($_SESSION['username'], 'manage_users')) {
                    $error = 'You do not have permission to manage users';
                    break;
                }
                if (empty($_POST['username']) || empty($_POST['password'])) {
                    $error = 'Username and password are required';
                    break;
                }
                
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? 'author';
                
                // Validate username format
                if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
                    $error = 'Username must be 3-20 characters long and contain only letters, numbers, and underscores';
                    break;
                }
                
                // Validate password strength
                if (strlen($password) < 8) {
                    $error = 'Password must be at least 8 characters long';
                    break;
                }
                
                // Load existing users
                if (!file_exists($usersFile)) {
                    $users = [];
                } else {
                    $usersData = file_get_contents($usersFile);
                    if ($usersData === false) {
                        $error = 'Failed to read users file';
                        break;
                    }
                    $users = json_decode($usersData, true) ?: [];
                }
                
                // Check if username already exists
                if (array_search($username, array_column($users, 'username')) !== false) {
                    $error = 'Username already exists';
                    break;
                }
                
                // Add new user
                $users[] = [
                    'id' => uniqid(),
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $role,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => $_SESSION['username']
                ];
                
                if (saveUsers($users)) {
                    $success = 'User "' . htmlspecialchars($username) . '" added successfully';
                    
                    // Log security event
                    error_log("SECURITY: User '{$username}' created by '{$_SESSION['username']}' from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                } else {
                    $error = 'Failed to add user';
                }
                break;
                
            case 'edit_user':
                if (!fcms_check_permission($_SESSION['username'], 'manage_users')) {
                    $error = 'You do not have permission to manage users';
                    break;
                }
                if (empty($_POST['username'])) {
                    $error = 'Username is required';
                    break;
                }
                
                $username = trim($_POST['username'] ?? '');
                $newUsername = trim($_POST['new_username'] ?? '');
                $newPassword = $_POST['new_password'] ?? '';
                $newRole = $_POST['user_role'] ?? 'author';
                
                // Load existing users
                if (!file_exists($usersFile)) {
                    $error = 'Users file not found';
                    break;
                }
                
                $usersData = file_get_contents($usersFile);
                if ($usersData === false) {
                    $error = 'Failed to read users file';
                    break;
                }
                $users = json_decode($usersData, true) ?: [];
                
                $userIndex = array_search($username, array_column($users, 'username'));
                
                if ($userIndex === false) {
                    $error = 'User not found';
                    break;
                }
                
                // Don't allow editing the last admin user
                $adminCount = count(array_filter($users, fn($u) => ($u['role'] ?? '') === 'administrator'));
                if (($users[$userIndex]['role'] ?? '') === 'administrator' && $adminCount <= 1 && $newRole !== 'administrator') {
                    $error = 'Cannot modify the last admin user';
                    break;
                }
                
                // Validate new username if provided
                if (!empty($newUsername)) {
                    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $newUsername)) {
                        $error = 'New username must be 3-20 characters long and contain only letters, numbers, and underscores';
                        break;
                    }
                    
                    // Check if new username already exists (excluding current user)
                    $existingUserIndex = array_search($newUsername, array_column($users, 'username'));
                    if ($existingUserIndex !== false && $existingUserIndex !== $userIndex) {
                        $error = 'New username already exists';
                        break;
                    }
                }
                
                // Validate new password if provided
                if (!empty($newPassword) && strlen($newPassword) < 8) {
                    $error = 'New password must be at least 8 characters long';
                    break;
                }
                
                // Update user
                if (!empty($newUsername)) {
                    $users[$userIndex]['username'] = $newUsername;
                }
                if (!empty($newPassword)) {
                    $users[$userIndex]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                }
                $users[$userIndex]['role'] = $newRole;
                $users[$userIndex]['updated_at'] = date('Y-m-d H:i:s');
                $users[$userIndex]['updated_by'] = $_SESSION['username'];
                
                if (saveUsers($users)) {
                    $success = 'User "' . htmlspecialchars($username) . '" updated successfully';
                    
                    // Log security event
                    error_log("SECURITY: User '{$username}' updated by '{$_SESSION['username']}' from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                } else {
                    $error = 'Failed to update user';
                }
                break;
                
            case 'delete_user':
                if (!fcms_check_permission($_SESSION['username'], 'manage_users')) {
                    $error = 'You do not have permission to manage users';
                    break;
                }
                if (empty($_POST['username'])) {
                    $error = 'Username is required';
                    break;
                }
                
                $username = trim($_POST['username'] ?? '');
                
                // Prevent self-deletion
                if ($username === $_SESSION['username']) {
                    $error = 'Cannot delete your own account';
                    break;
                }
                
                // Load existing users
                if (!file_exists($usersFile)) {
                    $error = 'Users file not found';
                    break;
                }
                
                $usersData = file_get_contents($usersFile);
                if ($usersData === false) {
                    $error = 'Failed to read users file';
                    break;
                }
                $users = json_decode($usersData, true) ?: [];
                
                $userIndex = array_search($username, array_column($users, 'username'));
                
                if ($userIndex === false) {
                    $error = 'User not found';
                    break;
                }
                
                // Don't allow deleting the last admin user
                $adminCount = count(array_filter($users, fn($u) => ($u['role'] ?? '') === 'administrator'));
                if (($users[$userIndex]['role'] ?? '') === 'administrator' && $adminCount <= 1) {
                    $error = 'Cannot delete the last admin user';
                    break;
                }
                
                // Store user info for logging
                $deletedUserRole = $users[$userIndex]['role'] ?? 'unknown';
                
                // Delete user
                array_splice($users, $userIndex, 1);
                
                if (saveUsers($users)) {
                    $success = 'User "' . htmlspecialchars($username) . '" deleted successfully';
                    
                    // Log security event
                    error_log("SECURITY: User '{$username}' (role: {$deletedUserRole}) deleted by '{$_SESSION['username']}' from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                } else {
                    $error = 'Failed to delete user';
                }
                break;
                
            default:
                $error = 'Invalid action specified';
                break;
        }
    }
}
?> 