<?php
/**
 * Debug createDemoContentFile method
 */

// Define constants
define('PROJECT_ROOT', __DIR__);
define('CONFIG_DIR', PROJECT_ROOT . '/config');
define('CONTENT_DIR', PROJECT_ROOT . '/content');

// Include necessary files
require_once 'includes/DemoModeManager.php';

echo "Debug createDemoContentFile Method\n";
echo "==================================\n\n";

// Simulate a demo session
$_SESSION['demo_mode'] = true;
$_SESSION['demo_session_id'] = 'test_demo_session_123';
$_SESSION['demo_start_time'] = time();
$_SESSION['username'] = 'demo';

echo "1. Session Variables:\n";
echo "   - \$_SESSION is set: " . (isset($_SESSION) ? 'true' : 'false') . "\n";
echo "   - demo_mode: " . (isset($_SESSION['demo_mode']) ? ($_SESSION['demo_mode'] ? 'true' : 'false') : 'not set') . "\n";
echo "   - username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'not set') . "\n\n";

echo "2. Testing Demo Manager Methods:\n";
$demoManager = new DemoModeManager();
echo "   - isDemoSession(): " . ($demoManager->isDemoSession() ? 'true' : 'false') . "\n";
echo "   - isDemoUserSession(): " . ($demoManager->isDemoUserSession() ? 'true' : 'false') . "\n\n";

echo "3. Testing createDemoContentFile Method Call:\n";
$testPath = 'debug-test-page';
$testTitle = 'Debug Test Page';
$testContent = '# Debug Test Page\n\nThis tests the actual method call.';

echo "   - Calling createDemoContentFile...\n";
$result = $demoManager->createDemoContentFile($testPath, $testTitle, $testContent, [
    'template' => 'page-with-sidebar'
]);

echo "   - Method returned: " . ($result ? 'true' : 'false') . "\n";

if ($result) {
    echo "   - SUCCESS: Method succeeded!\n";
    $expectedFile = $demoManager->getDemoContentDir() . '/pages/' . $testPath . '.md';
    echo "   - Expected file: $expectedFile\n";
    echo "   - File exists: " . (file_exists($expectedFile) ? 'true' : 'false') . "\n";
} else {
    echo "   - FAILED: Method returned false\n";
    echo "   - This means the session detection failed\n";
}

echo "\n4. Manual Session Detection Test:\n";
$isDemoSession = $demoManager->isDemoSession() || $demoManager->isDemoUserSession();
echo "   - Initial isDemoSession: " . ($isDemoSession ? 'true' : 'false') . "\n";

// FALLBACK: Check username directly if session detection fails
if (!$isDemoSession && isset($_SESSION['username']) && $_SESSION['username'] === 'demo') {
    echo "   - Fallback triggered: username is 'demo'\n";
    $isDemoSession = true;
}

echo "   - Final isDemoSession: " . ($isDemoSession ? 'true' : 'false') . "\n";

echo "\nTest completed!\n";
?>