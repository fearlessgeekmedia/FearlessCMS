<?php
/**
 * Test script to check PHP session extension availability
 */

echo "=== PHP Session Extension Test ===\n\n";

// Test 1: Check PHP version
echo "1. PHP Version:\n";
echo "   Version: " . phpversion() . "\n";
echo "   Version ID: " . PHP_VERSION_ID . "\n\n";

// Test 2: Check if session extension is loaded
echo "2. Session Extension Check:\n";
if (extension_loaded('session')) {
    echo "   ✓ Session extension is loaded\n";
} else {
    echo "   ✗ Session extension is NOT loaded\n";
    echo "   This is the root cause of the session_start() error.\n\n";
    echo "   To fix this, you need to:\n";
    echo "   1. Install the PHP session extension\n";
    echo "   2. Enable it in your php.ini file\n";
    echo "   3. Restart your web server\n\n";
    exit(1);
}

// Test 3: Check session functions
echo "\n3. Session Functions:\n";
$functions = [
    'session_start' => 'session_start()',
    'session_id' => 'session_id()',
    'session_regenerate_id' => 'session_regenerate_id()',
    'session_status' => 'session_status()',
    'session_destroy' => 'session_destroy()'
];

foreach ($functions as $func => $desc) {
    if (function_exists($func)) {
        echo "   ✓ $desc is available\n";
    } else {
        echo "   ✗ $desc is NOT available\n";
    }
}

// Test 4: Try to start a session
echo "\n4. Session Start Test:\n";
try {
    session_start();
    echo "   ✓ Session started successfully\n";
    echo "   Session ID: " . (function_exists('session_id') ? session_id() : 'not_available') . "\n";
    
    // Test session data
    $_SESSION['test'] = 'test_value';
    echo "   ✓ Session data written successfully\n";
    
    if (isset($_SESSION['test']) && $_SESSION['test'] === 'test_value') {
        echo "   ✓ Session data read successfully\n";
    } else {
        echo "   ✗ Session data read failed\n";
    }
    
    // Clean up
    if (function_exists('session_destroy')) {
        session_destroy();
        echo "   ✓ Session destroyed successfully\n";
    } else {
        echo "   ⚠ session_destroy() not available\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Session start failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Check PHP configuration
echo "\n5. PHP Configuration:\n";
$configs = [
    'session.save_handler' => 'Session save handler',
    'session.save_path' => 'Session save path',
    'session.use_cookies' => 'Session use cookies',
    'session.cookie_httponly' => 'Session cookie httponly'
];

foreach ($configs as $key => $desc) {
    $value = ini_get($key);
    echo "   $desc: " . ($value ?: 'not set') . "\n";
}

echo "\n=== Test Complete ===\n";
echo "If all tests passed, the session extension is working correctly.\n";
echo "If you're still getting session_start() errors, check your web server configuration.\n"; 