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

require_once dirname(__DIR__) . '/includes/config.php';

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
    exit;
}

// Validate required fields
if (empty($data['content']) || empty($data['title'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Create preview directory if it doesn't exist
$previewDir = CONTENT_DIR . '/_preview';
if (!is_dir($previewDir)) {
    mkdir($previewDir, 0755, true);
}

// Generate a unique filename
$filename = uniqid('preview_') . '.md';
$previewFile = $previewDir . '/' . $filename;

// Create metadata
$metadata = [
    'title' => $data['title'],
            'template' => $data['template'] ?? 'page-with-sidebar'
];

// Format content with metadata
$contentWithMetadata = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->' . "\n\n" . $data['content'];

// Save the preview file
if (file_put_contents($previewFile, $contentWithMetadata)) {
    echo json_encode(['success' => true, 'path' => $filename]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save preview file']);
} 