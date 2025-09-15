<?php
/**
 * Test the demo fix
 */

// Define constants
define('PROJECT_ROOT', __DIR__);
define('CONFIG_DIR', PROJECT_ROOT . '/config');
define('CONTENT_DIR', PROJECT_ROOT . '/content');

// Include necessary files
require_once 'includes/DemoModeManager.php';

echo "Testing Demo Fix\n";
echo "================\n\n";

// Simulate a demo session
$_SESSION['demo_mode'] = true;
$_SESSION['demo_session_id'] = 'test_demo_session_123';
$_SESSION['demo_start_time'] = time();
$_SESSION['username'] = 'demo';

echo "1. Session Setup:\n";
echo "   - demo_mode: " . (isset($_SESSION['demo_mode']) ? ($_SESSION['demo_mode'] ? 'true' : 'false') : 'not set') . "\n";
echo "   - username: " . ($_SESSION['username'] ?? 'not set') . "\n\n";

echo "2. Testing Demo Manager Methods:\n";
$demoManager = new DemoModeManager();
echo "   - isDemoSession(): " . ($demoManager->isDemoSession() ? 'true' : 'false') . "\n";
echo "   - isDemoUserSession(): " . ($demoManager->isDemoUserSession() ? 'true' : 'false') . "\n\n";

echo "3. Testing Fallback Logic:\n";
$isDemoUser = $demoManager->isDemoSession() || $demoManager->isDemoUserSession();
echo "   - Initial isDemoUser: " . ($isDemoUser ? 'true' : 'false') . "\n";

// FALLBACK: Check username directly if session detection fails
if (!$isDemoUser && isset($_SESSION['username']) && $_SESSION['username'] === 'demo') {
    echo "   - Fallback triggered: username is 'demo'\n";
    $isDemoUser = true;
}

echo "   - Final isDemoUser: " . ($isDemoUser ? 'true' : 'false') . "\n\n";

echo "4. Testing createDemoContentFile:\n";
$testPath = 'test-fix-page';
$testTitle = 'Test Fix Page';
$testContent = '# Test Fix Page\n\nThis page tests the demo fix.';

$result = $demoManager->createDemoContentFile($testPath, $testTitle, $testContent, [
    'template' => 'page-with-sidebar'
]);

echo "   - Content creation result: " . ($result ? 'success' : 'failed') . "\n";

if ($result) {
    $expectedFile = $demoManager->getDemoContentDir() . '/pages/' . $testPath . '.md';
    echo "   - Expected file: $expectedFile\n";
    echo "   - File exists: " . (file_exists($expectedFile) ? 'true' : 'false') . "\n";
    
    if (file_exists($expectedFile)) {
        echo "   - SUCCESS: Demo content created!\n";
        $content = file_get_contents($expectedFile);
        echo "   - Content preview: " . substr($content, 0, 100) . "...\n";
    }
} else {
    echo "   - FAILED: Demo content creation failed\n";
}

echo "\n5. Testing Content Access:\n";
$path = $testPath;
$contentDir = $isDemoUser ? $demoManager->getDemoContentDir() : CONTENT_DIR;

if ($isDemoUser) {
    $contentFile = $contentDir . '/pages/' . $path . '.md';
    echo "   - Content file: $contentFile\n";
    echo "   - File exists: " . (file_exists($contentFile) ? 'true' : 'false') . "\n";
    
    if (file_exists($contentFile)) {
        echo "   - SUCCESS: Content accessible!\n";
    } else {
        echo "   - ERROR: Content not accessible - would cause 'Content file not found'\n";
    }
}

echo "\nTest completed!\n";
?>