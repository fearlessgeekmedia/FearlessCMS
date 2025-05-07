<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Function to render the widget manager interface
function fcms_render_widget_manager() {
    // Load existing widgets
        $widgetsFile = ADMIN_CONFIG_DIR . '/widgets.json';
        $widgets = file_exists($widgetsFile) ? json_decode(file_get_contents($widgetsFile), true) : [];
        
    // Get current sidebar from URL or default to first sidebar
    $currentSidebar = $_GET['sidebar'] ?? array_key_first($widgets) ?? '';
    
    // Build sidebar selection dropdown
    $sidebarSelection = '<select name="sidebar" onchange="window.location.href=\'?action=manage_widgets&sidebar=\' + this.value" class="border rounded px-3 py-2">';
    foreach ($widgets as $id => $sidebar) {
        $selected = ($id === $currentSidebar) ? ' selected' : '';
        $sidebarSelection .= '<option value="' . htmlspecialchars($id) . '"' . $selected . '>' . 
                           htmlspecialchars($sidebar['name']) . '</option>';
    }
    $sidebarSelection .= '</select>';
    
    // Build widget list for current sidebar
    $widgetList = '';
    if ($currentSidebar && isset($widgets[$currentSidebar])) {
        $widgetList .= '<div class="widget-list" data-sidebar="' . htmlspecialchars($currentSidebar) . '">';
        foreach ($widgets[$currentSidebar]['widgets'] as $id => $widget) {
            $widgetList .= '<div class="widget-item bg-white p-4 rounded shadow mb-4" data-widget-id="' . htmlspecialchars($id) . '">';
            $widgetList .= '<div class="flex justify-between items-center mb-2">';
            $widgetList .= '<h3 class="text-lg font-semibold">' . htmlspecialchars($widget['title']) . '</h3>';
            $widgetList .= '<div class="flex gap-2">';
            $widgetList .= '<button onclick="editWidget(\'' . htmlspecialchars($id) . '\')" class="bg-blue-500 text-white px-3 py-1 rounded">Edit</button>';
            $widgetList .= '<button onclick="deleteWidget(\'' . htmlspecialchars($id) . '\')" class="bg-red-500 text-white px-3 py-1 rounded">Delete</button>';
            $widgetList .= '</div></div>';
            $widgetList .= '<div class="widget-content">' . htmlspecialchars($widget['content']) . '</div>';
            $widgetList .= '</div>';
        }
        $widgetList .= '</div>';
    }
    
    return [
        'sidebar_selection' => $sidebarSelection,
        'widget_list' => $widgetList,
        'current_sidebar' => $currentSidebar
    ];
}

// Only set JSON header and clear output for AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    ob_clean();
    header('Content-Type: application/json');
}

// Ensure user is logged in
    if (!isLoggedIn()) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['error' => 'Unauthorized access']);
    }
    exit;
}

// Get input data
$input = file_get_contents('php://input');
$data = json_decode($input, true) ?: $_POST;

// Get action from either URL or request body
$action = $_GET['action'] ?? $data['action'] ?? '';

// Log the incoming request for debugging
error_log('Widget Handler Request: ' . print_r([
    'GET' => $_GET,
    'POST' => $_POST,
    'input' => $input,
    'data' => $data,
    'action' => $action
], true));

// Guard against unintended calls
if (empty($action)) {
    // Only respond with JSON error if this is an AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['error' => 'No action specified']);
    }
    return; // Use return instead of exit for non-AJAX requests
}

// Ensure this is a widget-related action
$validActions = [
    'add_sidebar', 'create_sidebar', 'delete_sidebar',
    'save_widget', 'add_widget', 'delete_widget',
    'reorder_widgets'
];

if (!in_array($action, $validActions)) {
    // Only respond with JSON error if this is an AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['error' => 'Invalid action: ' . $action]);
    }
    return; // Use return instead of exit for non-AJAX requests
}

// Load existing widgets
        $widgetsFile = ADMIN_CONFIG_DIR . '/widgets.json';
        $widgets = file_exists($widgetsFile) ? json_decode(file_get_contents($widgetsFile), true) : [];
        
