<?php

/**
 * FearlessCMS
 *
 * Copyright (C) 2026 FearlessCMS
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * As a special exception to the GNU General Public License version 2
 * ("GPL"), the copyright holder(s) of FearlessCMS give you permission
 * to link, load, or combine this software with independent modules
 * ("Themes" and "Plugins") that are not derived from or based on this
 * software, regardless of the license terms of those Themes or
 * Plugins, including proprietary or commercial licenses, and to
 * convey the resulting combination under terms of your choice,
 * provided that you also convey the corresponding source code of the
 * FearlessCMS portions under the terms of the GPL.
 *
 * This exception does not affect any of your obligations under the
 * GPL with respect to the FearlessCMS core software itself, and it
 * does not extend to modifications of FearlessCMS core files — only
 * to independent Theme and Plugin modules that interact with
 * FearlessCMS through its documented Theme/Plugin API.
 */

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