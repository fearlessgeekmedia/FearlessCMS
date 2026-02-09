<?php
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