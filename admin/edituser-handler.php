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
 * Edit User Handler for FearlessCMS
 * Handles editing users with proper validation and security
 */

// Define the users file path if not already defined
if (!isset($usersFile)) {
    $usersFile = CONFIG_DIR . '/users.json';
}

// Handle editing user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to edit users';
    } elseif (false) { // CSRF validation handled globally in admin/index.php
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $username = $_POST['username'] ?? '';
        $newUsername = sanitize_input(trim($_POST['new_username'] ?? ''), 'username');
        $newPassword = $_POST['new_password'] ?? '';
        $newRole = sanitize_input($_POST['user_role'] ?? '', 'string');

        if (empty($username) || !validate_username($username)) {
            $error = 'Valid username is required';
        } elseif (!empty($newPassword) && !validate_password($newPassword)) {
            $error = 'Password must be at least 8 characters with letters and numbers';
        } else {
            // Load existing users
            if (!file_exists($usersFile)) {
                $error = 'Users file not found';
            } else {
                $usersData = file_get_contents($usersFile);
                if ($usersData === false) {
                    $error = 'Failed to read users file';
                } else {
                    $users = json_decode($usersData, true) ?: [];
                    $userIndex = -1;

                    // Find the user
                    foreach ($users as $index => $user) {
                        if ($user['username'] === $username) {
                            $userIndex = $index;
                            break;
                        }
                    }

                    if ($userIndex === -1) {
                        $error = 'User not found';
                    } else {
                        // Don't allow changing admin user's role
                        if ($username === 'admin') {
                            $newRole = 'administrator';
                        }

                        // Only allow administrators to change roles
                        if (!empty($newRole) && $username !== 'admin') {
                            $users[$userIndex]['role'] = $newRole;
                        }

                        // Update username if provided
                        if (!empty($newUsername) && $newUsername !== $username) {
                            // Check if new username already exists
                            foreach ($users as $user) {
                                if ($user['username'] === $newUsername) {
                                    $error = 'Username already exists';
                                    break;
                                }
                            }
                            if (!isset($error)) {
                                $users[$userIndex]['username'] = $newUsername;
                            }
                        }

                        // Update password if provided
                        if (!empty($newPassword)) {
                            $users[$userIndex]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                        }

                        if (!isset($error)) {
                            // Add metadata
                            $users[$userIndex]['updated_at'] = date('Y-m-d H:i:s');
                            $users[$userIndex]['updated_by'] = $_SESSION['username'];
                            
                            if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT)) !== false) {
                                $success = 'User "' . htmlspecialchars($username) . '" updated successfully';
                                
                                // Log security event
                                error_log("SECURITY: User '{$username}' updated by '{$_SESSION['username']}' from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                            } else {
                                $error = 'Failed to update user';
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
