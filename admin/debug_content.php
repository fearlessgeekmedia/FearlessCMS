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

// Debug script to check content loading
require_once dirname(__DIR__) . '/includes/config.php';

echo "<h1>Content Debug Script</h1>";

if (isset($_GET['path'])) {
    $path = $_GET['path'];
    $contentFile = CONTENT_DIR . '/' . $path;
    if (!str_ends_with($contentFile, '.md')) {
        $contentFile .= '.md';
    }
    
    echo "<h2>Debugging path: " . htmlspecialchars($path) . "</h2>";
    echo "<p><strong>Full file path:</strong> " . htmlspecialchars($contentFile) . "</p>";
    
    if (file_exists($contentFile)) {
        $fileModTime = filemtime($contentFile);
        $fileHash = md5_file($contentFile);
        $contentData = file_get_contents($contentFile);
        
        echo "<p><strong>File exists:</strong> Yes</p>";
        echo "<p><strong>File modification time:</strong> " . date('Y-m-d H:i:s', $fileModTime) . "</p>";
        echo "<p><strong>File hash:</strong> " . $fileHash . "</p>";
        echo "<p><strong>Content length:</strong> " . strlen($contentData) . " characters</p>";
        echo "<p><strong>Content preview:</strong></p>";
        echo "<pre>" . htmlspecialchars(substr($contentData, 0, 500)) . "</pre>";
        
        // Check for metadata
        if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $contentData, $matches)) {
            $metadata = json_decode($matches[1], true);
            echo "<p><strong>Metadata found:</strong></p>";
            echo "<pre>" . htmlspecialchars(print_r($metadata, true)) . "</pre>";
        } else {
            echo "<p><strong>No metadata found</strong></p>";
        }
        
        // Check file permissions
        echo "<p><strong>File permissions:</strong> " . substr(sprintf('%o', fileperms($contentFile)), -4) . "</p>";
        echo "<p><strong>File owner:</strong> " . posix_getpwuid(fileowner($contentFile))['name'] . "</p>";
        echo "<p><strong>File group:</strong> " . posix_getgrgid(filegroup($contentFile))['name'] . "</p>";
        
    } else {
        echo "<p><strong>File exists:</strong> No</p>";
        echo "<p><strong>Error:</strong> File not found</p>";
    }
    
    // Check directory contents
    $dir = dirname($contentFile);
    echo "<h3>Directory contents of: " . htmlspecialchars($dir) . "</h3>";
    if (is_dir($dir)) {
        $files = scandir($dir);
        echo "<ul>";
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $fullPath = $dir . '/' . $file;
                $isDir = is_dir($fullPath) ? ' (dir)' : '';
                $modTime = is_file($fullPath) ? ' - ' . date('Y-m-d H:i:s', filemtime($fullPath)) : '';
                echo "<li>" . htmlspecialchars($file) . $isDir . $modTime . "</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p>Directory not found</p>";
    }
    
} else {
    echo "<p>No path specified. Use ?path=filename to debug a specific file.</p>";
    echo "<p>Example: <a href='?path=home'>?path=home</a></p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Back to Admin</a></p>";
?> 