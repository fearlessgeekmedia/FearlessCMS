<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Enable error logging
error_log("Widget Handler: Script started");

function fcms_render_widget_manager() {
    $widgetsFile = ADMIN_CONFIG_DIR . '/widgets.json';
    $widgets = [];
    
    // Load widgets from file if it exists
    if (file_exists($widgetsFile)) {
        $jsonContent = file_get_contents($widgetsFile);
        if ($jsonContent !== false) {
            $widgets = json_decode($jsonContent, true) ?? [];
            error_log("Widget Handler: Loaded widgets: " . print_r($widgets, true));
        }
    }
    
    // Generate sidebar selection
    $sidebar_selection = '<select name="sidebar" id="sidebar-select" class="form-select">';
    $sidebar_selection .= '<option value="">Select a sidebar...</option>';
    
    if (!empty($widgets)) {
        foreach ($widgets as $id => $sidebar) {
            $selected = (!empty($_GET['sidebar']) && $_GET['sidebar'] === $id) ? 'selected' : '';
            $sidebar_selection .= sprintf(
                '<option value="%s" %s>%s</option>',
                htmlspecialchars($id),
                $selected,
                htmlspecialchars($sidebar['id'])
            );
        }
    }
    $sidebar_selection .= '</select>';
    
    // Generate widget list
    $widget_list = '';
    $current_sidebar = '';
    if (!empty($_GET['sidebar']) && isset($widgets[$_GET['sidebar']])) {
        $current_sidebar = $_GET['sidebar'];
        foreach ($widgets[$current_sidebar]['widgets'] as $widget) {
            $widget_list .= sprintf(
                '<div class="widget-item" data-id="%s" data-content="%s" data-classes="%s">
                    <div class="widget-header">
                        <h3 class="widget-title">%s</h3>
                        <div class="widget-drag-handle">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="widget-content">
                        <p class="text-sm text-gray-500">Type: %s</p>
                    </div>
                    <div class="widget-actions">
                        <button class="edit-widget" data-id="%s">Edit</button>
                        <button class="delete-widget" data-id="%s">Delete</button>
                    </div>
                </div>',
                htmlspecialchars($widget['id']),
                htmlspecialchars($widget['content'] ?? ''),
                htmlspecialchars($widget['classes'] ?? ''),
                htmlspecialchars($widget['title']),
                htmlspecialchars($widget['type']),
                htmlspecialchars($widget['id']),
                htmlspecialchars($widget['id'])
            );
        }
    }
    
    return [
        'sidebar_selection' => $sidebar_selection,
        'widget_list' => $widget_list,
        'current_sidebar' => $current_sidebar
    ];
}

