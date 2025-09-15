<?php
/**
 * Manual cleanup test
 */

// Define constants
define('PROJECT_ROOT', __DIR__);
define('CONFIG_DIR', PROJECT_ROOT . '/config');
define('CONTENT_DIR', PROJECT_ROOT . '/content');

// Include necessary files
require_once 'includes/DemoModeManager.php';

echo "Manual Cleanup Test\n";
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

echo "\n2. Manual Cleanup Execution:\n";
$sessionId = $_SESSION['demo_session_id'] ?? 'all';
echo "   - Session ID: $sessionId\n";

$cleanedFiles = 0;
$demoContentDir = $demoManager->getDemoContentDir();

if (is_dir($demoContentDir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($demoContentDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'md') {
            $content = file_get_contents($file->getPathname());
            
            // Check if this file belongs to current demo session or is demo content
            $shouldDelete = false;
            
            if ($sessionId === 'all') {
                // Clean up all demo content files
                if (preg_match('/demo_content["\s]*:\s*true/', $content)) {
                    $shouldDelete = true;
                }
            } else {
                // Clean up files from specific session
                if (preg_match('/demo_session_id["\s]*:\s*["\']?' . preg_quote($sessionId, '/') . '["\']?/', $content)) {
                    $shouldDelete = true;
                }
            }
            
            if ($shouldDelete) {
                echo "   - Deleting: " . $file->getFilename() . "\n";
                $unlinkResult = unlink($file->getPathname());
                echo "     Result: " . ($unlinkResult ? 'success' : 'failed') . "\n";
                if ($unlinkResult) {
                    $cleanedFiles++;
                }
            }
        }
    }
}

echo "   - Total files cleaned: $cleanedFiles\n";

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

echo "\nTest completed!\n";
?>