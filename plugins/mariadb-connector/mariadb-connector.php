<?php
/*
Plugin Name: MariaDB Connector
Description: Provides MariaDB database connectivity for FearlessCMS plugins
Version: 0.0.3
Author: Fearless Geek
*/

// Define constants
define('MARIADB_PLUGIN_DIR', PLUGIN_DIR . '/mariadb-connector');
define('MARIADB_CONFIG_DIR', CONTENT_DIR . '/mariadb-connector');
define('MARIADB_CONFIG_FILE', MARIADB_CONFIG_DIR . '/config.json');

// Initialize plugin
function mariadb_connector_init() {
    // Create necessary directories
    if (!file_exists(MARIADB_CONFIG_DIR)) {
        mkdir(MARIADB_CONFIG_DIR, 0755, true);
    }
    
    // Create default config if it doesn't exist
    if (!file_exists(MARIADB_CONFIG_FILE)) {
        $default_config = [
            'host' => 'localhost',
            'database' => '',
            'username' => '',
            'password' => '',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        ];
        file_put_contents(MARIADB_CONFIG_FILE, json_encode($default_config, JSON_PRETTY_PRINT));
    }

    // Register admin section
    fcms_register_admin_section('mariadb_connector', [
        'label' => 'MariaDB Connector',
        'menu_order' => 35,
        'parent' => 'manage_plugins',
        'render_callback' => 'mariadb_connector_admin_page'
    ]);

    // Register hooks for other plugins to use
    fcms_add_hook('database_connect', 'mariadb_connector_get_connection');
    fcms_add_hook('database_query', 'mariadb_connector_execute_query');
}

// Get database connection
function mariadb_connector_get_connection() {
    static $pdo = null;
    
    if ($pdo === null) {
        $config = mariadb_connector_get_config();
        
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
            $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            error_log("MariaDB Connector: Database connection established");
        } catch (PDOException $e) {
            error_log("MariaDB Connector: Connection failed - " . $e->getMessage());
            return null;
        }
    }
    
    return $pdo;
}

// Execute a database query
function mariadb_connector_execute_query($query, $params = []) {
    $pdo = mariadb_connector_get_connection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("MariaDB Connector: Query failed - " . $e->getMessage());
        return false;
    }
}

// Get plugin configuration
function mariadb_connector_get_config() {
    if (file_exists(MARIADB_CONFIG_FILE)) {
        return json_decode(file_get_contents(MARIADB_CONFIG_FILE), true);
    }
    return [];
}

// Save plugin configuration
function mariadb_connector_save_config($config) {
    return file_put_contents(MARIADB_CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT));
}

// Test database connection
function mariadb_connector_test_connection($config) {
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        
        // Test with a simple query
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        
        return $result && $result['test'] == 1;
    } catch (PDOException $e) {
        return false;
    }
}

// Admin page callback
function mariadb_connector_admin_page() {
    $success_message = '';
    $error_message = '';
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_config':
                $config = [
                    'host' => trim($_POST['host'] ?? 'localhost'),
                    'database' => trim($_POST['database'] ?? ''),
                    'username' => trim($_POST['username'] ?? ''),
                    'password' => $_POST['password'] ?? '',
                    'charset' => trim($_POST['charset'] ?? 'utf8mb4'),
                    'options' => [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                ];
                
                if (mariadb_connector_save_config($config)) {
                    $success_message = 'Configuration saved successfully!';
                } else {
                    $error_message = 'Failed to save configuration.';
                }
                break;
                
            case 'test_connection':
                $config = mariadb_connector_get_config();
                if (mariadb_connector_test_connection($config)) {
                    $success_message = 'Database connection test successful!';
                } else {
                    $error_message = 'Database connection test failed. Please check your settings.';
                }
                break;
        }
    }
    
    // Load current configuration
    $config = mariadb_connector_get_config();
    
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
    
    <h2 class="text-2xl font-bold mb-6">MariaDB Connector Settings</h2>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Configuration Form -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Database Configuration</h3>
            
            <form method="POST" class="space-y-4">
                <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                <input type="hidden" name="action" value="save_config">
                
                <div>
                    <label class="block font-medium mb-1">Host</label>
                    <input type="text" name="host" 
                           value="<?= htmlspecialchars($config['host'] ?? 'localhost') ?>" 
                           class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block font-medium mb-1">Database</label>
                    <input type="text" name="database" 
                           value="<?= htmlspecialchars($config['database'] ?? '') ?>" 
                           class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block font-medium mb-1">Username</label>
                    <input type="text" name="username" 
                           value="<?= htmlspecialchars($config['username'] ?? '') ?>" 
                           class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block font-medium mb-1">Password</label>
                    <input type="password" name="password" 
                           value="<?= htmlspecialchars($config['password'] ?? '') ?>" 
                           class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block font-medium mb-1">Charset</label>
                    <input type="text" name="charset" 
                           value="<?= htmlspecialchars($config['charset'] ?? 'utf8mb4') ?>" 
                           class="w-full border rounded px-3 py-2">
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
            $pdo = mariadb_connector_get_connection();
            if ($pdo): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded mb-4">
                    <strong>✓ Connected</strong><br>
                    Database connection is active and working.
                </div>
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
mariadb_connector_init();
?>