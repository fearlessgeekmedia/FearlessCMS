<?php
// Debug the MariaDB Connector Plugin
echo "Debugging MariaDB Connector Plugin...\n";

// Include the necessary FearlessCMS files
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/plugins.php';

// Check if the plugin directory exists
echo "Plugin directory: " . PLUGIN_DIR . "/mariadb-connector\n";
echo "Plugin exists: " . (is_dir(PLUGIN_DIR . '/mariadb-connector') ? 'Yes' : 'No') . "\n";

// Check if the plugin file exists
echo "Plugin file: " . PLUGIN_DIR . "/mariadb-connector/mariadb-connector.php\n";
echo "Plugin file exists: " . (file_exists(PLUGIN_DIR . '/mariadb-connector/mariadb-connector.php') ? 'Yes' : 'No') . "\n";

// Check active plugins
echo "Active plugins: " . file_get_contents(CONFIG_DIR . '/active_plugins.json') . "\n";

// Check if the plugin functions are available
echo "Function mariadb_connector_init exists: " . (function_exists('mariadb_connector_init') ? 'Yes' : 'No') . "\n";
echo "Function mariadb_connector_get_connection exists: " . (function_exists('mariadb_connector_get_connection') ? 'Yes' : 'No') . "\n";

// Try to call the function directly
if (function_exists('mariadb_connector_get_connection')) {
    echo "Testing direct function call...\n";
    $pdo = mariadb_connector_get_connection();
    echo "Direct call result: " . ($pdo ? 'Success' : 'Failed') . "\n";
}

echo "Debug completed!\n";
?> 