// Only handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    error_log("Widget Handler: Processing AJAX request");
    error_log("Widget Handler: POST data: " . print_r($_POST, true));
    
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

    // Load existing widgets
    $widgetsFile = ADMIN_CONFIG_DIR . '/widgets.json';
    $widgets = [];
    if (file_exists($widgetsFile)) {
        $jsonContent = file_get_contents($widgetsFile);
        if ($jsonContent !== false) {
            $widgets = json_decode($jsonContent, true) ?? [];
        }
    }

    try {
        if (!isset($data['action'])) {
            throw new Exception('No action specified');
        }

        error_log("Widget Handler: Processing action: " . $data['action']);

        switch ($data['action']) {
            case 'add_sidebar':
                if (empty($data['id'])) {
                    throw new Exception('Sidebar ID is required');
                }

                // Format the ID (lowercase, replace spaces with hyphens)
                $id = strtolower(preg_replace('/[^a-z0-9-]/', '-', $data['id']));
                error_log("Widget Handler: Creating sidebar with ID: " . $id);
                
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
                error_log("Widget Handler: Created sidebar: " . print_r($widgets[$id], true));
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

                // Check if this is an update or new widget
                if (!empty($data['id']) && isset($widgets[$data['sidebar']]['widgets'][$data['id']])) {
                    // Update existing widget
                    error_log("Widget Handler: Updating existing widget: " . $data['id']);
                    $widgets[$data['sidebar']]['widgets'][$data['id']] = [
                        'id' => $data['id'],
                        'title' => $data['title'],
                        'type' => $data['type'],
                        'content' => $data['content'],
                        'classes' => $data['classes'] ?? ''
                    ];
                } else {
                    // Create new widget
                    $widgetId = 'widget-' . uniqid();
                    error_log("Widget Handler: Creating new widget: " . $widgetId);
                    $widgets[$data['sidebar']]['widgets'][$widgetId] = [
                        'id' => $widgetId,
                        'title' => $data['title'],
                        'type' => $data['type'],
                        'content' => $data['content'],
                        'classes' => $data['classes'] ?? ''
                    ];
                }
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

            case 'update_widget_order':
                if (empty($data['sidebar'])) {
                    throw new Exception('Sidebar ID is required');
                }
                if (!isset($widgets[$data['sidebar']])) {
                    throw new Exception('Sidebar not found');
                }
                if (empty($data['widgets'])) {
                    throw new Exception('Widget order data is required');
                }

                // Get the new order of widget IDs
                $widgetOrder = is_array($data['widgets']) ? $data['widgets'] : json_decode($data['widgets'], true);
                if (!is_array($widgetOrder)) {
                    throw new Exception('Invalid widget order data');
                }

                // Create a new array with widgets in the correct order
                $orderedWidgets = [];
                foreach ($widgetOrder as $widgetId) {
                    if (isset($widgets[$data['sidebar']]['widgets'][$widgetId])) {
                        // Keep all existing widget data
                        $orderedWidgets[$widgetId] = $widgets[$data['sidebar']]['widgets'][$widgetId];
                    }
                }

                // Replace the widgets array with the ordered one
                $widgets[$data['sidebar']]['widgets'] = $orderedWidgets;
                break;

            case 'save_sidebar':
                if (empty($data['sidebar'])) {
                    throw new Exception('Sidebar ID is required');
                }
                if (!isset($widgets[$data['sidebar']])) {
                    throw new Exception('Sidebar not found');
                }
                // The sidebar is already loaded and will be saved at the end
                error_log("Widget Handler: Saving sidebar: " . $data['sidebar']);
                break;

            default:
                throw new Exception('Invalid action');
        }

        // Save changes to admin config
        if (!is_dir(dirname($widgetsFile))) {
            mkdir(dirname($widgetsFile), 0755, true);
        }
        
        $jsonData = json_encode($widgets, JSON_PRETTY_PRINT);
        error_log("Widget Handler: Saving widgets to admin config: " . $jsonData);
        
        if (file_put_contents($widgetsFile, $jsonData) === false) {
            throw new Exception('Failed to save widgets file');
        }

        // Also save to public config for the frontend
        $publicWidgetsFile = CONFIG_DIR . '/widgets.json';
        if (!is_dir(dirname($publicWidgetsFile))) {
            mkdir(dirname($publicWidgetsFile), 0755, true);
        }
        
        // Convert to array format for the WidgetManager
        $publicWidgets = [];
        foreach ($widgets as $sidebarId => $sidebar) {
            $publicWidgets[$sidebarId] = [
                'id' => $sidebar['id'],
                'classes' => $sidebar['classes'],
                'widgets' => array_values($sidebar['widgets']) // Convert to array
            ];
        }
        
        $publicJsonData = json_encode($publicWidgets, JSON_PRETTY_PRINT);
        error_log("Widget Handler: Saving widgets to public config: " . $publicJsonData);
        
        if (file_put_contents($publicWidgetsFile, $publicJsonData) === false) {
            error_log("Widget Handler: Warning - Failed to save to public config, but admin config was saved");
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
    exit;
} 