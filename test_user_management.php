<?php
require_once 'includes/config.php';

echo "Testing User Management\n";
echo "======================\n\n";

// Test 1: Load users
echo "1. Loading users from config/users.json:\n";
$users = json_decode(file_get_contents(CONFIG_DIR . '/users.json'), true);
echo "   Found " . count($users) . " users:\n";
foreach ($users as $user) {
    echo "   - " . $user['username'] . " (role: " . $user['role'] . ")\n";
}
echo "\n";

// Test 2: Test authentication
echo "2. Testing authentication:\n";
require_once 'includes/auth.php';

// Test with admin user
$result = login('admin', 'admin');
echo "   Login test for admin/admin: " . ($result ? "SUCCESS" : "FAILED") . "\n";

// Test with bob user
$result = login('bob', 'password');
echo "   Login test for bob/password: " . ($result ? "SUCCESS" : "FAILED") . "\n";

// Test 3: Test permission checking
echo "\n3. Testing permission checking:\n";
$result = fcms_check_permission('admin', 'manage_users');
echo "   Admin can manage_users: " . ($result ? "YES" : "NO") . "\n";

$result = fcms_check_permission('bob', 'manage_users');
echo "   Bob can manage_users: " . ($result ? "YES" : "NO") . "\n";

echo "\nTest completed.\n";
?> 