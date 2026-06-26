<?php
// Simple test script to verify redirect logic
echo "<h1>Redirect Test</h1>";

if (isset($_GET['test'])) {
    echo "<p>Test parameter received: " . htmlspecialchars($_GET['test']) . "</p>";
    
    if ($_GET['test'] === 'redirect') {
        echo "<p>Testing redirect...</p>";
        $timestamp = time();
        $redirectUrl = '?test=redirected&_t=' . $timestamp . '&saved=1';
        
        echo "<p>Redirecting to: " . htmlspecialchars($redirectUrl) . "</p>";
        
        if (!headers_sent()) {
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            echo "<p>Headers already sent, using JavaScript redirect</p>";
            echo "<script>window.location.href = '" . htmlspecialchars($redirectUrl) . "';</script>";
        }
    } elseif ($_GET['test'] === 'redirected') {
        echo "<p>Redirect successful!</p>";
        echo "<p>Timestamp: " . htmlspecialchars($_GET['_t'] ?? 'NOT SET') . "</p>";
        echo "<p>Saved: " . htmlspecialchars($_GET['saved'] ?? 'NOT SET') . "</p>";
    }
} else {
    echo "<p>No test parameter. Use ?test=redirect to test redirect logic.</p>";
    echo "<p><a href='?test=redirect'>Test Redirect</a></p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Back to Admin</a></p>";
?> 