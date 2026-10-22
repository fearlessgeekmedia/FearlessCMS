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

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/plugins/user-roles/user-roles.php';

// Check if user is logged in
// Session should already be started by session.php
if (!isLoggedIn()) {
    header('Location: login');
    exit;
}

// Get current user
$currentUser = getCurrentUser();

// Handle role management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_roles':
            if (user_has_capability($currentUser['username'], 'manage_roles')) {
                $roles = json_decode($_POST['roles'] ?? '{}', true);
                if (is_array($roles)) {
                    file_put_contents(USER_ROLES_CONFIG_FILE, json_encode($roles, JSON_PRETTY_PRINT));
                    $_SESSION['success'] = 'Roles updated successfully';
                } else {
                    $_SESSION['error'] = 'Invalid roles data';
                }
            } else {
                $_SESSION['error'] = 'You do not have permission to manage roles';
            }
            break;
    }
    
    // Redirect back to role management page
    header('Location: ?action=manage_roles');
    exit;
} 