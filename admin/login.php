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

// Session is already started by index.php, so we don't need to start it again
// Just ensure we have access to the required functions

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Apply security headers
set_security_headers();

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

// Load configuration
$configFile = CONFIG_DIR . '/config.json';
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$adminPath = $config['admin_path'] ?? 'admin';

// Generate CSRF token for the form
generate_csrf_token();

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    // Use JavaScript redirect instead of PHP headers to avoid "headers already sent" error
    echo '<script>window.location.href = "/' . $adminPath . '?action=dashboard";</script>';
    exit;
}

// Process login attempt
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Log login attempt for security monitoring
    error_log("Login attempt for user: " . $username . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    error_log("DEBUG: SESSION CSRF: " . ($_SESSION['csrf_token'] ?? 'NOT SET'));
    error_log("DEBUG: POST CSRF: " . ($_POST['csrf_token'] ?? 'NOT SET'));

    // Validate CSRF token
    if (!validate_csrf_token()) {
        error_log("CSRF token validation failed for user: " . $username);
        error_log("DEBUG: Session ID: " . session_id());
        error_log("DEBUG: Session contents: " . json_encode($_SESSION));
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } else {
        // Check rate limiting before attempting login
        if (!check_login_rate_limit($username)) {
            error_log("Rate limit exceeded for user: " . $username);
            $error = 'Too many login attempts. Please wait 15 minutes before trying again.';
        } elseif (login($username, $password)) {
            error_log("Successful login for user: " . $username . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            // Use JavaScript redirect instead of PHP headers to avoid "headers already sent" error
            echo '<script>window.location.href = "/' . $adminPath . '?action=dashboard";</script>';
            exit;
        } else {
            error_log("Failed login for user: " . $username . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            $error = 'Invalid username or password';
        }
    }
}

// Show login page directly
include ADMIN_TEMPLATE_DIR . '/login.php';
?>
