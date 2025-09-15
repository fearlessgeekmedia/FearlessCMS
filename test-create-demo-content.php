<?php
/**
 * Test createDemoContentFile method directly
 */

// Define constants
define('PROJECT_ROOT', __DIR__);
define('CONFIG_DIR', PROJECT_ROOT . '/config');
define('CONTENT_DIR', PROJECT_ROOT . '/content');

// Include necessary files
require_once 'includes/DemoModeManager.php';

echo "Testing createDemoContentFile Method\n";
echo "====================================\n\n";

// Simulate a demo session
$_SESSION['demo_mode'] = true;
$_SESSION['demo_session_id'] = 'test_demo_session_123';
$_SESSION['demo_start_time'] = time();
$_SESSION['username'] = 'demo';

echo "1. Session Variables:\n";
echo "   - \$_SESSION is set: " . (isset($_SESSION) ? 'true' : 'false') . "\n";
echo "   - demo_mode: " . (isset($_SESSION['demo_mode']) ? ($_SESSION['demo_mode'] ? 'true' : 'false') : 'not set') . "\n";
echo "   - username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'not set') . "\n";
echo "   - demo_session_id: " . (isset($_SESSION['demo_session_id']) ? $_SESSION['demo_session_id'] : 'not set') . "\n\n";

echo "2. Testing Demo Manager Methods:\n";
$demoManager = new DemoModeManager();
echo "   - isDemoSession(): " . ($demoManager->isDemoSession() ? 'true' : 'false') . "\n";
echo "   - isDemoUserSession(): " . ($demoManager->isDemoUserSession() ? 'true' : 'false') . "\n\n";

echo "3. Testing createDemoContentFile Logic Step by Step:\n";

// Check demo session detection methods
$isDemoSession = $demoManager->isDemoSession() || $demoManager->isDemoUserSession();
echo "   - Initial isDemoSession: " . ($isDemoSession ? 'true' : 'false') . "\n";

// FALLBACK: Check username directly if session detection fails
if (!$isDemoSession && isset($_SESSION['username']) && $_SESSION['username'] === 'demo') {
    echo "   - Fallback triggered: username is 'demo'\n";
    $isDemoSession = true;
}

echo "   - Final isDemoSession: " . ($isDemoSession ? 'true' : 'false') . "\n";

if (!$isDemoSession) {
    echo "   - ERROR: createDemoContentFile would return false\n";
} else {
    echo "   - SUCCESS: createDemoContentFile would proceed\n";
    
    // Test the rest of the method
    $path = 'test-direct-page';
    $title = 'Test Direct Page';
    $content = '# Test Direct Page\n\nThis tests the method directly.';
    
    echo "\n4. Testing Method Execution:\n";
    echo "   - Path: $path\n";
    echo "   - Title: $title\n";
    
    // Generate unique session-based filename to avoid conflicts
    $sessionId = $_SESSION['demo_session_id'] ?? uniqid('demo_', true);
    echo "   - Session ID: $sessionId\n";
    
    // Determine if this is a blog post or page based on path
    $isBlogPost = strpos($path, 'blog/') === 0;
    echo "   - Is blog post: " . ($isBlogPost ? 'true' : 'false') . "\n";
    
    if ($isBlogPost) {
        $blogPath = substr($path, 5); // Remove 'blog/' prefix
        $filePath = $demoManager->getDemoContentDir() . '/blog/' . $blogPath . '.md';
    } else {
        $filePath = $demoManager->getDemoContentDir() . '/pages/' . $path . '.md';
    }
    
    echo "   - File path: $filePath\n";
    
    $dir = dirname($filePath);
    echo "   - Directory: $dir\n";
    echo "   - Directory exists: " . (is_dir($dir) ? 'true' : 'false') . "\n";
    
    if (!is_dir($dir)) {
        echo "   - Creating directory...\n";
        $mkdirResult = mkdir($dir, 0755, true);
        echo "   - Directory creation result: " . ($mkdirResult ? 'success' : 'failed') . "\n";
    }
    
    $defaultMetadata = [
        'title' => $title,
        'demo_content' => true,
        'demo_session_id' => $sessionId,
        'created_at' => date('Y-m-d H:i:s'),
        'template' => $isBlogPost ? 'post' : 'page-with-sidebar'
    ];
    
    $metadata = array_merge($defaultMetadata, []);
    
    $frontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
    $fileContent = $frontmatter . "\n\n" . $content;
    
    echo "   - File content length: " . strlen($fileContent) . " bytes\n";
    
    $writeResult = file_put_contents($filePath, $fileContent);
    echo "   - File write result: " . ($writeResult !== false ? 'success' : 'failed') . "\n";
    
    if ($writeResult !== false) {
        echo "   - SUCCESS: File created successfully!\n";
        echo "   - File exists: " . (file_exists($filePath) ? 'true' : 'false') . "\n";
    } else {
        echo "   - ERROR: File creation failed\n";
    }
}

echo "\nTest completed!\n";
?>