<?php
/**
 * Test demo page access
 */

// Define constants
define('PROJECT_ROOT', __DIR__);
define('CONFIG_DIR', PROJECT_ROOT . '/config');
define('CONTENT_DIR', PROJECT_ROOT . '/content');

// Include necessary files
require_once 'includes/DemoModeManager.php';

echo "Testing Demo Page Access\n";
echo "========================\n\n";

// Simulate a demo session
$_SESSION['demo_mode'] = true;
$_SESSION['demo_session_id'] = 'test_demo_session_123';
$_SESSION['demo_start_time'] = time();
$_SESSION['username'] = 'demo';

echo "1. Session Setup:\n";
echo "   - demo_mode: " . (isset($_SESSION['demo_mode']) ? ($_SESSION['demo_mode'] ? 'true' : 'false') : 'not set') . "\n";
echo "   - username: " . ($_SESSION['username'] ?? 'not set') . "\n";
echo "   - demo_session_id: " . ($_SESSION['demo_session_id'] ?? 'not set') . "\n\n";

echo "2. Demo Manager Status:\n";
$demoManager = new DemoModeManager();
echo "   - isDemoSession(): " . ($demoManager->isDemoSession() ? 'true' : 'false') . "\n";
echo "   - isDemoUserSession(): " . ($demoManager->isDemoUserSession() ? 'true' : 'false') . "\n";
echo "   - getDemoContentDir(): " . $demoManager->getDemoContentDir() . "\n\n";

echo "3. Testing Content Routing Logic:\n";
$path = 'about'; // Test accessing the about page
echo "   - Path: $path\n";

// This mimics the logic from index.php
$contentDir = CONTENT_DIR;
$isDemoUser = $demoManager->isDemoSession() || $demoManager->isDemoUserSession();

echo "   - isDemoUser: " . ($isDemoUser ? 'true' : 'false') . "\n";
echo "   - contentDir (before): $contentDir\n";

if ($isDemoUser) {
    $contentDir = $demoManager->getDemoContentDir();
    echo "   - contentDir (after): $contentDir\n";
    
    // Check demo pages first, then demo blog
    if ($path === 'home' || $path === 'about' || $path === 'contact') {
        $contentFile = $contentDir . '/pages/' . $path . '.md';
    } elseif (strpos($path, 'blog/') === 0) {
        $blogPath = substr($path, 5); // Remove 'blog/' prefix
        $contentFile = $contentDir . '/blog/' . $blogPath . '.md';
    } else {
        $contentFile = $contentDir . '/pages/' . $path . '.md';
    }
    
    echo "   - contentFile: $contentFile\n";
    echo "   - file exists: " . (file_exists($contentFile) ? 'true' : 'false') . "\n";
    
    if (file_exists($contentFile)) {
        echo "   - SUCCESS: File found!\n";
        $content = file_get_contents($contentFile);
        echo "   - Content length: " . strlen($content) . " bytes\n";
        echo "   - Content preview: " . substr($content, 0, 100) . "...\n";
    } else {
        echo "   - ERROR: File not found!\n";
        
        // Check what files actually exist
        $pagesDir = $contentDir . '/pages';
        if (is_dir($pagesDir)) {
            echo "   - Pages directory exists: true\n";
            $files = glob($pagesDir . '/*.md');
            echo "   - Files in pages directory: " . count($files) . "\n";
            foreach ($files as $file) {
                echo "     * " . basename($file) . "\n";
            }
        } else {
            echo "   - Pages directory exists: false\n";
        }
    }
} else {
    echo "   - ERROR: Demo user detection failed!\n";
    echo "   - This means the content will be looked for in the main content directory\n";
}

echo "\n4. Testing with a custom page:\n";
$customPath = 'test-page';
echo "   - Custom path: $customPath\n";

if ($isDemoUser) {
    $customContentFile = $contentDir . '/pages/' . $customPath . '.md';
    echo "   - Custom content file: $customContentFile\n";
    echo "   - Custom file exists: " . (file_exists($customContentFile) ? 'true' : 'false') . "\n";
    
    if (!file_exists($customContentFile)) {
        echo "   - This would cause 'Content file not found' error\n";
    }
}

echo "\nTest completed!\n";
?>