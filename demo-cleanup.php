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
 * Demo Mode Cleanup Script
 * This script can be run via cron to clean up expired demo sessions
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/DemoModeManager.php';

// Only allow this script to be run from command line or with proper authorization
if (php_sapi_name() !== 'cli' && !isset($_GET['cleanup_key'])) {
    http_response_code(403);
    die('Access denied. This script can only be run from command line or with proper authorization.');
}

// Check cleanup key if running via web
if (php_sapi_name() !== 'cli' && $_GET['cleanup_key'] !== 'demo_cleanup_2024') {
    http_response_code(403);
    die('Invalid cleanup key.');
}

echo "Starting demo mode cleanup...\n";

$demoManager = new DemoModeManager();

// Check if demo mode is enabled
if (!$demoManager->isEnabled()) {
    echo "Demo mode is not enabled. No cleanup needed.\n";
    exit(0);
}

// Clean up expired demo sessions
$sessionsDir = session_save_path() ?: sys_get_temp_dir();
$sessionFiles = glob($sessionsDir . '/sess_*');

$cleanedSessions = 0;
$currentTime = time();

foreach ($sessionFiles as $sessionFile) {
    $sessionData = file_get_contents($sessionFile);
    
    // Check if this is a demo session
    if (strpos($sessionData, 'demo_mode|b:1') !== false) {
        // Extract demo start time
        if (preg_match('/demo_start_time\|i:(\d+)/', $sessionData, $matches)) {
            $startTime = (int)$matches[1];
            $sessionAge = $currentTime - $startTime;
            
            // If session is older than timeout, clean it up
            if ($sessionAge > ($demoManager->getStatus()['timeout'] ?? 3600)) {
                unlink($sessionFile);
                $cleanedSessions++;
                echo "Cleaned up expired demo session: " . basename($sessionFile) . "\n";
            }
        }
    }
}

echo "Demo cleanup completed. Cleaned up $cleanedSessions expired sessions.\n";

// Clean up demo content directories if they're empty
$demoContentDir = $demoManager->getDemoContentDir();
$demoConfigDir = $demoManager->getDemoConfigDir();

if (is_dir($demoContentDir) && count(scandir($demoContentDir)) <= 2) {
    rmdir($demoContentDir);
    echo "Removed empty demo content directory.\n";
}

if (is_dir($demoConfigDir) && count(scandir($demoConfigDir)) <= 2) {
    rmdir($demoConfigDir);
    echo "Removed empty demo config directory.\n";
}

echo "Demo mode cleanup finished.\n";
?>