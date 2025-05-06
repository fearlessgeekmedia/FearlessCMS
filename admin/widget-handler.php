<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to perform this action';
    } else {
        switch ($_POST['action']) {
            case 'save_widget':
                if (!fcms_check_permission($_SESSION['username'], 'manage_widgets')) {
                    $error = 'You do not have permission to manage widgets';
                    break;
                }
                if (empty($_POST['widget_id']) || empty($_POST['widget_data'])) {
                    $error = 'Widget ID and data are required';
                    break;
                }
                $widgetId = $_POST['widget_id'] ?? '';
                $widgetData = $_POST['widget_data'] ?? '';
                
                $widgetFile = ADMIN_CONFIG_DIR . '/widgets.json';
                $widgets = file_exists($widgetFile) ? json_decode(file_get_contents($widgetFile), true) : [];
                $widgets[$widgetId] = json_decode($widgetData, true);
                if (file_put_contents($widgetFile, json_encode($widgets, JSON_PRETTY_PRINT))) {
                    $success = 'Widget saved successfully';
                } else {
                    $error = 'Failed to save widget';
                }
                break;
                
            case 'delete_widget':
                if (!fcms_check_permission($_SESSION['username'], 'manage_widgets')) {
                    $error = 'You do not have permission to manage widgets';
                    break;
                }
                if (empty($_POST['widget_id'])) {
                    $error = 'Widget ID is required';
                    break;
                }
                $widgetId = $_POST['widget_id'] ?? '';
                
                $widgetFile = ADMIN_CONFIG_DIR . '/widgets.json';
                $widgets = file_exists($widgetFile) ? json_decode(file_get_contents($widgetFile), true) : [];
                if (isset($widgets[$widgetId])) {
                    unset($widgets[$widgetId]);
                    if (file_put_contents($widgetFile, json_encode($widgets, JSON_PRETTY_PRINT))) {
                        $success = 'Widget deleted successfully';
                    } else {
                        $error = 'Failed to delete widget';
                    }
                }
                break;
        }
    }
} 