<?php
// Set appropriate error reporting for installation
if (getenv('FCMS_DEBUG') === 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
}
ini_set('log_errors', 1);

// Skip session for now - just use a simple token approach
if (function_exists('random_bytes')) {
    $csrf_token = bin2hex(random_bytes(32));
} elseif (function_exists('openssl_random_pseudo_bytes')) {
    $csrf_token = bin2hex(openssl_random_pseudo_bytes(32));
} else {
    $csrf_token = bin2hex(md5(uniqid(mt_rand(), true)));
}

// Parse command line arguments
$options = getopt('', ['check', 'create-dirs', 'install-export-deps']);

if (isset($options['check'])) {
    checkEnvironment();
    exit(0);
}

if (isset($options['create-dirs'])) {
    createDirectories();
    exit(0);
}

if (isset($options['install-export-deps'])) {
    installExportDependencies();
    exit(0);
}

// If no specific command, show help
showHelp();

function checkEnvironment() {
    echo "🔍 Checking FearlessCMS Environment\n";
    echo "===================================\n\n";

    $checks = [];

    // PHP version check
    $phpVersion = phpversion();
    $checks['PHP Version'] = [
        'status' => version_compare($phpVersion, '8.1.0', '>='),
        'message' => "PHP $phpVersion " . (version_compare($phpVersion, '8.1.0', '>=') ? '✓' : '✗ (requires 8.1+)')
    ];

    // Directory permissions
    $dirs = ['cache', 'content', 'sessions', 'uploads', 'config'];
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $checks["Directory: $dir"] = [
            'status' => is_writable($dir),
            'message' => is_writable($dir) ? '✓ Writable' : '✗ Not writable'
        ];
    }

    // Required functions
    $functions = ['hash', 'random_bytes', 'json_encode', 'file_get_contents'];
    foreach ($functions as $func) {
        $checks["Function: $func"] = [
            'status' => function_exists($func),
            'message' => function_exists($func) ? '✓ Available' : '✗ Missing'
        ];
    }

    // Session support (optional now)
    $checks['Session Support'] = [
        'status' => function_exists('session_start'),
        'message' => function_exists('session_start') ? '✓ Available' : '⚠ Not available (using alternative)'
    ];

    // Display results
    $allPassed = true;
    foreach ($checks as $name => $check) {
        echo sprintf("%-20s: %s\n", $name, $check['message']);
        if (!$check['status'] && $name !== 'Session Support') {
            $allPassed = false;
        }
    }

    echo "\n";
    if ($allPassed) {
        echo "🎉 Environment check passed! FearlessCMS is ready to use.\n";
    } else {
        echo "❌ Environment check failed. Please fix the issues above.\n";
        exit(1);
    }
}

function createDirectories() {
    echo "📁 Creating FearlessCMS directories\n";
    echo "==================================\n\n";

    $dirs = [
        'cache' => 'Cache files',
        'content' => 'Content files',
        'sessions' => 'Session storage',
        'uploads' => 'Uploaded files',
        'config' => 'Configuration files',
        'themes/default' => 'Default theme',
        'plugins' => 'Plugin files'
    ];

    foreach ($dirs as $dir => $description) {
        if (!file_exists($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "✓ Created: $dir ($description)\n";
            } else {
                echo "✗ Failed to create: $dir\n";
            }
        } else {
            echo "• Exists: $dir ($description)\n";
        }
    }

    echo "\n✅ Directory creation complete!\n";
}

function installExportDependencies() {
    echo "📦 Installing export dependencies\n";
    echo "================================\n\n";

    if (!file_exists('package.json')) {
        echo "✗ package.json not found\n";
        exit(1);
    }

    echo "Running npm install...\n";
    $output = [];
    $returnCode = 0;
    exec('npm install 2>&1', $output, $returnCode);

    if ($returnCode === 0) {
        echo "✅ NPM dependencies installed successfully!\n";
    } else {
        echo "❌ Failed to install NPM dependencies:\n";
        foreach ($output as $line) {
            echo "  $line\n";
        }
        exit(1);
    }
}

function showHelp() {
    echo "🐺 FearlessCMS Installation Helper\n";
    echo "=================================\n\n";
    echo "Usage: php install-no-session.php [OPTION]\n\n";
    echo "Options:\n";
    echo "  --check                Check environment requirements\n";
    echo "  --create-dirs         Create necessary directories\n";
    echo "  --install-export-deps Install Node.js dependencies for export\n";
    echo "\n";
    echo "Examples:\n";
    echo "  php install-no-session.php --check\n";
    echo "  php install-no-session.php --create-dirs\n";
    echo "  php install-no-session.php --install-export-deps\n";
    echo "\n";
    echo "Note: This version works without PHP session extension.\n";
}
?>
