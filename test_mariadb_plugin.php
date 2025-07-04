<?php
// Test the MariaDB Connector Plugin
echo "Testing MariaDB Connector Plugin...\n";

// Include the necessary FearlessCMS files
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/plugins.php';

// Test the plugin hooks
echo "Testing database connection hook...\n";
$pdo = fcms_do_hook('database_connect');
echo "Type of pdo: " . gettype($pdo) . "\n";
var_dump($pdo);

if ($pdo instanceof PDO) {
    echo "✓ Database connection successful!\n";
    
    // Test creating a table
    echo "Testing table creation...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS plugin_test (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Test table created successfully!\n";
    
    // Test inserting data
    echo "Testing data insertion...\n";
    $stmt = $pdo->prepare("INSERT INTO plugin_test (name, description) VALUES (?, ?)");
    $stmt->execute(['MariaDB Connector Test', 'This is a test record from the plugin']);
    echo "✓ Data inserted successfully!\n";
    
    // Test querying data
    echo "Testing data retrieval...\n";
    $stmt = $pdo->query("SELECT * FROM plugin_test ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch();
    echo "✓ Latest record: " . $row['name'] . " - " . $row['description'] . "\n";
    
    // Test the query hook
    echo "Testing query hook...\n";
    $query = 'SELECT COUNT(*) as count FROM plugin_test';
    $stmt = fcms_do_hook('database_query', $query, []);
    if ($stmt) {
        $result = $stmt->fetch();
        echo "✓ Total records in test table: " . $result['count'] . "\n";
    }
    
} else {
    echo "✗ Database connection failed!\n";
}

echo "\nPlugin test completed!\n";
?> 