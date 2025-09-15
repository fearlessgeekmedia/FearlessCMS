<?php
/**
 * Test session scope
 */

echo "Test Session Scope\n";
echo "==================\n\n";

echo "1. Before session_start():\n";
echo "   - \$_SESSION is set: " . (isset($_SESSION) ? 'true' : 'false') . "\n";
echo "   - session_status(): " . (function_exists('session_status') ? session_status() : 'function not available') . "\n\n";

// Try to start session
if (function_exists('session_start')) {
    echo "2. Starting session...\n";
    $sessionStarted = @session_start();
    echo "   - session_start() result: " . ($sessionStarted ? 'true' : 'false') . "\n";
    echo "   - session_status(): " . (function_exists('session_status') ? session_status() : 'function not available') . "\n";
    echo "   - session_id(): " . (function_exists('session_id') ? session_id() : 'function not available') . "\n\n";
} else {
    echo "2. session_start() function not available\n\n";
}

echo "3. After session_start():\n";
echo "   - \$_SESSION is set: " . (isset($_SESSION) ? 'true' : 'false') . "\n";

if (isset($_SESSION)) {
    echo "   - \$_SESSION is array: " . (is_array($_SESSION) ? 'true' : 'false') . "\n";
    echo "   - \$_SESSION count: " . count($_SESSION) . "\n";
} else {
    echo "   - \$_SESSION is not set\n";
}

echo "\n4. Setting session variables:\n";
$_SESSION['test_var'] = 'test_value';
$_SESSION['username'] = 'demo';
echo "   - Set test_var: " . ($_SESSION['test_var'] ?? 'not set') . "\n";
echo "   - Set username: " . ($_SESSION['username'] ?? 'not set') . "\n\n";

echo "5. Testing in function scope:\n";
function testSessionScope() {
    echo "   - Inside function - \$_SESSION is set: " . (isset($_SESSION) ? 'true' : 'false') . "\n";
    if (isset($_SESSION)) {
        echo "   - Inside function - username: " . ($_SESSION['username'] ?? 'not set') . "\n";
    }
}

testSessionScope();

echo "\n6. Testing in class method scope:\n";
class TestSessionClass {
    public function testSessionScope() {
        echo "   - Inside class method - \$_SESSION is set: " . (isset($_SESSION) ? 'true' : 'false') . "\n";
        if (isset($_SESSION)) {
            echo "   - Inside class method - username: " . ($_SESSION['username'] ?? 'not set') . "\n";
        }
    }
}

$testClass = new TestSessionClass();
$testClass->testSessionScope();

echo "\nTest completed!\n";
?>