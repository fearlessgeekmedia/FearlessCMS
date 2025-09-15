<?php
/**
 * Debug session variables inside method
 */

// Define constants
define('PROJECT_ROOT', __DIR__);
define('CONFIG_DIR', PROJECT_ROOT . '/config');
define('CONTENT_DIR', PROJECT_ROOT . '/content');

// Include necessary files
require_once 'includes/DemoModeManager.php';

echo "Debug Session Variables\n";
echo "======================\n\n";

// Simulate a demo session
$_SESSION['demo_mode'] = true;
$_SESSION['demo_session_id'] = 'test_demo_session_123';
$_SESSION['demo_start_time'] = time();
$_SESSION['username'] = 'demo';

echo "1. Session Variables Outside Method:\n";
echo "   - \$_SESSION is set: " . (isset($_SESSION) ? 'true' : 'false') . "\n";
echo "   - demo_mode: " . (isset($_SESSION['demo_mode']) ? ($_SESSION['demo_mode'] ? 'true' : 'false') : 'not set') . "\n";
echo "   - username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'not set') . "\n";
echo "   - username === 'demo': " . (($_SESSION['username'] ?? '') === 'demo' ? 'true' : 'false') . "\n\n";

echo "2. Testing Demo Manager Methods:\n";
$demoManager = new DemoModeManager();
echo "   - isDemoSession(): " . ($demoManager->isDemoSession() ? 'true' : 'false') . "\n";
echo "   - isDemoUserSession(): " . ($demoManager->isDemoUserSession() ? 'true' : 'false') . "\n\n";

echo "3. Testing createDemoContentFile Method Call:\n";
$testPath = 'debug-session-page';
$testTitle = 'Debug Session Page';
$testContent = '# Debug Session Page\n\nThis tests session variables inside the method.';

echo "   - Calling createDemoContentFile...\n";

// Let's try to debug what's happening inside the method
class DebugSessionDemoModeManager extends DemoModeManager {
    public function createDemoContentFile($path, $title, $content, $metadata = []) {
        echo "   DEBUG: Inside createDemoContentFile method\n";
        
        // Check session variables inside the method
        echo "   DEBUG: \$_SESSION is set: " . (isset($_SESSION) ? 'true' : 'false') . "\n";
        echo "   DEBUG: demo_mode: " . (isset($_SESSION['demo_mode']) ? ($_SESSION['demo_mode'] ? 'true' : 'false') : 'not set') . "\n";
        echo "   DEBUG: username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'not set') . "\n";
        echo "   DEBUG: username === 'demo': " . (($_SESSION['username'] ?? '') === 'demo' ? 'true' : 'false') . "\n";
        
        // Check demo session detection methods
        $isDemoSession = $this->isDemoSession() || $this->isDemoUserSession();
        echo "   DEBUG: Initial isDemoSession: " . ($isDemoSession ? 'true' : 'false') . "\n";
        
        // FALLBACK: Check username directly if session detection fails
        echo "   DEBUG: Checking fallback condition...\n";
        echo "   DEBUG: !\$isDemoSession: " . (!$isDemoSession ? 'true' : 'false') . "\n";
        echo "   DEBUG: isset(\$_SESSION['username']): " . (isset($_SESSION['username']) ? 'true' : 'false') . "\n";
        echo "   DEBUG: \$_SESSION['username'] === 'demo': " . (($_SESSION['username'] ?? '') === 'demo' ? 'true' : 'false') . "\n";
        
        if (!$isDemoSession && isset($_SESSION['username']) && $_SESSION['username'] === 'demo') {
            echo "   DEBUG: Fallback triggered: username is 'demo'\n";
            $isDemoSession = true;
        } else {
            echo "   DEBUG: Fallback NOT triggered\n";
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

$debugDemoManager = new DebugSessionDemoModeManager();
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