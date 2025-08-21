<?php
/*
Plugin Name: SQLite Connector
Description: Provides SQLite database connectivity for FearlessCMS plugins
Version: 1.0.0
Author: Fearless Geek
*/

// Define constants
define('SQLITE_PLUGIN_DIR', PLUGIN_DIR . '/sqlite-connector');
define('SQLITE_CONFIG_DIR', CONTENT_DIR . '/sqlite-connector');
define('SQLITE_CONFIG_FILE', SQLITE_CONFIG_DIR . '/config.json');

// Initialize plugin
function sqlite_connector_init() {
    // Create necessary directories
    if (!file_exists(SQLITE_CONFIG_DIR)) {
        mkdir(SQLITE_CONFIG_DIR, 0755, true);
    }
    
    // Create default config if it doesn't exist
    if (!file_exists(SQLITE_CONFIG_FILE)) {
        $default_config = [
            'database_path' => SQLITE_CONFIG_DIR . '/fearlesscms.db',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        ];
        file_put_contents(SQLITE_CONFIG_FILE, json_encode($default_config, JSON_PRETTY_PRINT));
    }

    // Register admin section
    fcms_register_admin_section('sqlite_connector', [
        'label' => 'SQLite Connector',
        'menu_order' => 36,
        'parent' => 'manage_plugins',
        'render_callback' => 'sqlite_connector_admin_page'
    ]);

    // Register hooks for other plugins to use
    fcms_add_hook('database_connect', 'sqlite_connector_get_connection');
    fcms_add_hook('database_query', 'sqlite_connector_execute_query');
}

// Get database connection
function sqlite_connector_get_connection() {
    static $pdo = null;
    
    if ($pdo === null) {
        $config = sqlite_connector_get_config();
        
        try {
            $dsn = "sqlite:" . $config['database_path'];
            $pdo = new PDO($dsn, null, null, $config['options']);
            
            // Enable foreign key constraints
            $pdo->exec('PRAGMA foreign_keys = ON');
            
            error_log("SQLite Connector: Database connection established");
        } catch (PDOException $e) {
            error_log("SQLite Connector: Connection failed - " . $e->getMessage());
            return null;
        }
    }
    
    return $pdo;
}

// Execute a database query
function sqlite_connector_execute_query($query, $params = []) {
    $pdo = sqlite_connector_get_connection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("SQLite Connector: Query failed - " . $e->getMessage());
        return false;
    }
}

// Get plugin configuration
function sqlite_connector_get_config() {
    if (file_exists(SQLITE_CONFIG_FILE)) {
        return json_decode(file_get_contents(SQLITE_CONFIG_FILE), true);
    }
    return [];
}

// Save plugin configuration
function sqlite_connector_save_config($config) {
    return file_put_contents(SQLITE_CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT));
}

// Test database connection
function sqlite_connector_test_connection($config) {
    try {
        $dsn = "sqlite:" . $config['database_path'];
        $pdo = new PDO($dsn, null, null, $config['options']);
        
        // Test with a simple query
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        
        return $result && $result['test'] == 1;
    } catch (PDOException $e) {
        return false;
    }
}

// Get database file size
function sqlite_connector_get_database_size($config) {
    $db_path = $config['database_path'];
    if (file_exists($db_path)) {
        $size = filesize($db_path);
        if ($size < 1024) {
            return $size . ' B';
        } elseif ($size < 1024 * 1024) {
            return round($size / 1024, 2) . ' KB';
        } else {
            return round($size / (1024 * 1024), 2) . ' MB';
        }
    }
    return '0 B';
}

// Get database statistics
function sqlite_connector_get_database_stats($config) {
    $pdo = sqlite_connector_get_connection();
    if (!$pdo) {
        return null;
    }
    
    try {
        // Get list of tables
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $stats = [
            'tables' => count($tables),
            'table_list' => $tables
        ];
        
        // Get row counts for each table
        $table_counts = [];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $result = $stmt->fetch();
            $table_counts[$table] = $result['count'];
        }
        $stats['table_counts'] = $table_counts;
        
        return $stats;
    } catch (PDOException $e) {
        return null;
    }
}

