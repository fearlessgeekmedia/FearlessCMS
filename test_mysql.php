<?php
// Test MariaDB connectivity (MariaDB is a MySQL-compatible database)
echo "Testing MariaDB connectivity...\n";

// Check if PDO MySQL driver is available (works with MariaDB)
$drivers = PDO::getAvailableDrivers();
echo "Available PDO drivers: " . implode(', ', $drivers) . "\n";

// Test with our new fearlesscms user and database
try {
    $pdo = new PDO('mysql:host=localhost;dbname=fearlesscms_test', 'fearlesscms', 'fearlesscms123');
    echo "Successfully connected to MariaDB with fearlesscms user!\n";
    
    // Test creating a simple table
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Test table created successfully!\n";
    
    // Test inserting data
    $stmt = $pdo->prepare("INSERT INTO test_table (name) VALUES (?)");
    $stmt->execute(['FearlessCMS Test']);
    echo "Data inserted successfully!\n";
    
    // Test querying data
    $stmt = $pdo->query("SELECT * FROM test_table");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Query successful! Found " . count($rows) . " rows.\n";
    
    $pdo = null;
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?> 