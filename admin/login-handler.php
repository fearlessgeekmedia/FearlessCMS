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

// Set appropriate error reporting for production
if (getenv('FCMS_DEBUG') === 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
}

// Check if session extension is loaded
if (!extension_loaded('session') || !function_exists('session_start')) {
    error_log("Warning: Session functionality not available in login handler");
    http_response_code(500);
    echo "Session functionality not available";
    exit;
}

// Session should already be started by session.php
// No need to start it again

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Load configuration
$configFile = CONFIG_DIR . '/config.json';
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$adminPath = $config['admin_path'] ?? 'admin';

// Debug output
error_log("Login handler called");
// POST data debugging removed for security
// Session debugging removed for security

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    error_log("Login attempt for user: " . $username);

    // Validate CSRF token
    if (!validate_csrf_token()) {
        error_log("CSRF token validation failed for user: " . $username);
        header('Location: /' . $adminPath . '?action=login&error=invalid_token');
        exit;
    }

    if (empty($username) || empty($password)) {
        error_log("Empty username or password");
        header('Location: /' . $adminPath . '?action=login&error=empty_fields');
        exit;
    }

    if (login($username, $password)) {
        error_log("Login successful for user: " . $username);
        // Session debugging removed for security
        header('Location: /' . $adminPath . '?action=dashboard');
        exit;
    } else {
        error_log("Login failed for user: " . $username);
        header('Location: /' . $adminPath . '?action=login&error=invalid_credentials');
        exit;
    }
}

// If we get here, something went wrong
error_log("Invalid request to login handler");
header('Location: /' . $adminPath . '?action=login&error=invalid_request');
exit;
?>
