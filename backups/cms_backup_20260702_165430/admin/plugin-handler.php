<?php
// Check if session extension is loaded
if (!extension_loaded('session') || !function_exists('session_start')) {
    error_log("Warning: Session functionality not available in plugin handler");
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Session functionality not available']);
    exit;
}

// Session should already be started by session.php
// No need to start it again

// Include required files
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/CMSModeManager.php';

// Only handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    error_log("DEBUG: Plugin handler - Action received: " . $_POST['action']);
    // POST data debugging removed for security
    // Session debugging removed for security

    // Clear any previous output and set JSON header
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');

    if (!isLoggedIn()) {
        error_log("DEBUG: Plugin handler - User not logged in");
        fcms_flush_output(); // Flush output buffer before setting headers
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You must be logged in to perform this action']);
        exit;
    }

    error_log("DEBUG: Plugin handler - User is logged in, proceeding with action");

    // Initialize CMS mode manager
    $cmsModeManager = new CMSModeManager();

    try {
        error_log("DEBUG: Plugin handler - Entering switch statement for action: " . $_POST['action']);
        switch ($_POST['action']) {
            case 'activate_plugin':
                error_log("DEBUG: Plugin handler - Processing activate_plugin");
                if (!$cmsModeManager->canActivatePlugins()) {
                    throw new Exception('Plugin activation is not allowed in the current CMS mode');
                }
                if (empty($_POST['plugin_slug'])) {
                    throw new Exception('Plugin slug is required');
                }
                $plugin_slug = $_POST['plugin_slug'];
                error_log("DEBUG: Plugin handler - Activating plugin: " . $plugin_slug);
                $activePluginsFile = PLUGIN_CONFIG;
                $activePlugins = file_exists($activePluginsFile) ? json_decode(file_get_contents($activePluginsFile), true) : [];
                if (!in_array($plugin_slug, $activePlugins)) {
                    $activePlugins[] = $plugin_slug;
                    if (!file_put_contents($activePluginsFile, json_encode($activePlugins, JSON_PRETTY_PRINT))) {
                        throw new Exception('Failed to save plugin configuration');
                    }
                }
                error_log("DEBUG: Plugin handler - Plugin activated successfully");
                echo json_encode(['success' => true, 'message' => 'Plugin activated successfully']);
                break;

            case 'deactivate_plugin':
                error_log("DEBUG: Plugin handler - Processing deactivate_plugin");
                if (!$cmsModeManager->canDeactivatePlugins()) {
                    throw new Exception('Plugin deactivation is not allowed in the current CMS mode');
                }
                if (empty($_POST['plugin_slug'])) {
                    throw new Exception('Plugin slug is required');
                }
                $plugin_slug = $_POST['plugin_slug'];
                error_log("DEBUG: Plugin handler - Deactivating plugin: " . $plugin_slug);
                $activePluginsFile = PLUGIN_CONFIG;
                $activePlugins = file_exists($activePluginsFile) ? json_decode(file_get_contents($activePluginsFile), true) : [];
                $pluginIndex = array_search($plugin_slug, $activePlugins);
                if ($pluginIndex !== false) {
                    array_splice($activePlugins, $pluginIndex, 1);
                    if (!file_put_contents($activePluginsFile, json_encode($activePlugins, JSON_PRETTY_PRINT))) {
                        throw new Exception('Failed to save plugin configuration');
                    }
                }
                error_log("DEBUG: Plugin handler - Plugin deactivated successfully");
                echo json_encode(['success' => true, 'message' => 'Plugin deactivated successfully']);
                break;

            case 'delete_plugin':
                error_log("DEBUG: Plugin handler - Processing delete_plugin");
                if (!$cmsModeManager->canDeletePlugins()) {
                    throw new Exception('Plugin deletion is not allowed in the current CMS mode');
                }
                if (empty($_POST['plugin_slug'])) {
                    throw new Exception('Plugin slug is required');
                }
                $plugin_slug = $_POST['plugin_slug'];
                // Mitigation: Only allow safe plugin slugs
                if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $plugin_slug)) {
                    throw new Exception('Invalid plugin slug');
                }
                error_log("DEBUG: Plugin handler - Deleting plugin: " . $plugin_slug);

                // Check if plugin is active before deletion
                $activePluginsFile = PLUGIN_CONFIG;
                $activePlugins = file_exists($activePluginsFile) ? json_decode(file_get_contents($activePluginsFile), true) : [];
                if (in_array($plugin_slug, $activePlugins)) {
                    throw new Exception('Cannot delete an active plugin. Please deactivate it first.');
                }

                // Delete plugin directory with realpath check
                $pluginDir = PLUGIN_DIR . '/' . $plugin_slug;
                $resolvedPluginDir = realpath($pluginDir);
                $resolvedPluginBase = realpath(PLUGIN_DIR);
                if (!$resolvedPluginDir || strpos($resolvedPluginDir, $resolvedPluginBase) !== 0) {
                    throw new Exception('Access denied: Invalid plugin directory');
                }
                if (is_dir($resolvedPluginDir)) {
                    if (!deleteDirectory($resolvedPluginDir)) {
                        throw new Exception('Failed to delete plugin directory');
                    }
                }

                error_log("DEBUG: Plugin handler - Plugin deleted successfully");
                echo json_encode(['success' => true, 'message' => 'Plugin deleted successfully']);
                break;

            default:
                error_log("DEBUG: Plugin handler - Invalid action: " . $_POST['action']);
                throw new Exception('Invalid action specified');
        }
    } catch (Exception $e) {
        error_log("DEBUG: Plugin handler - Exception caught: " . $e->getMessage());
        fcms_flush_output(); // Flush output buffer before setting headers
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    error_log("DEBUG: Plugin handler - Exiting");
    exit;
}

/**
 * Recursively delete a directory
 */
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }

    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }

    return rmdir($dir);
}
