<?php
/**
 * Comprehensive PHP Compatibility Test for FearlessCMS
 * Tests all functions that have been made backward compatible
 */

echo "=== FearlessCMS PHP Compatibility Test ===\n\n";

// Test 1: PHP Version Information
echo "1. PHP Version Information:\n";
echo "   PHP Version: " . phpversion() . "\n";
echo "   PHP Version ID: " . PHP_VERSION_ID . "\n";
echo "   PHP SAPI: " . php_sapi_name() . "\n\n";

// Test 2: Session Functions
echo "2. Session Functions:\n";
$sessionFunctions = [
    'session_start' => 'session_start()',
    'session_id' => 'session_id()',
    'session_regenerate_id' => 'session_regenerate_id()',
    'session_status' => 'session_status()',
    'session_destroy' => 'session_destroy()'
];

foreach ($sessionFunctions as $func => $desc) {
    if (function_exists($func)) {
        echo "   ✓ $desc is available\n";
    } else {
        echo "   ✗ $desc is NOT available\n";
    }
}

// Test 3: Security Functions
echo "\n3. Security Functions:\n";
$securityFunctions = [
    'password_verify' => 'password_verify()',
    'password_hash' => 'password_hash()',
    'random_bytes' => 'random_bytes()',
    'openssl_random_pseudo_bytes' => 'openssl_random_pseudo_bytes()',
    'hash_equals' => 'hash_equals()',
    'hash' => 'hash()',
    'bin2hex' => 'bin2hex()'
];

foreach ($securityFunctions as $func => $desc) {
    if (function_exists($func)) {
        echo "   ✓ $desc is available\n";
    } else {
        echo "   ✗ $desc is NOT available\n";
    }
}

// Test 4: JSON Functions
echo "\n4. JSON Functions:\n";
$jsonFunctions = [
    'json_encode' => 'json_encode()',
    'json_decode' => 'json_decode()'
];

foreach ($jsonFunctions as $func => $desc) {
    if (function_exists($func)) {
        echo "   ✓ $desc is available\n";
    } else {
        echo "   ✗ $desc is NOT available\n";
    }
}

// Test 5: File System Functions
echo "\n5. File System Functions:\n";
$fileFunctions = [
    'file_get_contents' => 'file_get_contents()',
    'file_put_contents' => 'file_put_contents()',
    'file_exists' => 'file_exists()',
    'is_dir' => 'is_dir()',
    'mkdir' => 'mkdir()',
    'chmod' => 'chmod()'
];

foreach ($fileFunctions as $func => $desc) {
    if (function_exists($func)) {
        echo "   ✓ $desc is available\n";
    } else {
        echo "   ✗ $desc is NOT available\n";
    }
}

// Test 6: Session Extension
echo "\n6. Session Extension:\n";
if (extension_loaded('session')) {
    echo "   ✓ Session extension is loaded\n";
} else {
    echo "   ✗ Session extension is NOT loaded\n";
}

// Test 7: OpenSSL Extension
echo "\n7. OpenSSL Extension:\n";
if (extension_loaded('openssl')) {
    echo "   ✓ OpenSSL extension is loaded\n";
} else {
    echo "   ✗ OpenSSL extension is NOT loaded\n";
}

// Test 8: Hash Extension
echo "\n8. Hash Extension:\n";
if (extension_loaded('hash')) {
    echo "   ✓ Hash extension is loaded\n";
} else {
    echo "   ✗ Hash extension is NOT loaded\n";
}

// Test 9: Test Session Initialization (Backward Compatible)
echo "\n9. Session Initialization Test:\n";
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
}

// Test 10: Test CSRF Token Generation (Backward Compatible)
echo "\n10. CSRF Token Generation Test:\n";
try {
    if (function_exists('random_bytes')) {
        $token = bin2hex(random_bytes(32));
        echo "   ✓ Using random_bytes(): " . substr($token, 0, 16) . "...\n";
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        $token = bin2hex(openssl_random_pseudo_bytes(32));
        echo "   ✓ Using openssl_random_pseudo_bytes(): " . substr($token, 0, 16) . "...\n";
    } else {
        $token = bin2hex(md5(uniqid(mt_rand(), true)));
        echo "   ✓ Using fallback method: " . substr($token, 0, 16) . "...\n";
    }
} catch (Exception $e) {
    echo "   ✗ CSRF token generation failed: " . $e->getMessage() . "\n";
}

// Test 11: Test Hash Comparison (Backward Compatible)
echo "\n11. Hash Comparison Test:\n";
$token1 = "test_token_1";
$token2 = "test_token_2";
$token3 = "test_token_1";

if (function_exists('hash_equals')) {
    $result1 = hash_equals($token1, $token2);
    $result2 = hash_equals($token1, $token3);
    echo "   ✓ Using hash_equals(): " . ($result1 ? "true" : "false") . " / " . ($result2 ? "true" : "false") . "\n";
} else {
    $result1 = $token1 === $token2;
    $result2 = $token1 === $token3;
    echo "   ✓ Using fallback comparison: " . ($result1 ? "true" : "false") . " / " . ($result2 ? "true" : "false") . "\n";
}

// Test 12: Test Session Regeneration (Backward Compatible)
echo "\n12. Session Regeneration Test:\n";
if (function_exists('session_regenerate_id')) {
    $oldId = function_exists('session_id') ? session_id() : 'not_available';
    session_regenerate_id(true);
    $newId = function_exists('session_id') ? session_id() : 'not_available';
    if ($oldId !== $newId) {
        echo "   ✓ Session ID regenerated successfully\n";
    } else {
        echo "   ✗ Session ID regeneration failed\n";
    }
} else {
    echo "   ⚠ session_regenerate_id() not available, skipping test\n";
}

// Test 13: Test JSON Operations
echo "\n13. JSON Operations Test:\n";
$testData = ['test' => 'value', 'number' => 123];
$jsonString = json_encode($testData);
$decodedData = json_decode($jsonString, true);

if ($jsonString && $decodedData && $decodedData['test'] === 'value') {
    echo "   ✓ JSON encode/decode working correctly\n";
} else {
    echo "   ✗ JSON operations failed\n";
}

// Test 14: Test File Operations
echo "\n14. File Operations Test:\n";
$testFile = 'test_compatibility_temp.txt';
$testContent = 'Test content for compatibility check';

if (file_put_contents($testFile, $testContent) !== false) {
    $readContent = file_get_contents($testFile);
    if ($readContent === $testContent) {
        echo "   ✓ File write/read operations working\n";
    } else {
        echo "   ✗ File read operation failed\n";
    }
    unlink($testFile); // Clean up
} else {
    echo "   ✗ File write operation failed\n";
}

// Test 15: Overall Compatibility Assessment
echo "\n15. Overall Compatibility Assessment:\n";

$criticalFunctions = [
    'session_start', 'session_id', 'json_encode', 'json_decode',
    'file_get_contents', 'file_put_contents', 'file_exists'
];

$missingCritical = [];
foreach ($criticalFunctions as $func) {
    if (!function_exists($func)) {
        $missingCritical[] = $func;
    }
}

if (empty($missingCritical)) {
    echo "   ✓ All critical functions are available\n";
    echo "   ✓ FearlessCMS should work on this PHP version\n";
} else {
    echo "   ✗ Missing critical functions: " . implode(', ', $missingCritical) . "\n";
    echo "   ✗ FearlessCMS may not work properly on this PHP version\n";
}

echo "\n=== Test Complete ===\n";
echo "This test checks compatibility with PHP versions from 5.4+ to 8.2+\n";
echo "FearlessCMS has been designed to work with backward compatibility for older PHP versions.\n"; 