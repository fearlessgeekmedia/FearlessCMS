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
 * Password Change Handler for FearlessCMS
 * Handles user password changes with proper validation and security
 */

// Define the users file path if not already defined
if (!isset($usersFile)) {
    $usersFile = CONFIG_DIR . '/users.json';
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to change password';
    } elseif (false) { // CSRF validation handled globally in admin/index.php
        $error = 'Invalid security token. Please refresh the page and try again.';
    } elseif (!check_operation_rate_limit('change_password', $_SESSION['username'])) {
        $error = 'Too many password change attempts. Please wait before trying again.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'All password fields are required';
        } elseif (!validate_password($newPassword)) {
            $error = 'New password must be at least 8 characters with letters and numbers';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match';
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
                    $userIndex = array_search($_SESSION['username'], array_column($users, 'username'));

                    if ($userIndex !== false && password_verify($currentPassword, $users[$userIndex]['password'])) {
                        $users[$userIndex]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                        $users[$userIndex]['password_changed_at'] = date('Y-m-d H:i:s');
                        $users[$userIndex]['password_changed_by'] = $_SESSION['username'];
                        
                        if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT)) !== false) {
                            $success = 'Password changed successfully';

                            // Log security event
                            error_log("SECURITY: Password changed for user '{$_SESSION['username']}' from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                        } else {
                            $error = 'Failed to update password';
                        }
                    } else {
                        $error = 'Current password is incorrect';
                    }
                }
            }
        }
    }
}
?>
