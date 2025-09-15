<?php
/**
 * Simple test to debug the issue
 */

// Define constants
define('PROJECT_ROOT', __DIR__);
define('CONFIG_DIR', PROJECT_ROOT . '/config');
define('CONTENT_DIR', PROJECT_ROOT . '/content');

// Include necessary files
require_once 'includes/DemoModeManager.php';

echo "Simple Test\n";
echo "===========\n\n";

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

echo "3. Testing Manual Logic:\n";
$isDemoSession = $demoManager->isDemoSession() || $demoManager->isDemoUserSession();
echo "   - Initial isDemoSession: " . ($isDemoSession ? 'true' : 'false') . "\n";

// FALLBACK: Check username directly if session detection fails
if (!$isDemoSession && isset($_SESSION['username']) && $_SESSION['username'] === 'demo') {
    echo "   - Fallback triggered: username is 'demo'\n";
    $isDemoSession = true;
}

echo "   - Final isDemoSession: " . ($isDemoSession ? 'true' : 'false') . "\n";

if (!$isDemoSession) {
    echo "   - ERROR: Session detection failed\n";
} else {
    echo "   - SUCCESS: Session detection passed\n";
    
    // Test creating the file manually
    $testPath = 'simple-test-page';
    $testTitle = 'Simple Test Page';
    $testContent = '# Simple Test Page\n\nThis tests manual file creation.';
    
    $sessionId = $_SESSION['demo_session_id'] ?? uniqid('demo_', true);
    $filePath = $demoManager->getDemoContentDir() . '/pages/' . $testPath . '.md';
    
    echo "\n4. Manual File Creation:\n";
    echo "   - Session ID: $sessionId\n";
    echo "   - File path: $filePath\n";
    
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $defaultMetadata = [
        'title' => $testTitle,
        'demo_content' => true,
        'demo_session_id' => $sessionId,
        'created_at' => date('Y-m-d H:i:s'),
        'template' => 'page-with-sidebar'
    ];
    
    $frontmatter = '<!-- json ' . json_encode($defaultMetadata, JSON_PRETTY_PRINT) . ' -->';
    $fileContent = $frontmatter . "\n\n" . $testContent;
    
    $writeResult = file_put_contents($filePath, $fileContent);
    echo "   - Write result: " . ($writeResult !== false ? 'success' : 'failed') . "\n";
    
    if ($writeResult !== false) {
        echo "   - SUCCESS: File created manually!\n";
        echo "   - File exists: " . (file_exists($filePath) ? 'true' : 'false') . "\n";
    } else {
        echo "   - ERROR: Manual file creation failed\n";
    }
}

echo "\nTest completed!\n";
?>