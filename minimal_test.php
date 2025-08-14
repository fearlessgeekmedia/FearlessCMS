<?php
echo "Minimal test started\n";

try {
    echo "About to include session.php\n";
    require_once __DIR__ . '/includes/session.php';
    echo "Session included successfully\n";
} catch (Throwable $e) {
    echo "Session error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

try {
    echo "About to include config.php\n";
    require_once __DIR__ . '/includes/config.php';
    echo "Config included successfully\n";
} catch (Throwable $e) {
    echo "Config error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "Test complete\n";
?>
