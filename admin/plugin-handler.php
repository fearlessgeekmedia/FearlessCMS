<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Only handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Clear any previous output and set JSON header
    ob_clean();
    header('Content-Type: application/json');

    if (!isLoggedIn()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You must be logged in to perform this action']);
        exit;
    }

    try {
        switch ($_POST['action']) {
            case 'activate_plugin':
                if (!fcms_check_permission($_SESSION['username'], 'manage_plugins')) {
                    throw new Exception('You do not have permission to manage plugins');
                }
                if (empty($_POST['plugin_slug'])) {
                    throw new Exception('Plugin slug is required');
                }
                $plugin_slug = $_POST['plugin_slug'];
                $activePluginsFile = PLUGIN_CONFIG;
                $activePlugins = file_exists($activePluginsFile) ? json_decode(file_get_contents($activePluginsFile), true) : [];
                if (!in_array($plugin_slug, $activePlugins)) {
                    $activePlugins[] = $plugin_slug;
                    if (!file_put_contents($activePluginsFile, json_encode($activePlugins, JSON_PRETTY_PRINT))) {
                        throw new Exception('Failed to save plugin configuration');
                    }
                }
                echo json_encode(['success' => true, 'message' => 'Plugin activated successfully']);
                break;
                
            case 'deactivate_plugin':
                if (!fcms_check_permission($_SESSION['username'], 'manage_plugins')) {
                    throw new Exception('You do not have permission to manage plugins');
                }
                if (empty($_POST['plugin_slug'])) {
                    throw new Exception('Plugin slug is required');
                }
                $plugin_slug = $_POST['plugin_slug'];
                $activePluginsFile = PLUGIN_CONFIG;
                $activePlugins = file_exists($activePluginsFile) ? json_decode(file_get_contents($activePluginsFile), true) : [];
                $pluginIndex = array_search($plugin_slug, $activePlugins);
                if ($pluginIndex !== false) {
                    array_splice($activePlugins, $pluginIndex, 1);
                    if (!file_put_contents($activePluginsFile, json_encode($activePlugins, JSON_PRETTY_PRINT))) {
                        throw new Exception('Failed to save plugin configuration');
                    }
                }
                echo json_encode(['success' => true, 'message' => 'Plugin deactivated successfully']);
                break;

            default:
                throw new Exception('Invalid action specified');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
} 