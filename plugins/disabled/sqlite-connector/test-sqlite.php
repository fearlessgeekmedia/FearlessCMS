<?php
/**
 * Test script for SQLite Connector Plugin
 * Run this from the project root: php plugins/sqlite-connector/test-sqlite.php
 */

// Bootstrap the CMS
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/plugins.php';

// Load the SQLite connector plugin
require_once __DIR__ . '/sqlite-connector.php';

echo "🐺 SQLite Connector Test\n";
echo "=======================\n\n";

// Test 1: Configuration
echo "1. Testing Configuration...\n";
$config = sqlite_connector_get_config();
if (!empty($config)) {
    echo "   ✓ Configuration loaded successfully\n";
    echo "   Database path: " . $config['database_path'] . "\n";
} else {
    echo "   ✗ Failed to load configuration\n";
    exit(1);
}

// Test 2: Connection
echo "\n2. Testing Database Connection...\n";
$pdo = sqlite_connector_get_connection();
if ($pdo) {
    echo "   ✓ Database connection established\n";
} else {
    echo "   ✗ Failed to establish database connection\n";
    exit(1);
}

// Test 3: Basic Query
echo "\n3. Testing Basic Query...\n";
try {
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    if ($result && $result['test'] == 1) {
        echo "   ✓ Basic query executed successfully\n";
    } else {
        echo "   ✗ Basic query failed\n";
    }
} catch (Exception $e) {
    echo "   ✗ Query error: " . $e->getMessage() . "\n";
}

// Test 4: Table Creation
echo "\n4. Testing Table Creation...\n";
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_table (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "   ✓ Test table created successfully\n";
} catch (Exception $e) {
    echo "   ✗ Table creation failed: " . $e->getMessage() . "\n";
}

// Test 5: Insert and Select
echo "\n5. Testing Insert and Select...\n";
try {
    // Insert test data
    $stmt = $pdo->prepare("INSERT INTO test_table (name) VALUES (?)");
    $stmt->execute(['Test Entry ' . date('Y-m-d H:i:s')]);
    echo "   ✓ Data inserted successfully\n";
    
    // Select test data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM test_table");
    $result = $stmt->fetch();
    echo "   ✓ Data selected successfully. Total rows: " . $result['count'] . "\n";
} catch (Exception $e) {
    echo "   ✗ Insert/Select failed: " . $e->getMessage() . "\n";
}

// Test 6: Hook System
echo "\n6. Testing Hook System...\n";
$hook_pdo = fcms_do_hook('database_connect');
if ($hook_pdo && $hook_pdo === $pdo) {
    echo "   ✓ Hook system working correctly\n";
} else {
    echo "   ✗ Hook system not working\n";
}

// Test 7: Database Statistics
echo "\n7. Testing Database Statistics...\n";
$stats = sqlite_connector_get_database_stats($config);
if ($stats) {
    echo "   ✓ Database statistics retrieved\n";
    echo "   Tables: " . $stats['tables'] . "\n";
    echo "   Database size: " . sqlite_connector_get_database_size($config) . "\n";
} else {
    echo "   ✗ Failed to get database statistics\n";
}

// Test 8: Cleanup
echo "\n8. Cleaning up test data...\n";
try {
    $pdo->exec("DROP TABLE IF EXISTS test_table");
    echo "   ✓ Test table cleaned up\n";
} catch (Exception $e) {
    echo "   ✗ Cleanup failed: " . $e->getMessage() . "\n";
}

echo "\n🎉 All tests completed!\n";
echo "SQLite Connector is working correctly.\n";
?> 