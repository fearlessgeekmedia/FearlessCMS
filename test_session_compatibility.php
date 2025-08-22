<?php
/**
 * Test script to verify session compatibility
 * Tests session functionality with backward compatibility for older PHP versions
 */

echo "=== FearlessCMS Session Compatibility Test ===\n\n";

// Test 1: Check PHP version
echo "1. PHP Version Check:\n";
echo "   PHP Version: " . phpversion() . "\n";
echo "   PHP Version ID: " . PHP_VERSION_ID . "\n\n";

// Test 2: Check session extension
echo "2. Session Extension Check:\n";
if (extension_loaded('session')) {
    echo "   ✓ Session extension is loaded\n";
} else {
    echo "   ✗ Session extension is NOT loaded\n";
    exit(1);
}

// Test 3: Check session functions
echo "\n3. Session Functions Check:\n";
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

// Test 4: Test session initialization
echo "\n4. Session Initialization Test:\n";
try {
    // Test the backward-compatible session check
    if (function_exists('session_status')) {
        $sessionActive = session_status() === PHP_SESSION_ACTIVE;
        echo "   Using session_status(): " . ($sessionActive ? "Active" : "Not Active") . "\n";
    } else {
        $sessionActive = isset($_SESSION) || (function_exists('session_id') && session_id() !== '');
        echo "   Using fallback check: " . ($sessionActive ? "Active" : "Not Active") . "\n";
    }
    
    if (!$sessionActive) {
        session_start();
        echo "   ✓ Session started successfully\n";
    } else {
        echo "   ✓ Session already active\n";
    }
} catch (Exception $e) {
    echo "   ✗ Session initialization failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Test session data
echo "\n5. Session Data Test:\n";
$_SESSION['test_key'] = 'test_value_' . time();
echo "   ✓ Session data written\n";

if (isset($_SESSION['test_key'])) {
    echo "   ✓ Session data read successfully: " . $_SESSION['test_key'] . "\n";
} else {
    echo "   ✗ Session data read failed\n";
}

// Test 6: Test session regeneration
echo "\n6. Session Regeneration Test:\n";
    $oldSessionId = function_exists('session_id') ? session_id() : 'not_available';
    echo "   Old session ID: " . $oldSessionId . "\n";

    if (function_exists('session_regenerate_id')) {
        session_regenerate_id(true);
        $newSessionId = function_exists('session_id') ? session_id() : 'not_available';
        echo "   New session ID: " . $newSessionId . "\n";
    
    if ($oldSessionId !== $newSessionId) {
        echo "   ✓ Session ID regenerated successfully\n";
    } else {
        echo "   ✗ Session ID regeneration failed\n";
    }
} else {
    echo "   ⚠ session_regenerate_id() not available, skipping test\n";
}

// Test 7: Test session cleanup
echo "\n7. Session Cleanup Test:\n";
if (function_exists('session_destroy')) {
    session_destroy();
    echo "   ✓ Session destroyed successfully\n";
} else {
    echo "   ✗ session_destroy() not available\n";
}

echo "\n=== Test Complete ===\n";
echo "If all tests passed, your PHP environment supports FearlessCMS session functionality.\n"; 