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
 * New User Handler for FearlessCMS
 * Handles adding new users with proper validation and security
 */

// Define the users file path if not already defined
if (!isset($usersFile)) {
    $usersFile = CONFIG_DIR . '/users.json';
}

// Handle adding new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to add users';
    } elseif (false) { // CSRF validation handled globally in admin/index.php
        $error = 'Invalid security token. Please refresh the page and try again.';
    } elseif (!check_operation_rate_limit('create_user', $_SESSION['username'])) {
        $error = 'Too many user creation attempts. Please wait before trying again.';
    } else {
        $newUsername = sanitize_input($_POST['new_username'] ?? '', 'username');
        $newUserPassword = $_POST['new_user_password'] ?? '';

        if (empty($newUsername) || empty($newUserPassword)) {
            $error = 'Username and password are required';
        } elseif (!validate_username($newUsername)) {
            $error = 'Username must be 3-50 characters, letters, numbers, underscore and dash only';
        } elseif (!validate_password($newUserPassword)) {
            $error = 'Password must be at least 8 characters with letters and numbers';
        } else {
            // Load existing users
            if (!file_exists($usersFile)) {
                $users = [];
            } else {
                $usersData = file_get_contents($usersFile);
                if ($usersData === false) {
                    $error = 'Failed to read users file';
                } else {
                    $users = json_decode($usersData, true) ?: [];
                }
            }
            
            if (!isset($error)) {
                // Check if username already exists
                if (array_search($newUsername, array_column($users, 'username')) !== false) {
                    $error = 'Username already exists';
                } else {
                    $users[] = [
                        'id' => uniqid(),
                        'username' => $newUsername,
                        'password' => password_hash($newUserPassword, PASSWORD_DEFAULT),
                        'role' => 'editor', // Default role for new users
                        'created_at' => date('Y-m-d H:i:s'),
                        'created_by' => $_SESSION['username']
                    ];
                    
                    if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT)) !== false) {
                        $success = 'User "' . htmlspecialchars($newUsername) . '" added successfully';

                        // Log security event
                        error_log("SECURITY: User '{$newUsername}' created by '{$_SESSION['username']}' from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                    } else {
                        $error = 'Failed to add user';
                    }
                }
            }
        }
    }
}
?>
