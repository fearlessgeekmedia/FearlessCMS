<?php
// Debug plugin loading
echo "Debugging plugin loading...\n";

// Include the necessary FearlessCMS files
require_once __DIR__ . '/includes/config.php';

echo "PLUGINS_DIR: " . PLUGINS_DIR . "\n";
echo "ADMIN_CONFIG_DIR: " . ADMIN_CONFIG_DIR . "\n";

// Check if config file exists
$configFile = ADMIN_CONFIG_DIR . '/plugins.json';
if (file_exists($configFile)) {
    $active = json_decode(file_get_contents($configFile), true);
    echo "Active plugins: " . print_r($active, true) . "\n";
} else {
    echo "Plugin config file not found at: $configFile\n";
}

// Check plugin directory
if (is_dir(PLUGINS_DIR)) {
    echo "Plugin directory exists\n";
    $plugins = glob(PLUGINS_DIR . '/*', GLOB_ONLYDIR);
    echo "Found plugin directories: " . print_r($plugins, true) . "\n";
    
    foreach ($plugins as $pluginFolder) {
        $pluginName = basename($pluginFolder);
        echo "Checking plugin: $pluginName\n";
        
        if (in_array($pluginName, $active)) {
            echo "  - Plugin is active\n";
            $mainFile = $pluginFolder . '/' . $pluginName . '.php';
            echo "  - Looking for main file: $mainFile\n";
            
            if (file_exists($mainFile)) {
                echo "  - Main file exists\n";
                echo "  - Including file...\n";
                include_once $mainFile;
                echo "  - File included\n";
            } else {
                echo "  - Main file not found!\n";
            }
        } else {
            echo "  - Plugin is not active\n";
        }
    }
} else {
    echo "Plugin directory not found!\n";
}

echo "Debug completed!\n";
?> 