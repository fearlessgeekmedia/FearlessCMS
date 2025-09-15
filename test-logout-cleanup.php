<?php
/**
 * Test logout cleanup
 */

// Define constants
define('PROJECT_ROOT', __DIR__);
define('CONFIG_DIR', PROJECT_ROOT . '/config');
define('CONTENT_DIR', PROJECT_ROOT . '/content');

// Include necessary files
require_once 'includes/DemoModeManager.php';

echo "Test Logout Cleanup\n";
echo "==================\n\n";

// Simulate a demo session
$_SESSION['demo_mode'] = true;
$_SESSION['demo_session_id'] = 'demo_68c85f88be5944.95134505';
$_SESSION['demo_start_time'] = time();
$_SESSION['username'] = 'demo';

echo "1. Before Cleanup - Demo Content Files:\n";
$demoManager = new DemoModeManager();
$demoContentDir = $demoManager->getDemoContentDir();
$pagesDir = $demoContentDir . '/pages';

if (is_dir($pagesDir)) {
    $files = glob($pagesDir . '/*.md');
    echo "   - Files in pages directory: " . count($files) . "\n";
    foreach ($files as $file) {
        echo "     * " . basename($file) . "\n";
    }
} else {
    echo "   - Pages directory doesn't exist\n";
}

echo "\n2. Testing cleanupDemoContent():\n";
$cleanedFiles = $demoManager->cleanupDemoContent();
echo "   - Cleaned files: $cleanedFiles\n";

echo "\n3. After Cleanup - Demo Content Files:\n";
if (is_dir($pagesDir)) {
    $files = glob($pagesDir . '/*.md');
    echo "   - Files in pages directory: " . count($files) . "\n";
    foreach ($files as $file) {
        echo "     * " . basename($file) . "\n";
    }
} else {
    echo "   - Pages directory doesn't exist\n";
}

echo "\n4. Testing endDemoSession():\n";
echo "   - Demo session before: " . ($demoManager->isDemoSession() ? 'true' : 'false') . "\n";
$demoManager->endDemoSession();
echo "   - Demo session after: " . ($demoManager->isDemoSession() ? 'true' : 'false') . "\n";

echo "\n5. Final Demo Content Files:\n";
if (is_dir($pagesDir)) {
    $files = glob($pagesDir . '/*.md');
    echo "   - Files in pages directory: " . count($files) . "\n";
    foreach ($files as $file) {
        echo "     * " . basename($file) . "\n";
    }
} else {
    echo "   - Pages directory doesn't exist\n";
}

echo "\nTest completed!\n";
?>