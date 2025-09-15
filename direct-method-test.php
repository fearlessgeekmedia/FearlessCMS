<?php
/**
 * Direct method test
 */

// Define constants
define('PROJECT_ROOT', __DIR__);
define('CONFIG_DIR', PROJECT_ROOT . '/config');
define('CONTENT_DIR', PROJECT_ROOT . '/content');

// Include necessary files
require_once 'includes/DemoModeManager.php';

echo "Direct Method Test\n";
echo "=================\n\n";

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
$testPath = 'direct-test-page';
$testTitle = 'Direct Test Page';
$testContent = '# Direct Test Page\n\nThis tests the actual method call directly.';

echo "   - Calling createDemoContentFile...\n";

// Let's try to debug what's happening inside the method
// We'll create a custom version that adds debug output

class DebugDemoModeManager extends DemoModeManager {
    public function createDemoContentFile($path, $title, $content, $metadata = []) {
        echo "   DEBUG: Inside createDemoContentFile method\n";
        
        // Check demo session detection methods
        $isDemoSession = $this->isDemoSession() || $this->isDemoUserSession();
        echo "   DEBUG: Initial isDemoSession: " . ($isDemoSession ? 'true' : 'false') . "\n";
        
        // FALLBACK: Check username directly if session detection fails
        if (!$isDemoSession && isset($_SESSION['username']) && $_SESSION['username'] === 'demo') {
            echo "   DEBUG: Fallback triggered: username is 'demo'\n";
            $isDemoSession = true;
        }
        
        echo "   DEBUG: Final isDemoSession: " . ($isDemoSession ? 'true' : 'false') . "\n";
        
        if (!$isDemoSession) {
            echo "   DEBUG: createDemoContentFile rejected - not a demo user\n";
            return false;
        }
        
        echo "   DEBUG: Proceeding with file creation\n";
        
        // Generate unique session-based filename to avoid conflicts
        $sessionId = $_SESSION['demo_session_id'] ?? uniqid('demo_', true);
        $timestamp = time();
        
        // Determine if this is a blog post or page based on path
        $isBlogPost = strpos($path, 'blog/') === 0;
        
        if ($isBlogPost) {
            $blogPath = substr($path, 5); // Remove 'blog/' prefix
            $filePath = $this->demoContentDir . '/blog/' . $blogPath . '.md';
        } else {
            $filePath = $this->demoContentDir . '/pages/' . $path . '.md';
        }
        
        echo "   DEBUG: File path: $filePath\n";
        
        $dir = dirname($filePath);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $defaultMetadata = [
            'title' => $title,
            'demo_content' => true,
            'demo_session_id' => $sessionId,
            'created_at' => date('Y-m-d H:i:s'),
            'template' => $isBlogPost ? 'post' : 'page-with-sidebar'
        ];
        
        $metadata = array_merge($defaultMetadata, $metadata);
        
        $frontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
        $fileContent = $frontmatter . "\n\n" . $content;
        
        echo "   DEBUG: Writing file...\n";
        $result = file_put_contents($filePath, $fileContent) !== false;
        echo "   DEBUG: Write result: " . ($result ? 'success' : 'failed') . "\n";
        
        return $result;
    }
}

$debugDemoManager = new DebugDemoModeManager();
$result = $debugDemoManager->createDemoContentFile($testPath, $testTitle, $testContent, [
    'template' => 'page-with-sidebar'
]);

echo "   - Method returned: " . ($result ? 'true' : 'false') . "\n";

if ($result) {
    echo "   - SUCCESS: Method succeeded!\n";
    $expectedFile = $debugDemoManager->getDemoContentDir() . '/pages/' . $testPath . '.md';
    echo "   - Expected file: $expectedFile\n";
    echo "   - File exists: " . (file_exists($expectedFile) ? 'true' : 'false') . "\n";
} else {
    echo "   - FAILED: Method returned false\n";
}

echo "\nTest completed!\n";
?>