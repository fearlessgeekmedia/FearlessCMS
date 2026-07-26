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

// Simple test version to isolate 500 error
error_log("DEBUG: Quill upload handler started");
error_log("DEBUG: Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("DEBUG: POST data: " . print_r($_POST, true));
error_log("DEBUG: FILES data: " . print_r($_FILES, true));

// Ensure no output before headers
if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');

// Basic error handling
try {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if user is logged in (simplified)
    if (!isset($_SESSION['username'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_FILES['image']) || isset($_FILES['file']))) {
        $uploadDir = dirname(__DIR__) . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file = $_FILES['image'] ?? $_FILES['file'];
        
        // Basic validation
        $prefix = isset($_FILES['image']) ? 'quill_' : 'toastui_';
        $originalName = $file['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type']);
            exit;
        }

        // Sanitize filename
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $filename = $prefix . $safeName . '_' . time() . '.' . $ext;
        $target = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            chmod($target, 0644);
            $url = '/uploads/' . $filename;
            echo json_encode(['success' => true, 'url' => $url]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Upload failed']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No image file received']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Fatal error: ' . $e->getMessage()]);
}
?> 