<?php

/**
 * FearlessCMS
 *
 * Copyright (C) 2026 FearlessCMS
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * As a special exception to the GNU General Public License version 2
 * ("GPL"), the copyright holder(s) of FearlessCMS give you permission
 * to link, load, or combine this software with independent modules
 * ("Themes" and "Plugins") that are not derived from or based on this
 * software, regardless of the license terms of those Themes or
 * Plugins, including proprietary or commercial licenses, and to
 * convey the resulting combination under terms of your choice,
 * provided that you also convey the corresponding source code of the
 * FearlessCMS portions under the terms of the GPL.
 *
 * This exception does not affect any of your obligations under the
 * GPL with respect to the FearlessCMS core software itself, and it
 * does not extend to modifications of FearlessCMS core files — only
 * to independent Theme and Plugin modules that interact with
 * FearlessCMS through its documented Theme/Plugin API.
 */

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