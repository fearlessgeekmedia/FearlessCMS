<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once PROJECT_ROOT . '/includes/ThemeManager.php';
require_once PROJECT_ROOT . '/includes/plugins.php';
require_once __DIR__ . '/widget-handler.php';
require_once __DIR__ . '/theme-handler.php';

$themeManager = new ThemeManager();
$usersFile = ADMIN_CONFIG_DIR . '/users.json';
$themes = $themeManager->getThemes();
$menusFile = CONFIG_DIR . '/menus.json';
$pluginsFile = PLUGIN_CONFIG;
$activePlugins = file_exists($pluginsFile) ? json_decode(file_get_contents($pluginsFile), true) : [];

// Load site name from config
$configFile = CONFIG_DIR . '/config.json';
$siteName = 'FearlessCMS'; // Default
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if (isset($config['site_name'])) {
        $siteName = $config['site_name'];
    }
}

// Calculate total pages
$totalPages = 0;
$contentDir = CONTENT_DIR;
if (is_dir($contentDir)) {
    $files = glob($contentDir . '/*.md');
    $totalPages = count($files);
}

// Load recent content for dashboard
$recentContent = [];
if (is_dir($contentDir)) {
    $files = glob($contentDir . '/*.md');
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    $files = array_slice($files, 0, 5); // Get 5 most recent files
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $title = '';
        if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $content, $matches)) {
            $metadata = json_decode($matches[1], true);
            if ($metadata && isset($metadata['title'])) {
                $title = $metadata['title'];
            }
        }
        if (!$title) {
            $title = ucwords(str_replace(['-', '_'], ' ', basename($file, '.md')));
        }
        $recentContent[] = [
            'title' => $title,
            'path' => basename($file, '.md'),
            'modified' => filemtime($file)
        ];
    }
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
    // Process POST actions first
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle JSON POST requests
        if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';
            
            switch ($action) {
                case 'save_menu':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_menus')) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Permission denied']);
                        exit;
                    }

                    if (!isset($input['menu_id']) || !isset($input['items'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Menu ID and items are required']);
                        exit;
                    }

                    $menuId = $input['menu_id'];
                    $menuData = [
                        'label' => $input['label'] ?? ucfirst($menuId),
                        'menu_class' => $input['class'] ?? '',
                        'items' => $input['items']
                    ];

                    $menus = file_exists($menusFile) ? json_decode(file_get_contents($menusFile), true) : [];
                    $menus[$menuId] = $menuData;

                    if (file_put_contents($menusFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true]);
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Failed to save menu']);
                    }
                    exit;
                    break;

                case 'create_menu':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_menus')) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Permission denied']);
                        exit;
                    }

                    if (empty($input['name'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Menu name is required']);
                        exit;
                    }

                    $menuId = strtolower(preg_replace('/[^a-z0-9]+/', '-', $input['name']));
                    $menuData = [
                        'label' => $input['name'],
                        'menu_class' => $input['class'] ?? '',
                        'items' => []
                    ];

                    $menus = file_exists($menusFile) ? json_decode(file_get_contents($menusFile), true) : [];
                    $menus[$menuId] = $menuData;

                    if (file_put_contents($menusFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true]);
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Failed to create menu']);
                    }
                    exit;
                    break;
            }
        }
        // Handle regular POST requests
        else if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'activate_theme':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_themes')) {
                        $error = 'You do not have permission to manage themes';
                    } else if (empty($_POST['theme'])) {
                        $error = 'Theme name is required';
                    } else {
                        try {
                            $themeManager->setActiveTheme($_POST['theme']);
                            $success = 'Theme activated successfully';
                        } catch (Exception $e) {
                            $error = $e->getMessage();
                        }
                    }
                    break;

                case 'update_site_name':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_settings')) {
                        $error = 'You do not have permission to manage settings';
                    } else if (empty($_POST['site_name'])) {
                        $error = 'Site name is required';
                    } else {
                        $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
                        $config['site_name'] = trim($_POST['site_name']);
                        if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT))) {
                            $siteName = $config['site_name'];
                            $success = 'Site name updated successfully';
                        } else {
                            $error = 'Failed to update site name';
                        }
                    }
                    break;

                case 'save_content':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_content')) {
                        $error = 'You do not have permission to edit content';
                    } else if (empty($_POST['path']) || empty($_POST['content'])) {
                        $error = 'Path and content are required';
                    } else {
                        $path = $_POST['path'];
                        $title = $_POST['title'] ?? '';
                        $content = $_POST['content'];
                        $parent = $_POST['parent'] ?? '';
                        $contentFile = CONTENT_DIR . '/' . $path . '.md';
                        
                        // Create metadata
                        $metadata = [
                            'title' => $title,
                            'last_modified' => date('Y-m-d H:i:s'),
                            'author' => $_SESSION['username']
                        ];
                        
                        // Add parent if specified
                        if (!empty($parent)) {
                            $metadata['parent'] = $parent;
                        }
                        
                        // Combine metadata and content
                        $fullContent = "<!-- json " . json_encode($metadata) . " -->\n\n" . $content;
                        
                        if (file_put_contents($contentFile, $fullContent)) {
                            $success = 'Content saved successfully';
                            // Redirect back to editor
                            header('Location: ?action=edit_content&path=' . urlencode($path) . '&editor=toast');
                            exit;
                        } else {
                            $error = 'Failed to save content';
                        }
                    }
                    break;

                case 'delete_content':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_content')) {
                        $error = 'You do not have permission to delete content';
                    } else if (empty($_POST['path'])) {
                        $error = 'Path is required';
                    } else {
                        $path = $_POST['path'];
                        $contentFile = CONTENT_DIR . '/' . $path . '.md';
                        
                        if (file_exists($contentFile)) {
                            if (unlink($contentFile)) {
                                $success = 'Page deleted successfully';
                                // Redirect back to dashboard
                                header('Location: ?action=dashboard');
                                exit;
                            } else {
                                $error = 'Failed to delete page';
                            }
                        } else {
                            $error = 'Page not found';
                        }
                    }
                    break;

                case 'upload_image':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_content')) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Permission denied']);
                        exit;
                    }

                    if (empty($_FILES['file'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'No file uploaded']);
                        exit;
                    }

                    $file = $_FILES['file'];
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $maxSize = 5 * 1024 * 1024; // 5MB

                    if (!in_array($file['type'], $allowedTypes)) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Invalid file type']);
                        exit;
                    }

                    if ($file['size'] > $maxSize) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'File too large']);
                        exit;
                    }

                    // Create uploads directory if it doesn't exist
                    $uploadDir = PROJECT_ROOT . '/uploads';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    // Generate unique filename
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = uniqid() . '.' . $extension;
                    $targetPath = $uploadDir . '/' . $filename;

                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'url' => '/uploads/' . $filename
                        ]);
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Failed to save file']);
                    }
                    exit;
                    break;
            }
        }
    }

    // Get action from GET or POST, default to dashboard
    $action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';
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
            $pageTitle = 'Themes';
            ob_start();
            include ADMIN_TEMPLATE_DIR . '/themes.php';
            $content = ob_get_clean();
            break;

        case 'manage_menus':
            $menus = file_exists($menusFile) ? json_decode(file_get_contents($menusFile), true) : [];
            $menu_options = '';
            foreach ($menus as $id => $menu) {
                $menu_options .= '<option value="' . htmlspecialchars($id) . '">' . htmlspecialchars($menu['label'] ?? $id) . '</option>';
            }
            ob_start();
            include ADMIN_TEMPLATE_DIR . '/menus.php';
            $content = ob_get_clean();
            break;

        case 'load_menu':
            if (!fcms_check_permission($_SESSION['username'], 'manage_menus')) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Permission denied']);
                exit;
            }
            
            $menuId = $_GET['menu_id'] ?? '';
            if (empty($menuId)) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Menu ID is required']);
                exit;
            }
            
            $menus = file_exists($menusFile) ? json_decode(file_get_contents($menusFile), true) : [];
            if (!isset($menus[$menuId])) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Menu not found']);
                exit;
            }
            
            header('Content-Type: application/json');
            echo json_encode($menus[$menuId]);
            exit;
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
            
            case 'manage_widgets':
                // Set up variables for widgets
                $widgetManager = fcms_render_widget_manager();
                $sidebar_selection = $widgetManager['sidebar_selection'];
                $widget_list = $widgetManager['widget_list'];
                $current_sidebar = $widgetManager['current_sidebar'];
            
                ob_start();
                include ADMIN_TEMPLATE_DIR . '/widgets.php';
                $content = ob_get_clean();
                $pageTitle = 'Widgets';
                break;

            case 'new_content':
                if (!fcms_check_permission($_SESSION['username'], 'manage_content')) {
                    $error = 'You do not have permission to create content';
                    ob_start();
                    include ADMIN_TEMPLATE_DIR . '/dashboard.php';
                    $content = ob_get_clean();
                } else {
                    ob_start();
                    include ADMIN_TEMPLATE_DIR . '/new_content.php';
                    $content = ob_get_clean();
                    $pageTitle = 'New Page';
                }
                break;

            case 'edit_content':
                if (!fcms_check_permission($_SESSION['username'], 'manage_content')) {
                    $error = 'You do not have permission to edit content';
                    ob_start();
                    include ADMIN_TEMPLATE_DIR . '/dashboard.php';
                    $content = ob_get_clean();
                } else {
                    $path = $_GET['path'] ?? '';
                    $editor = $_GET['editor'] ?? 'default';
                    $contentFile = CONTENT_DIR . '/' . $path . '.md';
                    
                    if (!file_exists($contentFile)) {
                        $error = 'Content file not found';
                        ob_start();
                        include ADMIN_TEMPLATE_DIR . '/dashboard.php';
                        $content = ob_get_clean();
                    } else {
                        $contentData = file_get_contents($contentFile);
                        $title = '';
                        if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $contentData, $matches)) {
                            $metadata = json_decode($matches[1], true);
                            if ($metadata && isset($metadata['title'])) {
                                $title = $metadata['title'];
                            }
                        }
                        if (!$title) {
                            $title = ucwords(str_replace(['-', '_'], ' ', $path));
                        }
                        
                        ob_start();
                        if ($editor === 'toast') {
                            include ADMIN_TEMPLATE_DIR . '/edit_content_toast.php';
                        } else {
                            include ADMIN_TEMPLATE_DIR . '/edit_content.php';
                        }
                        $content = ob_get_clean();
                        $pageTitle = 'Edit: ' . $title;
                    }
                }
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