// Admin page callback
function sqlite_connector_admin_page() {
    $success_message = '';
    $error_message = '';
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_config':
                $config = [
                    'database_path' => trim($_POST['database_path'] ?? SQLITE_CONFIG_DIR . '/fearlesscms.db'),
                    'options' => [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                ];
                
                if (sqlite_connector_save_config($config)) {
                    $success_message = 'Configuration saved successfully!';
                } else {
                    $error_message = 'Failed to save configuration.';
                }
                break;
                
            case 'test_connection':
                $config = sqlite_connector_get_config();
                if (sqlite_connector_test_connection($config)) {
                    $success_message = 'Database connection test successful!';
                } else {
                    $error_message = 'Database connection test failed. Please check your settings.';
                }
                break;
        }
    }
    
    // Load current configuration
    $config = sqlite_connector_get_config();
    
    // Start output buffer
    ob_start();
    ?>
    
    <?php if ($success_message): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>
    
    <h2 class="text-2xl font-bold mb-6">SQLite Connector Settings</h2>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Configuration Form -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold mb-4">Database Configuration</h3>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="save_config">
                
                <div>
                    <label class="block font-medium mb-1">Database File Path</label>
                    <input type="text" name="database_path" 
                           value="<?= htmlspecialchars($config['database_path'] ?? SQLITE_CONFIG_DIR . '/fearlesscms.db') ?>" 
                           class="w-full border rounded px-3 py-2"
                           placeholder="<?= htmlspecialchars(SQLITE_CONFIG_DIR . '/fearlesscms.db') ?>">
                    <p class="text-sm text-gray-600 mt-1">Path to the SQLite database file. The directory must be writable.</p>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        Save Configuration
                    </button>
                    
                    <button type="submit" name="action" value="test_connection" 
                            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                        Test Connection
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Status and Info -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold mb-4">Connection Status</h3>
            
            <?php
            $pdo = sqlite_connector_get_connection();
            if ($pdo): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded mb-4">
                    <strong>✓ Connected</strong><br>
                    SQLite database connection is active and working.
                </div>
                
                <?php
                $db_stats = sqlite_connector_get_database_stats($config);
                if ($db_stats): ?>
                    <div class="mt-4">
                        <h4 class="font-medium mb-2">Database Statistics:</h4>
                        <div class="text-sm space-y-1">
                            <div><strong>Tables:</strong> <?= $db_stats['tables'] ?></div>
                            <div><strong>Size:</strong> <?= sqlite_connector_get_database_size($config) ?></div>
                            <?php if (!empty($db_stats['table_counts'])): ?>
                                <div class="mt-2">
                                    <strong>Table Row Counts:</strong>
                                    <ul class="ml-4 mt-1">
                                        <?php foreach ($db_stats['table_counts'] as $table => $count): ?>
                                            <li><?= htmlspecialchars($table) ?>: <?= $count ?> rows</li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                    <strong>✗ Not Connected</strong><br>
                    Unable to establish database connection.
                </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <h4 class="font-medium mb-2">Available PDO Drivers:</h4>
                <code class="bg-gray-100 px-2 py-1 rounded text-sm">
                    <?= implode(', ', PDO::getAvailableDrivers()) ?>
                </code>
            </div>
            
            <div class="mt-4">
                <h4 class="font-medium mb-2">Usage Example:</h4>
                <pre class="bg-gray-100 p-3 rounded text-sm overflow-x-auto"><code>// Get database connection
$pdo = fcms_do_hook('database_connect');

// Execute a query
$stmt = fcms_do_hook('database_query', 'SELECT * FROM users WHERE id = ?', [1]);</code></pre>
            </div>
        </div>
    </div>
    
    <?php
    return ob_get_clean();
}

// Initialize the plugin
sqlite_connector_init();
?> 