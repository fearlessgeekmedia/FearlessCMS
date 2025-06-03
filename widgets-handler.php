<?php
// Add this after the widgets-handler.php require

// Handle sidebar deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_sidebar') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to manage sidebars';
    } else {
        $sidebarId = $_POST['sidebar_id'] ?? '';
        $widgetsFile = ADMIN_CONFIG_DIR . '/widgets.json';
        $widgets = file_exists($widgetsFile) ? json_decode(file_get_contents($widgetsFile), true) : [];
        
        if (isset($widgets[$sidebarId])) {
            unset($widgets[$sidebarId]);
            file_put_contents($widgetsFile, json_encode($widgets, JSON_PRETTY_PRINT));
            $success = "Sidebar '$sidebarId' deleted successfully";
            // Redirect to refresh the page
            header('Location: ?action=manage_widgets&success=' . urlencode($success));
            exit;
        } else {
            $error = 'Sidebar not found';
        }
    }
}

// Handle widget deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_widget') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to manage widgets';
    } else {
        $sidebarId = $_POST['sidebar_id'] ?? '';
        $widgetId = $_POST['widget_id'] ?? '';
        $widgetsFile = ADMIN_CONFIG_DIR . '/widgets.json';
        $widgets = file_exists($widgetsFile) ? json_decode(file_get_contents($widgetsFile), true) : [];
        
        if (isset($widgets[$sidebarId])) {
            $widgets[$sidebarId]['widgets'] = array_filter(
                $widgets[$sidebarId]['widgets'],
                fn($widget) => $widget['id'] !== $widgetId
            );
            file_put_contents($widgetsFile, json_encode($widgets, JSON_PRETTY_PRINT));
            $success = 'Widget deleted successfully';
            // Return JSON response for AJAX request
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => true]);
                exit;
            }
            header('Location: ?action=manage_widgets&sidebar=' . urlencode($sidebarId) . '&success=' . urlencode($success));
            exit;
        } else {
            $error = 'Sidebar not found';
        }
    }
}


// Handle widget operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to manage widgets';
        return;
    }

    $widgetsFile = ADMIN_CONFIG_DIR . '/widgets.json';
    $widgets = file_exists($widgetsFile) ? json_decode(file_get_contents($widgetsFile), true) : [];

    switch ($_POST['action']) {
        case 'create_sidebar':
            $sidebarId = trim($_POST['sidebar_id'] ?? '');
            if ($sidebarId && !isset($widgets[$sidebarId])) {
                $widgets[$sidebarId] = [
                    'name' => $_POST['sidebar_name'] ?? $sidebarId,
                    'description' => $_POST['sidebar_description'] ?? '',
                    'widgets' => []
                ];
                file_put_contents($widgetsFile, json_encode($widgets, JSON_PRETTY_PRINT));
                $success = 'Sidebar created successfully';
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
                    'settings' => $_POST['widget_settings'] ?? []
                ];
                file_put_contents($widgetsFile, json_encode($widgets, JSON_PRETTY_PRINT));
                $success = 'Widget added successfully';
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
                $success = 'Widget updated successfully';
            }
            break;

        case 'delete_widget':
            $sidebarId = $_POST['sidebar_id'] ?? '';
            $widgetId = $_POST['widget_id'] ?? '';
            if (isset($widgets[$sidebarId])) {
                $widgets[$sidebarId]['widgets'] = array_filter(
                    $widgets[$sidebarId]['widgets'],
                    fn($w) => $w['id'] !== $widgetId
                );
                file_put_contents($widgetsFile, json_encode($widgets, JSON_PRETTY_PRINT));
                $success = 'Widget deleted successfully';
            }
            break;

        case 'reorder_widgets':
            $sidebarId = $_POST['sidebar_id'] ?? '';
            $newOrder = json_decode($_POST['widget_order'] ?? '[]', true);
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
                $success = 'Widget order updated successfully';
            }
            break;
    }
}
?>

