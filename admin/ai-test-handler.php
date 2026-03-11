<?php
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
