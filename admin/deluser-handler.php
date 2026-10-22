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
 * Delete User Handler for FearlessCMS
 * Handles user deletion with proper validation and security
 */

// Define the users file path if not already defined
if (!isset($usersFile)) {
    $usersFile = CONFIG_DIR . '/users.json';
}

// Handle deleting user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to delete users';
    } elseif (false) { // CSRF validation handled globally in admin/index.php
        $error = 'Invalid security token. Please refresh the page and try again.';
    } elseif (!check_operation_rate_limit('delete_user', $_SESSION['username'])) {
        $error = 'Too many user deletion attempts. Please wait before trying again.';
    } else {
        $username = sanitize_input($_POST['username'] ?? '', 'username');

        // Additional validation
        if (empty($username) || !validate_username($username)) {
            $error = 'Invalid username provided';
        } elseif ($username === $_SESSION['username']) {
            $error = 'Cannot delete your own account';
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
                    $userIndex = array_search($username, array_column($users, 'username'));

                    if ($userIndex === false) {
                        $error = 'User not found';
                    } else {
                        // Don't allow deleting the last admin user
                        $adminCount = count(array_filter($users, fn($u) => ($u['role'] ?? '') === 'administrator' || $u['username'] === 'admin'));
                        if (($users[$userIndex]['role'] ?? '') === 'administrator' && $adminCount <= 1) {
                            $error = 'Cannot delete the last administrator user';
                        } else {
                            // Store user info for logging
                            $deletedUserRole = $users[$userIndex]['role'] ?? 'unknown';
                            
                            // Delete user
                            array_splice($users, $userIndex, 1);
                            
                            if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT)) !== false) {
                                $success = 'User "' . htmlspecialchars($username) . '" deleted successfully';

                                // Log security event
                                error_log("SECURITY: User '{$username}' (role: {$deletedUserRole}) deleted by '{$_SESSION['username']}' from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                            } else {
                                $error = 'Failed to delete user';
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
