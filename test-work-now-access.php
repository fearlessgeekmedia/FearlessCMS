<?php
/**
 * Test accessing the work-now page
 */

// Define constants
define('PROJECT_ROOT', __DIR__);
define('CONFIG_DIR', PROJECT_ROOT . '/config');
define('CONTENT_DIR', PROJECT_ROOT . '/content');

// Include necessary files
require_once 'includes/DemoModeManager.php';

echo "Test work-now Page Access\n";
echo "========================\n\n";

// Simulate a demo session
$_SESSION['demo_mode'] = true;
$_SESSION['demo_session_id'] = 'demo_68c85f88be5944.95134505';
$_SESSION['demo_start_time'] = time();
$_SESSION['username'] = 'demo';

echo "1. Session Setup:\n";
echo "   - demo_mode: " . (isset($_SESSION['demo_mode']) ? ($_SESSION['demo_mode'] ? 'true' : 'false') : 'not set') . "\n";
echo "   - username: " . ($_SESSION['username'] ?? 'not set') . "\n";
echo "   - demo_session_id: " . ($_SESSION['demo_session_id'] ?? 'not set') . "\n\n";

echo "2. Testing Demo Manager Methods:\n";
$demoManager = new DemoModeManager();
echo "   - isDemoSession(): " . ($demoManager->isDemoSession() ? 'true' : 'false') . "\n";
echo "   - isDemoUserSession(): " . ($demoManager->isDemoUserSession() ? 'true' : 'false') . "\n\n";

echo "3. Testing Content Routing Logic for 'work-now':\n";
$path = 'work-now';
echo "   - Path: $path\n";

// This mimics the logic from index.php
$contentDir = CONTENT_DIR;
$isDemoUser = $demoManager->isDemoSession() || $demoManager->isDemoUserSession();

echo "   - Initial isDemoUser: " . ($isDemoUser ? 'true' : 'false') . "\n";

// FALLBACK: Check username directly if session detection fails
if (!$isDemoUser && isset($_SESSION['username']) && $_SESSION['username'] === 'demo') {
    echo "   - Fallback triggered: username is 'demo'\n";
    $isDemoUser = true;
}

echo "   - Final isDemoUser: " . ($isDemoUser ? 'true' : 'false') . "\n";

if ($isDemoUser) {
    $contentDir = $demoManager->getDemoContentDir();
    echo "   - Using demo content directory: $contentDir\n";
    
    // Check demo pages first, then demo blog
    if ($path === 'home' || $path === 'about' || $path === 'contact') {
        $contentFile = $contentDir . '/pages/' . $path . '.md';
    } elseif (strpos($path, 'blog/') === 0) {
        $blogPath = substr($path, 5); // Remove 'blog/' prefix
        $contentFile = $contentDir . '/blog/' . $blogPath . '.md';
    } else {
        $contentFile = $contentDir . '/pages/' . $path . '.md';
    }
    
    echo "   - Content file: $contentFile\n";
    echo "   - File exists: " . (file_exists($contentFile) ? 'true' : 'false') . "\n";
    
    if (file_exists($contentFile)) {
        echo "   - SUCCESS: File found!\n";
        $content = file_get_contents($contentFile);
        echo "   - Content length: " . strlen($content) . " bytes\n";
        echo "   - Content preview: " . substr($content, 0, 200) . "...\n";
        
        // Parse frontmatter
        if (preg_match('/<!-- json\s*(.*?)\s*-->/s', $content, $matches)) {
            $metadata = json_decode($matches[1], true);
            if ($metadata) {
                echo "   - Title: " . ($metadata['title'] ?? 'N/A') . "\n";
                echo "   - Demo content: " . (isset($metadata['demo_content']) ? ($metadata['demo_content'] ? 'true' : 'false') : 'not set') . "\n";
                echo "   - Session ID: " . ($metadata['demo_session_id'] ?? 'N/A') . "\n";
            }
        }
    } else {
        echo "   - ERROR: File not found!\n";
        echo "   - This would cause 'Content file not found' error\n";
    }
} else {
    echo "   - ERROR: Demo user detection failed!\n";
    echo "   - This means the content will be looked for in the main content directory\n";
}

echo "\n4. Checking demo content directory:\n";
$demoContentDir = $demoManager->getDemoContentDir();
echo "   - Demo content dir: $demoContentDir\n";
$pagesDir = $demoContentDir . '/pages';
echo "   - Pages dir: $pagesDir\n";
echo "   - Pages dir exists: " . (is_dir($pagesDir) ? 'true' : 'false') . "\n";

if (is_dir($pagesDir)) {
    $files = glob($pagesDir . '/*.md');
    echo "   - Files in pages directory: " . count($files) . "\n";
    foreach ($files as $file) {
        echo "     * " . basename($file) . "\n";
    }
}

echo "\nTest completed!\n";
?>