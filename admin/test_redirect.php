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