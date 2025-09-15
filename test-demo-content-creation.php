<?php
/**
 * Test demo content creation and access
 */

// Define constants
define('PROJECT_ROOT', __DIR__);
define('CONFIG_DIR', PROJECT_ROOT . '/config');
define('CONTENT_DIR', PROJECT_ROOT . '/content');

// Include necessary files
require_once 'includes/DemoModeManager.php';

echo "Testing Demo Content Creation and Access\n";
echo "========================================\n\n";

// Simulate a demo session
$_SESSION['demo_mode'] = true;
$_SESSION['demo_session_id'] = 'test_demo_session_123';
$_SESSION['demo_start_time'] = time();
$_SESSION['username'] = 'demo';

echo "1. Demo Session Setup:\n";
$demoManager = new DemoModeManager();
echo "   - Demo session: " . ($demoManager->isDemoSession() ? 'true' : 'false') . "\n";
echo "   - Demo user session: " . ($demoManager->isDemoUserSession() ? 'true' : 'false') . "\n";
echo "   - Demo content dir: " . $demoManager->getDemoContentDir() . "\n\n";

echo "2. Creating demo content:\n";
$testPath = 'test-page';
$testTitle = 'Test Page';
$testContent = '# Test Page\n\nThis is a test page created by a demo user.';

$result = $demoManager->createDemoContentFile($testPath, $testTitle, $testContent, [
    'template' => 'page-with-sidebar'
]);

echo "   - Content creation result: " . ($result ? 'success' : 'failed') . "\n";

if ($result) {
    $expectedFile = $demoManager->getDemoContentDir() . '/pages/' . $testPath . '.md';
    echo "   - Expected file: $expectedFile\n";
    echo "   - File exists: " . (file_exists($expectedFile) ? 'true' : 'false') . "\n";
    
    if (file_exists($expectedFile)) {
        echo "   - File content preview:\n";
        $content = file_get_contents($expectedFile);
        echo "     " . substr($content, 0, 100) . "...\n";
    }
}

echo "\n3. Testing content access routing:\n";
$path = $testPath;
$contentDir = $demoManager->getDemoContentDir();
$isDemoUser = $demoManager->isDemoSession() || $demoManager->isDemoUserSession();

echo "   - Path: $path\n";
echo "   - Is demo user: " . ($isDemoUser ? 'true' : 'false') . "\n";
echo "   - Content dir: $contentDir\n";

if ($isDemoUser) {
    // This mimics the routing logic from index.php
    if ($path === 'home' || $path === 'about' || $path === 'contact') {
        $contentFile = $contentDir . '/pages/' . $path . '.md';
    } elseif (strpos($path, 'blog/') === 0) {
        $blogPath = substr($path, 5);
        $contentFile = $contentDir . '/blog/' . $blogPath . '.md';
    } else {
        $contentFile = $contentDir . '/pages/' . $path . '.md';
    }
    
    echo "   - Content file: $contentFile\n";
    echo "   - File exists: " . (file_exists($contentFile) ? 'true' : 'false') . "\n";
    
    if (file_exists($contentFile)) {
        echo "   - File found successfully!\n";
        $content = file_get_contents($contentFile);
        echo "   - Content length: " . strlen($content) . " bytes\n";
    } else {
        echo "   - ERROR: File not found!\n";
    }
}

echo "\n4. Checking demo content directory structure:\n";
$demoContentDir = $demoManager->getDemoContentDir();
if (is_dir($demoContentDir)) {
    echo "   - Demo content dir exists: true\n";
    
    $pagesDir = $demoContentDir . '/pages';
    if (is_dir($pagesDir)) {
        echo "   - Pages dir exists: true\n";
        $pageFiles = glob($pagesDir . '/*.md');
        echo "   - Page files: " . count($pageFiles) . "\n";
        foreach ($pageFiles as $file) {
            echo "     * " . basename($file) . "\n";
        }
    } else {
        echo "   - Pages dir exists: false\n";
    }
    
    $blogDir = $demoContentDir . '/blog';
    if (is_dir($blogDir)) {
        echo "   - Blog dir exists: true\n";
        $blogFiles = glob($blogDir . '/*.md');
        echo "   - Blog files: " . count($blogFiles) . "\n";
        foreach ($blogFiles as $file) {
            echo "     * " . basename($file) . "\n";
        }
    } else {
        echo "   - Blog dir exists: false\n";
    }
} else {
    echo "   - Demo content dir exists: false\n";
}

echo "\nTest completed!\n";
?>