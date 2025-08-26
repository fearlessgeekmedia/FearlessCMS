<?php
// Debug script to test save and reload flow
require_once dirname(__DIR__) . '/includes/config.php';

echo "<h1>Save Flow Debug Script</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>POST Request Received</h2>";
    echo "<pre>POST data: " . print_r($_POST, true) . "</pre>";
    
    if (isset($_POST['action']) && $_POST['action'] === 'save_content') {
        $fileName = $_POST['path'] ?? '';
        $content = $_POST['content'] ?? '';
        
        echo "<h3>Processing Save Request</h3>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($fileName) . "</p>";
        echo "<p><strong>Content Length:</strong> " . strlen($content) . "</p>";
        
        $filePath = CONTENT_DIR . '/' . $fileName . '.md';
        echo "<p><strong>Full Path:</strong> " . htmlspecialchars($filePath) . "</p>";
        
        if (file_exists($filePath)) {
            echo "<p><strong>File exists:</strong> Yes</p>";
            echo "<p><strong>Current content length:</strong> " . strlen(file_get_contents($filePath)) . "</p>";
        } else {
            echo "<p><strong>File exists:</strong> No</p>";
        }
        
        // Simulate the save
        if (file_put_contents($filePath, $content) !== false) {
            echo "<p><strong>Save result:</strong> Success</p>";
            echo "<p><strong>New content length:</strong> " . strlen(file_get_contents($filePath)) . "</p>";
            
            // Test the redirect
            $redirectPath = str_replace('.md', '', $fileName);
            $timestamp = time();
            $redirectUrl = '?action=edit_content&path=' . urlencode($redirectPath) . '&saved=1&_t=' . $timestamp;
            
            echo "<h3>Redirect Test</h3>";
            echo "<p><strong>Redirect URL:</strong> " . htmlspecialchars($redirectUrl) . "</p>";
            echo "<p><a href='" . htmlspecialchars($redirectUrl) . "'>Click here to test redirect</a></p>";
            
        } else {
            echo "<p><strong>Save result:</strong> Failed</p>";
        }
    }
} else {
    echo "<h2>No POST Request</h2>";
    echo "<p>This script expects a POST request with save_content action.</p>";
    
    // Show current content files
    echo "<h3>Current Content Files</h3>";
    $contentFiles = glob(CONTENT_DIR . '/*.md');
    if ($contentFiles) {
        echo "<ul>";
        foreach ($contentFiles as $file) {
            $fileName = basename($file);
            $content = file_get_contents($file);
            echo "<li><strong>" . htmlspecialchars($fileName) . "</strong> - " . strlen($content) . " chars</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No content files found.</p>";
    }
}

echo "<hr>";
echo "<p><a href='index.php'>Back to Admin</a></p>";
?> 