<?php
// Debug logging for all POST requests to this handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Widgets handler - POST request received");
    error_log("Widgets handler - POST data: " . print_r($_POST, true));
    error_log("Widgets handler - REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
    error_log("Widgets handler - Action: " . ($_POST['action'] ?? 'none'));
}

// Add this after the widgets-handler.php require

// Helper function to return JSON response
function returnJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Handle sidebar deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_sidebar') {
    if (!isLoggedIn()) {
        returnJsonResponse(['success' => false, 'error' => 'You must be logged in to manage sidebars']);
    } else {
        $sidebarId = $_POST['id'] ?? '';
        $widgetsFile = ADMIN_CONFIG_DIR . '/widgets.json';
        $widgets = file_exists($widgetsFile) ? json_decode(file_get_contents($widgetsFile), true) : [];
        
        if (isset($widgets[$sidebarId])) {
            unset($widgets[$sidebarId]);
            file_put_contents($widgetsFile, json_encode($widgets, JSON_PRETTY_PRINT));
            returnJsonResponse(['success' => true, 'message' => "Sidebar '$sidebarId' deleted successfully"]);
        } else {
            returnJsonResponse(['success' => false, 'error' => 'Sidebar not found']);
        }
    }
}

// Handle widget deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_widget') {
    if (!isLoggedIn()) {
        returnJsonResponse(['success' => false, 'error' => 'You must be logged in to manage widgets']);
    } else {
        $sidebarId = $_POST['sidebar'] ?? '';
        $widgetId = $_POST['id'] ?? '';
        $widgetsFile = ADMIN_CONFIG_DIR . '/widgets.json';
        $widgets = file_exists($widgetsFile) ? json_decode(file_get_contents($widgetsFile), true) : [];
        
        error_log("Standalone delete_widget - Sidebar: " . $sidebarId . ", Widget: " . $widgetId);
        
        if (isset($widgets[$sidebarId])) {
            // Ensure widgets is always an array
            if (!is_array($widgets[$sidebarId]['widgets'])) {
                $widgets[$sidebarId]['widgets'] = [];
            }
            
            // Filter out the widget to delete
            $widgets[$sidebarId]['widgets'] = array_filter(
                $widgets[$sidebarId]['widgets'],
                function($widget) use ($widgetId) {
                    return is_array($widget) && isset($widget['id']) && $widget['id'] !== $widgetId;
                }
            );
            
            // Re-index the array to ensure sequential keys
            $widgets[$sidebarId]['widgets'] = array_values($widgets[$sidebarId]['widgets']);
            
            file_put_contents($widgetsFile, json_encode($widgets, JSON_PRETTY_PRINT));
            error_log("Standalone delete_widget - Widget deleted successfully");
            returnJsonResponse(['success' => true, 'message' => 'Widget deleted successfully']);
        } else {
            error_log("Standalone delete_widget - Sidebar not found: " . $sidebarId);
            returnJsonResponse(['success' => false, 'error' => 'Sidebar not found']);
        }
    }
}

