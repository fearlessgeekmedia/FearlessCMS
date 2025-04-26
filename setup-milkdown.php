<?php
// setup-milkdown.php - Script to download and set up Milkdown files

// Configuration
$adminDir = __DIR__ . '/admin';
$assetsDir = $adminDir . '/assets/js/milkdown';

// Create directories if they don't exist
if (!file_exists($assetsDir)) {
    mkdir($assetsDir, 0755, true);
    echo "Created directory: $assetsDir\n";
}

// Milkdown files to download
$milkdownFiles = [
    'core' => 'https://cdn.jsdelivr.net/npm/@milkdown/core@7.3.5/dist/index.min.js',
    'preset-commonmark' => 'https://cdn.jsdelivr.net/npm/@milkdown/preset-commonmark@7.3.5/dist/index.min.js',
    'theme-nord' => 'https://cdn.jsdelivr.net/npm/@milkdown/theme-nord@7.3.5/dist/index.min.js',
    'plugin-menu' => 'https://cdn.jsdelivr.net/npm/@milkdown/plugin-menu@7.3.5/dist/index.min.js',
    'theme-nord-css' => 'https://cdn.jsdelivr.net/npm/@milkdown/theme-nord@7.3.5/style.css'
];

// Download files
foreach ($milkdownFiles as $name => $url) {
    $extension = strpos($name, 'css') !== false ? '.css' : '.js';
    $filename = str_replace('-css', '', $name) . $extension;
    $filePath = $assetsDir . '/' . $filename;
    
    echo "Downloading $name from $url...\n";
    $content = file_get_contents($url);
    
    if ($content === false) {
        echo "Error downloading $name\n";
        continue;
    }
    
    // For JS files, wrap the content in a module loader that exposes it to window
    if ($extension === '.js') {
        // Extract module name from URL
        preg_match('/\@milkdown\/([^@]+)@/', $url, $matches);
        $moduleName = isset($matches[1]) ? $matches[1] : $name;
        $moduleName = str_replace('-', '', $moduleName);
        
        $content = <<<JS
(function(global) {
    // UMD loader
    const factory = function() {
        const exports = {};
        // Original minified code
        $content
        
        // Expose module
        if (!global.Milkdown) global.Milkdown = {};
        global.Milkdown.$moduleName = exports;
        return exports;
    };
    
    factory();
})(typeof window !== 'undefined' ? window : this);
JS;
    }
    
    // Save file
    if (file_put_contents($filePath, $content) !== false) {
        echo "Successfully saved $filePath\n";
    } else {
        echo "Error saving $filePath\n";
    }
}

echo "\nSetup complete!\n";
echo "Please update your dashboard.html to use the local Milkdown files.\n";
?>
