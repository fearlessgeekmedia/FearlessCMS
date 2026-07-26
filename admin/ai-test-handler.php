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

/**
 * AI Test Handler for AJAX requests from the AI Connector admin page.
 */

// Define project root and includes
define('PROJECT_ROOT', dirname(__DIR__));
require_once PROJECT_ROOT . '/includes/config.php';
require_once PROJECT_ROOT . '/includes/auth.php';
require_once PROJECT_ROOT . '/includes/session.php';
require_once PROJECT_ROOT . '/includes/plugins.php';

// Ensure no output before JSON
if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Validate CSRF token
if (!validate_csrf_token()) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$prompt = $_POST['prompt'] ?? '';

if (empty($prompt)) {
    echo json_encode(['success' => false, 'message' => 'Prompt is required']);
    exit;
}

try {
    // The plugins are already loaded via includes/plugins.php
    // We can use the global function provided by the AI Connector plugin
    if (function_exists('fcms_ai_generate_content')) {
        $result = fcms_ai_generate_content($prompt);
        echo json_encode(['success' => true, 'result' => $result]);
    } else {
        echo json_encode(['success' => false, 'message' => 'AI Connector plugin is not active or properly loaded.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