// Handle different actions
switch ($action) {
    case 'add_sidebar':
    case 'create_sidebar':  // Add support for create_sidebar action
        if (empty($data['sidebar_id'])) {
            echo json_encode(['error' => 'Sidebar ID is required']);
            exit;
        }
        
        // Format the ID (lowercase, replace spaces with hyphens)
        $id = strtolower(preg_replace('/[^a-z0-9-]/', '-', $data['sidebar_id']));
        
        // Ensure the ID is unique
        $baseId = $id;
        $counter = 1;
        while (isset($widgets[$id])) {
            $id = $baseId . '-' . $counter;
            $counter++;
        }

        $widgets[$id] = [
            'id' => $id,
            'name' => $data['sidebar_name'] ?? ucwords(str_replace('-', ' ', $id)),
            'classes' => $data['classes'] ?? 'sidebar-' . $id,
                    'widgets' => []
                ];
            break;

    case 'delete_sidebar':
        if (empty($data['sidebar_id'])) {
            echo json_encode(['error' => 'Sidebar ID is required']);
            exit;
        }
        if (!isset($widgets[$data['sidebar_id']])) {
            echo json_encode(['error' => 'Sidebar not found']);
            exit;
        }
        unset($widgets[$data['sidebar_id']]);
            break;

    case 'save_widget':
    case 'add_widget':  // Add support for add_widget action
        if (empty($data['sidebar_id'])) {
            echo json_encode(['error' => 'Sidebar ID is required']);
            exit;
        }
        if (!isset($widgets[$data['sidebar_id']])) {
            echo json_encode(['error' => 'Sidebar not found']);
            exit;
        }

        // Generate widget ID if not provided
        $widgetId = $data['widget_id'] ?? 'widget-' . uniqid();
        
        $widgets[$data['sidebar_id']]['widgets'][$widgetId] = [
            'id' => $widgetId,
            'title' => $data['widget_title'] ?? $data['title'] ?? '',
            'type' => $data['widget_type'] ?? $data['type'] ?? 'text',
            'content' => $data['widget_content'] ?? $data['content'] ?? '',
            'classes' => $data['classes'] ?? ''
        ];
            break;

        case 'delete_widget':
        if (empty($data['sidebar_id']) || empty($data['widget_id'])) {
            echo json_encode(['error' => 'Sidebar ID and Widget ID are required']);
            exit;
        }
        if (!isset($widgets[$data['sidebar_id']])) {
            echo json_encode(['error' => 'Sidebar not found']);
            exit;
        }
        if (!isset($widgets[$data['sidebar_id']]['widgets'][$data['widget_id']])) {
            echo json_encode(['error' => 'Widget not found']);
            exit;
        }
        unset($widgets[$data['sidebar_id']]['widgets'][$data['widget_id']]);
            break;

        case 'reorder_widgets':
        if (empty($data['sidebar_id']) || empty($data['widget_order'])) {
            echo json_encode(['error' => 'Sidebar ID and widget order are required']);
            exit;
        }
        if (!isset($widgets[$data['sidebar_id']])) {
            echo json_encode(['error' => 'Sidebar not found']);
            exit;
        }
        $newOrder = json_decode($data['widget_order'], true);
        if (!is_array($newOrder)) {
            echo json_encode(['error' => 'Invalid widget order']);
            exit;
        }
                $reorderedWidgets = [];
                foreach ($newOrder as $widgetId) {
            if (isset($widgets[$data['sidebar_id']]['widgets'][$widgetId])) {
                $reorderedWidgets[$widgetId] = $widgets[$data['sidebar_id']]['widgets'][$widgetId];
            }
        }
        $widgets[$data['sidebar_id']]['widgets'] = $reorderedWidgets;
            break;
        
    default:
        error_log('Invalid widget action: ' . $action);
        echo json_encode(['error' => 'Invalid action: ' . $action]);
        exit;
}

// Save changes
if (!is_dir(dirname($widgetsFile))) {
    mkdir(dirname($widgetsFile), 0755, true);
}

if (file_put_contents($widgetsFile, json_encode($widgets, JSON_PRETTY_PRINT)) === false) {
    echo json_encode(['error' => 'Failed to save widgets file']);
    exit;
}

echo json_encode(['success' => true]);
?>

