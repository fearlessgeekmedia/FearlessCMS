<?php
/**
 * Test with debug output
 */

// Define constants
define('PROJECT_ROOT', __DIR__);
define('CONFIG_DIR', PROJECT_ROOT . '/config');
define('CONTENT_DIR', PROJECT_ROOT . '/content');

// Include necessary files
require_once 'includes/DemoModeManager.php';

echo "Test with Debug Output\n";
echo "=====================\n\n";

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
$testPath = 'debug-test-page-2';
$testTitle = 'Debug Test Page 2';
$testContent = '# Debug Test Page 2\n\nThis tests the actual method call with debug.';

echo "   - Calling createDemoContentFile...\n";

// Override error_log to capture debug messages
$debugMessages = [];
function debug_error_log($message) {
    global $debugMessages;
    $debugMessages[] = $message;
    echo "   DEBUG: $message\n";
}

// Temporarily override error_log
$originalErrorLog = 'error_log';
if (function_exists('error_log')) {
    // We can't override error_log, so let's just call the method and see what happens
}

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

echo "\n4. Checking if file was created despite return value:\n";
$expectedFile = $demoManager->getDemoContentDir() . '/pages/' . $testPath . '.md';
echo "   - Expected file: $expectedFile\n";
echo "   - File exists: " . (file_exists($expectedFile) ? 'true' : 'false') . "\n";

if (file_exists($expectedFile)) {
    echo "   - SUCCESS: File was created!\n";
    $content = file_get_contents($expectedFile);
    echo "   - Content preview: " . substr($content, 0, 100) . "...\n";
} else {
    echo "   - File was not created\n";
}

echo "\nTest completed!\n";
?>