// Handle widget operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Only handle widget-related actions
    $widgetActions = ['add_sidebar', 'add_widget', 'save_widget', 'update_widget', 'delete_widget', 'reorder_widgets', 'delete_sidebar'];
    
    if (!in_array($_POST['action'], $widgetActions)) {
        // Not a widget action, let other handlers deal with it
        return;
    }
    
    // Debug logging
    error_log("Widgets handler - POST data: " . print_r($_POST, true));
    
    if (!isLoggedIn()) {
        returnJsonResponse(['success' => false, 'error' => 'You must be logged in to manage widgets']);
    }

    $widgetsFile = ADMIN_CONFIG_DIR . '/widgets.json';
    $widgets = file_exists($widgetsFile) ? json_decode(file_get_contents($widgetsFile), true) : [];

    switch ($_POST['action']) {
        case 'add_sidebar':
            $sidebarId = trim($_POST['id'] ?? '');
            if ($sidebarId && !isset($widgets[$sidebarId])) {
                $widgets[$sidebarId] = [
                    'name' => $_POST['id'] ?? $sidebarId,
                    'description' => '',
                    'widgets' => []
                ];
                file_put_contents($widgetsFile, json_encode($widgets, JSON_PRETTY_PRINT));
                returnJsonResponse(['success' => true, 'message' => 'Sidebar created successfully']);
            } else {
                returnJsonResponse(['success' => false, 'error' => 'Invalid sidebar ID or sidebar already exists']);
            }
            break;

        case 'add_widget':
            $sidebarId = $_POST['sidebar_id'] ?? '';
            if (isset($widgets[$sidebarId])) {
                $widgets[$sidebarId]['widgets'][] = [
                    'id' => uniqid(),
                    'type' => $_POST['widget_type'],
                    'title' => $_POST['widget_title'],
                    'content' => $_POST['widget_content'],
                    'classes' => $_POST['widget_classes'] ?? '',
                    'settings' => []
                ];
                file_put_contents($widgetsFile, json_encode($widgets, JSON_PRETTY_PRINT));
                returnJsonResponse(['success' => true, 'message' => 'Widget added successfully']);
            } else {
                returnJsonResponse(['success' => false, 'error' => 'Sidebar not found']);
            }
            break;

        case 'save_widget':
            error_log("Widgets handler - Processing save_widget action");
            $sidebarId = $_POST['sidebar'] ?? '';
            $widgetId = $_POST['id'] ?? '';
            
            error_log("Widgets handler - Sidebar ID: " . $sidebarId);
            error_log("Widgets handler - Widget ID: " . $widgetId);
            error_log("Widgets handler - Available sidebars: " . print_r(array_keys($widgets), true));
            
            if (isset($widgets[$sidebarId])) {
                // Ensure widgets is always an array
                if (!is_array($widgets[$sidebarId]['widgets'])) {
                    $widgets[$sidebarId]['widgets'] = [];
                }
                
                if ($widgetId) {
                    error_log("Widgets handler - Updating existing widget");
                    // Update existing widget
                    foreach ($widgets[$sidebarId]['widgets'] as &$widget) {
                        if ($widget['id'] === $widgetId) {
                            $widget['title'] = $_POST['widget_title'];
                            $widget['content'] = $_POST['widget_content'];
                            $widget['type'] = $_POST['widget_type'];
                            $widget['classes'] = $_POST['widget_classes'] ?? '';
                            error_log("Widgets handler - Widget updated successfully");
                            break;
                        }
                    }
                } else {
                    error_log("Widgets handler - Adding new widget");
                    // Add new widget
                    $newWidgetId = uniqid();
                    $newWidget = [
                        'id' => $newWidgetId,
                        'type' => $_POST['widget_type'],
                        'title' => $_POST['widget_title'],
                        'content' => $_POST['widget_content'],
                        'classes' => $_POST['widget_classes'] ?? '',
                        'settings' => []
                    ];
                    
                    $widgets[$sidebarId]['widgets'][] = $newWidget;
                    error_log("Widgets handler - New widget added with ID: " . $newWidgetId);
                }
                
                // Normalize the widgets array to ensure consistent structure
                $normalizedWidgets = [];
                foreach ($widgets[$sidebarId]['widgets'] as $widget) {
                    if (is_array($widget) && isset($widget['id'])) {
                        $normalizedWidgets[] = $widget;
                    }
                }
                $widgets[$sidebarId]['widgets'] = $normalizedWidgets;
                
                file_put_contents($widgetsFile, json_encode($widgets, JSON_PRETTY_PRINT));
                error_log("Widgets handler - Widgets file saved successfully");
                returnJsonResponse(['success' => true, 'message' => 'Widget saved successfully']);
            } else {
                error_log("Widgets handler - Sidebar not found: " . $sidebarId);
                returnJsonResponse(['success' => false, 'error' => 'Sidebar not found']);
            }
            break;

        case 'update_widget':
            $sidebarId = $_POST['sidebar_id'] ?? '';
            $widgetId = $_POST['widget_id'] ?? '';
            if (isset($widgets[$sidebarId])) {
                foreach ($widgets[$sidebarId]['widgets'] as &$widget) {
                    if ($widget['id'] === $widgetId) {
                        $widget['title'] = $_POST['widget_title'];
                        $widget['content'] = $_POST['widget_content'];
                        $widget['settings'] = $_POST['widget_settings'] ?? [];
                    }
                }
                file_put_contents($widgetsFile, json_encode($widgets, JSON_PRETTY_PRINT));
                returnJsonResponse(['success' => true, 'message' => 'Widget updated successfully']);
            } else {
                returnJsonResponse(['success' => false, 'error' => 'Sidebar not found']);
            }
            break;

        case 'delete_widget':
            $sidebarId = $_POST['sidebar_id'] ?? '';
            $widgetId = $_POST['widget_id'] ?? '';
            error_log("Widgets handler - Deleting widget: " . $widgetId . " from sidebar: " . $sidebarId);
            
            // Validate required parameters
            if (empty($sidebarId) || empty($widgetId)) {
                error_log("Widgets handler - Missing required parameters. Sidebar ID: '$sidebarId', Widget ID: '$widgetId'");
                returnJsonResponse(['success' => false, 'error' => 'Sidebar ID and Widget ID are required']);
            }
            
            if (isset($widgets[$sidebarId])) {
                // Ensure widgets is always an array
                if (!is_array($widgets[$sidebarId]['widgets'])) {
                    $widgets[$sidebarId]['widgets'] = [];
                }
                
                // Filter out the widget to delete
                $widgets[$sidebarId]['widgets'] = array_filter(
                    $widgets[$sidebarId]['widgets'],
                    function($widget) use ($widgetId) {
                        return is_array($widget) && isset($widget['id']) && $widget['id'] !== $widgetId;
                    }
                );
                
                // Re-index the array to ensure sequential keys
                $widgets[$sidebarId]['widgets'] = array_values($widgets[$sidebarId]['widgets']);
                
                file_put_contents($widgetsFile, json_encode($widgets, JSON_PRETTY_PRINT));
                error_log("Widgets handler - Widget deleted successfully");
                returnJsonResponse(['success' => true, 'message' => 'Widget deleted successfully']);
            } else {
                error_log("Widgets handler - Sidebar not found for deletion: " . $sidebarId);
                returnJsonResponse(['success' => false, 'error' => 'Sidebar not found']);
            }
            break;

        case 'reorder_widgets':
            $sidebarId = $_POST['sidebar'] ?? '';
            $newOrder = json_decode($_POST['widgets'] ?? '[]', true);
            if (isset($widgets[$sidebarId]) && is_array($newOrder)) {
                $reorderedWidgets = [];
                foreach ($newOrder as $widgetId) {
                    foreach ($widgets[$sidebarId]['widgets'] as $widget) {
                        if ($widget['id'] === $widgetId) {
                            $reorderedWidgets[] = $widget;
                            break;
                        }
                    }
                }
                $widgets[$sidebarId]['widgets'] = $reorderedWidgets;
                file_put_contents($widgetsFile, json_encode($widgets, JSON_PRETTY_PRINT));
                returnJsonResponse(['success' => true, 'message' => 'Widget order updated successfully']);
            } else {
                returnJsonResponse(['success' => false, 'error' => 'Invalid data']);
            }
            break;

        default:
            // This should never be reached since we filter actions above
            error_log("Widgets handler - Unexpected action: " . ($_POST['action'] ?? 'none'));
            returnJsonResponse(['success' => false, 'error' => 'Unexpected widget action']);
            break;
    }
}
?>

