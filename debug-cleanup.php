<?php
/**
 * Debug cleanup method
 */

// Define constants
define('PROJECT_ROOT', __DIR__);
define('CONFIG_DIR', PROJECT_ROOT . '/config');
define('CONTENT_DIR', PROJECT_ROOT . '/content');

// Include necessary files
require_once 'includes/DemoModeManager.php';

echo "Debug Cleanup Method\n";
echo "===================\n\n";

// Simulate a demo session
$_SESSION['demo_mode'] = true;
$_SESSION['demo_session_id'] = 'demo_68c85f88be5944.95134505';
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

echo "3. Testing cleanupDemoContent() Step by Step:\n";

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
    echo "   - ERROR: cleanupDemoContent would return false\n";
} else {
    echo "   - SUCCESS: cleanupDemoContent would proceed\n";
    
    $sessionId = $_SESSION['demo_session_id'] ?? null;
    echo "   - Session ID: " . ($sessionId ?? 'null') . "\n";
    
    if (!$sessionId) {
        echo "   - No session ID, using 'all' fallback\n";
        $sessionId = 'all';
    }
    
    echo "   - Using session ID: $sessionId\n";
    
    $cleanedFiles = 0;
    $demoContentDir = $demoManager->getDemoContentDir();
    echo "   - Demo content dir: $demoContentDir\n";
    
    if (is_dir($demoContentDir)) {
        echo "   - Demo content dir exists: true\n";
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($demoContentDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        $fileCount = 0;
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $fileCount++;
                echo "     * File: " . $file->getFilename() . "\n";
                
                $content = file_get_contents($file->getPathname());
                echo "       Content length: " . strlen($content) . " bytes\n";
                
                // Check if this file belongs to current demo session or is demo content
                $shouldDelete = false;
                
                if ($sessionId === 'all') {
                    // Clean up all demo content files
                    if (preg_match('/demo_content["\s]*:\s*true/', $content)) {
                        $shouldDelete = true;
                        echo "       - Would delete (demo_content: true)\n";
                    } else {
                        echo "       - Would keep (not demo content)\n";
                    }
                } else {
                    // Clean up files from specific session
                    if (preg_match('/demo_session_id["\s]*:\s*["\']?' . preg_quote($sessionId, '/') . '["\']?/', $content)) {
                        $shouldDelete = true;
                        echo "       - Would delete (matches session ID)\n";
                    } else {
                        echo "       - Would keep (different session ID)\n";
                    }
                }
                
                if ($shouldDelete) {
                    $cleanedFiles++;
                }
            }
        }
        
        echo "   - Total files found: $fileCount\n";
        echo "   - Files that would be cleaned: $cleanedFiles\n";
    } else {
        echo "   - Demo content dir exists: false\n";
    }
}

echo "\n4. Testing actual cleanupDemoContent() method:\n";
$result = $demoManager->cleanupDemoContent();
echo "   - Method returned: " . ($result !== false ? $result : 'false') . "\n";

echo "\nTest completed!\n";
?>