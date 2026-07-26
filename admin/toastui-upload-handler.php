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

// Session should already be started by session.php
require_once dirname(__DIR__) . '/includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Check CMS mode restrictions
require_once dirname(__DIR__) . '/includes/CMSModeManager.php';
$cmsModeManager = new CMSModeManager();

if (!$cmsModeManager->canUploadContentImages()) {
    echo json_encode(['success' => false, 'error' => 'Image uploads are disabled in the current CMS mode']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadDir = dirname(__DIR__) . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $file = $_FILES['file'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB limit for editor uploads

    // Comprehensive file validation
    $originalName = $file['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $mimeType = $file['type'];
    $tmpName = $file['tmp_name'];

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Upload error occurred']);
        exit;
    }

    // Validate file extension
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowed)]);
        exit;
    }

    // Validate file size
    if ($file['size'] > $maxFileSize) {
        echo json_encode(['success' => false, 'error' => 'File too large. Maximum: 5MB']);
        exit;
    }

    // Validate MIME type for additional security
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mimeType, $allowedMimes)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type detected']);
        exit;
    }

    // Check for executable content in filename
    if (preg_match('/\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$/i', $originalName)) {
        echo json_encode(['success' => false, 'error' => 'Executable files are not allowed']);
        exit;
    }

    // Additional security: validate the actual file content
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $tmpName);
        finfo_close($finfo);

        if (!in_array($detectedMime, $allowedMimes)) {
            echo json_encode(['success' => false, 'error' => 'File content does not match allowed image types']);
            exit;
        }
    }

    // Sanitize filename and add timestamp
    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
    $filename = 'toastui_' . $safeName . '_' . time() . '.' . $ext;
    $target = $uploadDir . $filename;

    if (move_uploaded_file($tmpName, $target)) {
        // Set secure file permissions
        chmod($target, 0644);
        $url = '/uploads/' . $filename;
        echo json_encode(['success' => true, 'url' => $url]);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file']);
        exit;
    }
}
echo json_encode(['success' => false, 'error' => 'Upload failed']);
