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
 * Enable Demo Mode Script
 * Simple script to enable demo mode for FearlessCMS
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/DemoModeManager.php';

echo "FearlessCMS Demo Mode Setup\n";
echo "===========================\n\n";

$demoManager = new DemoModeManager();

if ($demoManager->isEnabled()) {
    echo "Demo mode is already enabled.\n";
    echo "Demo credentials: username=demo, password=demo\n";
    echo "Access your site at: http://localhost/admin/login\n\n";
} else {
    echo "Enabling demo mode...\n";
    $demoManager->enable();
    echo "Demo mode has been enabled successfully!\n\n";
    
    echo "Demo Information:\n";
    echo "- Username: demo\n";
    echo "- Password: demo\n";
    echo "- Session timeout: 1 hour\n";
    echo "- Access URL: http://localhost/admin/login\n\n";
    
    echo "To disable demo mode, use the admin panel or delete the config file:\n";
    echo "- Config file: " . CONFIG_DIR . "/demo_mode.json\n\n";
}

echo "Demo mode cleanup can be run with:\n";
echo "- Command line: php demo-cleanup.php\n";
echo "- Web: http://localhost/demo-cleanup.php?cleanup_key=demo_cleanup_2024\n\n";

echo "Enjoy exploring FearlessCMS!\n";
?>