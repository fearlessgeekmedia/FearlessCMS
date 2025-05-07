<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('PROJECT_ROOT', dirname(__DIR__));
define('ADMIN_CONFIG_DIR', __DIR__ . '/config');
define('ADMIN_TEMPLATE_DIR', __DIR__ . '/templates');
define('CONTENT_DIR', __DIR__ . '/../content');
define('CONFIG_DIR', dirname(__DIR__) . '/config');

session_start();

require_once PROJECT_ROOT . '/includes/ThemeManager.php';
require_once PROJECT_ROOT . '/includes/plugins.php';

$themeManager = new ThemeManager();
$usersFile = ADMIN_CONFIG_DIR . '/users.json';
$themes = $themeManager->getThemes();
$menusFile = CONFIG_DIR . '/menus.json';
$pluginsFile = PLUGIN_CONFIG;
$activePlugins = file_exists($pluginsFile) ? json_decode(file_get_contents($pluginsFile), true) : [];

function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

$username = $_SESSION['username'] ?? '';
$pageTitle = 'Dashboard';
$content = '';
$error = '';
$success = '';
$custom_css = '';
$custom_js = '';
$plugin_nav_items = '';

if (!isLoggedIn()) {
    // Show login page
    ob_start();
    include ADMIN_TEMPLATE_DIR . '/login.php';
    $content = ob_get_clean();
    $pageTitle = 'Login';
} else {
    $action = $_GET['action'] ?? 'dashboard';
    $pageTitle = ucfirst($action);

    switch ($action) {
        case 'dashboard':
            ob_start();
            include ADMIN_TEMPLATE_DIR . '/dashboard.php';
            $content = ob_get_clean();
            break;

        case 'manage_users':
            $users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];
            ob_start();
            include ADMIN_TEMPLATE_DIR . '/users.php';
            $content = ob_get_clean();
            break;

        case 'manage_themes':
            $themes = $themeManager->getThemes();
            ob_start();
            include ADMIN_TEMPLATE_DIR . '/themes.php';
            $content = ob_get_clean();
            break;

        case 'manage_menus':
            $menus = file_exists($menusFile) ? json_decode(file_get_contents($menusFile), true) : [];
            $menu_options = '';
            foreach ($menus as $id => $menu) {
                $menu_options .= '<option value="' . htmlspecialchars($id) . '">' . htmlspecialchars($id) . '</option>';
            }
            ob_start();
            include ADMIN_TEMPLATE_DIR . '/menus.php';
            $content = ob_get_clean();
            break;

        case 'manage_plugins':
            $plugins = [];
            $pluginDir = PROJECT_ROOT . '/plugins';
            if (is_dir($pluginDir)) {
                $pluginFolders = glob($pluginDir . '/*', GLOB_ONLYDIR);
                foreach ($pluginFolders as $folder) {
                    $pluginId = basename($folder);
                    $pluginFile = $folder . '/' . $pluginId . '.php';
                    $pluginData = [
                        'id' => $pluginId,
                        'name' => ucfirst($pluginId),
                        'description' => 'A plugin for FearlessCMS',
                        'version' => '1.0',
                        'author' => 'Unknown',
                        'active' => in_array($pluginId, $activePlugins)
                    ];
                    if (file_exists($pluginFile)) {
                        $pluginContent = file_get_contents($pluginFile);
                        if (preg_match('/Plugin Name: (.*?)$/m', $pluginContent, $matches)) {
                            $pluginData['name'] = trim($matches[1]);
                        }
                        if (preg_match('/Description: (.*?)$/m', $pluginContent, $matches)) {
                            $pluginData['description'] = trim($matches[1]);
                        }
                        if (preg_match('/Version: (.*?)$/m', $pluginContent, $matches)) {
                            $pluginData['version'] = trim($matches[1]);
                        }
                        if (preg_match('/Author: (.*?)$/m', $pluginContent, $matches)) {
                            $pluginData['author'] = trim($matches[1]);
                        }
                    }
                    $plugins[] = $pluginData;
                }
            }
                
            ob_start();
            include ADMIN_TEMPLATE_DIR . '/plugins.php';
            $content = ob_get_clean();
            break;
        
            case 'files':
                // Set up variables for the file manager
                $uploadsDir = PROJECT_ROOT . '/uploads';
                $webUploadsDir = '/uploads';
                $allowedExts = ['jpg','jpeg','png','gif','webp','pdf','zip','svg','txt','md'];
                $maxFileSize = 10 * 1024 * 1024; // 10MB
            
                // Initialize error/success
                $error = '';
                $success = '';
            
                // Handle file upload
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_file') {
                    if (!empty($_FILES['file']['name'])) {
                        $file = $_FILES['file'];
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        if (!in_array($ext, $allowedExts)) {
                            $error = 'File type not allowed.';
                        } elseif ($file['size'] > $maxFileSize) {
                            $error = 'File is too large.';
                        } else {
                            if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
                            $target = $uploadsDir . '/' . basename($file['name']);
                            if (move_uploaded_file($file['tmp_name'], $target)) {
                                $success = 'File uploaded successfully.';
                            } else {
                                $error = 'Failed to upload file.';
                            }
                        }
                    } else {
                        $error = 'No file selected.';
                    }
                }
            
                // Handle file deletion
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_file') {
                    $filename = $_POST['filename'] ?? '';
                    $filepath = realpath($uploadsDir . '/' . $filename);
                    if ($filename && $filepath && strpos($filepath, realpath($uploadsDir)) === 0 && is_file($filepath)) {
                        if (unlink($filepath)) {
                            $success = 'File deleted.';
                        } else {
                            $error = 'Failed to delete file.';
                        }
                    } else {
                        $error = 'Invalid file.';
                    }
                }
            
                // List files
                $files = [];
                if (is_dir($uploadsDir)) {
                    foreach (scandir($uploadsDir) as $f) {
                        if ($f === '.' || $f === '..') continue;
                        $full = $uploadsDir . '/' . $f;
                        if (is_file($full)) {
                            $files[] = [
                                'name' => $f,
                                'size' => filesize($full),
                                'type' => mime_content_type($full),
                                'url'  => $webUploadsDir . '/' . rawurlencode($f),
                                'ext'  => strtolower(pathinfo($f, PATHINFO_EXTENSION))
                            ];
                        }
                    }
                }
            
                // Buffer and include the file manager template
                ob_start();
                include ADMIN_TEMPLATE_DIR . '/file_manager.php';
                $content = ob_get_clean();
                $pageTitle = 'Files';
                break;

            default:
            ob_start();
            include ADMIN_TEMPLATE_DIR . '/dashboard.php';
            $content = ob_get_clean();
    }
}

// Load base template
ob_start();
include ADMIN_TEMPLATE_DIR . '/base.php';
echo ob_get_clean();
?>
