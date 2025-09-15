<?php
/**
 * Test script for demo cleanup functionality
 */

// Include necessary files
require_once 'includes/DemoModeManager.php';

// Start session
session_start();

echo "Testing Demo Cleanup Functionality\n";
echo "==================================\n\n";

// Create DemoModeManager instance
$demoManager = new DemoModeManager();

echo "1. Demo Mode Status:\n";
$status = $demoManager->getStatus();
foreach ($status as $key => $value) {
    echo "   $key: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
}

echo "\n2. Testing cleanup functionality:\n";

// Simulate a demo session
$_SESSION['demo_mode'] = true;
$_SESSION['demo_session_id'] = 'test_demo_session_123';
$_SESSION['demo_start_time'] = time();

echo "   - Set demo session: " . ($demoManager->isDemoSession() ? 'true' : 'false') . "\n";

// Test cleanup
$cleanedFiles = $demoManager->cleanupDemoContent();
echo "   - Cleaned up $cleanedFiles demo files\n";

// Clean up session
unset($_SESSION['demo_mode']);
unset($_SESSION['demo_session_id']);
unset($_SESSION['demo_start_time']);

echo "\n3. Demo session cleaned up\n";
echo "   - Demo session active: " . ($demoManager->isDemoSession() ? 'true' : 'false') . "\n";

echo "\nTest completed successfully!\n";
?>