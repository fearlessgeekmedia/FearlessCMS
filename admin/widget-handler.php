<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Clear any previous output and set JSON header
ob_clean();
header('Content-Type: application/json');

// Ensure user is logged in
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Get input data (either from POST or JSON)
$data = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        // Handle JSON input
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
            exit;
        }
    } else {
        // Handle form data
        $data = $_POST;
    }
}

// Load existing widgets
$widgetsFile = ADMIN_CONFIG_DIR . '/widgets.json';
$widgets = [];
if (file_exists($widgetsFile)) {
    $widgets = json_decode(file_get_contents($widgetsFile), true) ?? [];
}

try {
    if (!isset($data['action'])) {
        throw new Exception('No action specified');
    }

    switch ($data['action']) {
        case 'add_sidebar':
            if (empty($data['id'])) {
                throw new Exception('Sidebar ID is required');
            }

            // Format the ID (lowercase, replace spaces with hyphens)
            $id = strtolower(preg_replace('/[^a-z0-9-]/', '-', $data['id']));
            
            // Ensure the ID is unique
            $baseId = $id;
            $counter = 1;
            while (isset($widgets[$id])) {
                $id = $baseId . '-' . $counter;
                $counter++;
            }

            $widgets[$id] = [
                'id' => $id,
                'classes' => $data['classes'] ?? 'sidebar-' . $id,
                'widgets' => []
            ];
            break;

        case 'delete_sidebar':
            if (empty($data['id'])) {
                throw new Exception('Sidebar ID is required');
            }
            if (!isset($widgets[$data['id']])) {
                throw new Exception('Sidebar not found');
            }
            unset($widgets[$data['id']]);
            break;

        case 'save_widget':
            if (empty($data['sidebar'])) {
                throw new Exception('Sidebar ID is required');
            }
            if (!isset($widgets[$data['sidebar']])) {
                throw new Exception('Sidebar not found');
            }

            // Generate widget ID if not provided
            $widgetId = $data['id'] ?? 'widget-' . uniqid();
            
            $widgets[$data['sidebar']]['widgets'][$widgetId] = [
                'id' => $widgetId,
                'title' => $data['title'],
                'type' => $data['type'],
                'content' => $data['content'],
                'classes' => $data['classes'] ?? ''
            ];
            break;

        case 'delete_widget':
            if (empty($data['sidebar']) || empty($data['id'])) {
                throw new Exception('Sidebar ID and Widget ID are required');
            }
            if (!isset($widgets[$data['sidebar']])) {
                throw new Exception('Sidebar not found');
            }
            if (!isset($widgets[$data['sidebar']]['widgets'][$data['id']])) {
                throw new Exception('Widget not found');
            }
            unset($widgets[$data['sidebar']]['widgets'][$data['id']]);
            break;

        default:
            throw new Exception('Invalid action');
    }

    // Save changes
    if (!is_dir(dirname($widgetsFile))) {
        mkdir(dirname($widgetsFile), 0755, true);
    }
    
    if (file_put_contents($widgetsFile, json_encode($widgets, JSON_PRETTY_PRINT)) === false) {
        throw new Exception('Failed to save widgets file');
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log('Widget Handler Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 