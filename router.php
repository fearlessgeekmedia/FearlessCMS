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

// Router for PHP development server
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

$uri = preg_replace('#/\./#', '/', $uri);
$uri = preg_replace('#/{2,}#', '/', $uri);

// Load config to get admin path
$configFile = __DIR__ . '/config/config.json';
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$adminPath = $config['admin_path'] ?? 'admin';

// Special handling for admin routes
if (strpos($uri, '/' . $adminPath) === 0) {
    // Handle login route separately to avoid redirect loops
    if ($uri === '/' . $adminPath . '/login') {
        // Ensure session is started before including login.php
        require_once __DIR__ . '/includes/session.php';
        require_once __DIR__ . '/admin/login.php';
        return true;
    }
    
    // All other admin routes go to admin/index.php
    require_once __DIR__ . '/admin/index.php';
    return true;
}

// Special handling for uploads - route to uploads.php handler
if (strpos($uri, '/uploads/') === 0) {
    require_once __DIR__ . '/uploads.php';
    return true;
}

// If the file exists, serve it directly
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Otherwise, route everything to index.php
require_once __DIR__ . '/index.php';
?>
