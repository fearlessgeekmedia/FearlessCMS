<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug session at very start
error_log("Admin index.php - Initial session state: " . print_r($_SESSION, true));

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/ThemeManager.php';
require_once dirname(__DIR__) . '/includes/plugins.php';
require_once __DIR__ . '/widget-handler.php';
require_once __DIR__ . '/theme-handler.php';
require_once __DIR__ . '/store-handler.php';

// Get action from GET or POST, default to dashboard
$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';

// Debug logging
error_log("Admin index.php - Action: " . $action);

// Handle plugin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['activate_plugin', 'deactivate_plugin'])) {
    require_once __DIR__ . '/plugin-handler.php';
    exit;
}

// If not logged in and not on login page, redirect to login
if (!isLoggedIn() && $action !== 'login') {
    header('Location: /admin/login');
    exit;
}

// If logged in and trying to access login page, redirect to dashboard
if (isLoggedIn() && $action === 'login') {
    header('Location: /admin?action=dashboard');
    exit;
}

// Load plugins and run init hook
fcms_load_plugins();
fcms_do_hook('init');

// Debug logging for admin sections
$admin_sections = fcms_get_admin_sections();
error_log("Admin index.php - Available admin sections: " . print_r(array_keys($admin_sections), true));

$pageTitle = ucfirst($action);
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
    // Load custom code
    $custom_css = $config['custom_css'] ?? '';
    $custom_js = $config['custom_js'] ?? '';
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
$content = '';
$error = '';
$success = '';
$plugin_nav_items = '';

// Load content data for edit_content action
if ($action === 'edit_content' && isset($_GET['path'])) {
    $path = $_GET['path'];
                        $contentFile = CONTENT_DIR . '/' . $path . '.md';
                        if (file_exists($contentFile)) {
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
    } else {
        $error = 'Content file not found';
        $contentData = '';
        $title = '';
    }
} else {
    $contentData = '';
    $title = '';
}

// Process POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received");
    error_log("POST data: " . print_r($_POST, true));
    error_log("Request headers: " . print_r(getallheaders(), true));
    
    // Handle logout
    if (isset($_POST['action']) && $_POST['action'] === 'logout') {
        logout();
        header('Location: /admin/login');
        exit;
    }
    
    // Get the action from either POST data or JSON input
    $action = null;
    $data = [];
    
    // Check if this is a JSON request
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    if (strpos($contentType, 'application/json') !== false) {
        error_log("JSON request detected");
        $json = file_get_contents('php://input');
        error_log("Received JSON data: " . $json);
        
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            echo json_encode(['success' => false, 'error' => 'Invalid JSON data: ' . json_last_error_msg()]);
            exit;
        }
        
        if (isset($data['action'])) {
            $action = $data['action'];
        }
    } else {
        // Regular POST data
        $data = $_POST;
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
        }
    }
    
    error_log("Action: " . ($action ?? 'none'));
    error_log("Data: " . print_r($data, true));
    
    // Handle menu actions
    if ($action === 'save_menu' || $action === 'create_menu' || $action === 'delete_menu') {
        header('Content-Type: application/json');
        
        switch ($action) {
            case 'save_menu':
                error_log("Processing save_menu action");
                if (empty($data['menu_id']) || empty($data['menu_data'])) {
                    error_log("Missing menu_id or menu_data");
                    echo json_encode(['success' => false, 'error' => 'Menu ID and data are required']);
                    exit;
                }
                
                $menuId = $data['menu_id'];
                $menuData = $data['menu_data'];
                
                if (!is_array($menuData)) {
                    error_log("Invalid menu data format");
                    echo json_encode(['success' => false, 'error' => 'Invalid menu data format']);
                    exit;
                }
                
                $menuFile = CONFIG_DIR . '/menus.json';
                $menus = file_exists($menuFile) ? json_decode(file_get_contents($menuFile), true) : [];
                $menus[$menuId] = $menuData;
                
                if (file_put_contents($menuFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                    error_log("Menu saved successfully");
                    echo json_encode(['success' => true]);
                } else {
                    error_log("Failed to save menu");
                    echo json_encode(['success' => false, 'error' => 'Failed to save menu']);
                }
                exit;

            case 'create_menu':
                error_log("Processing create_menu action");
                if (empty($data['name'])) {
                    error_log("Menu name is required");
                    echo json_encode(['success' => false, 'error' => 'Menu name is required']);
                    exit;
                }
                
                $menuId = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $data['name']));
                $menuFile = CONFIG_DIR . '/menus.json';
                $menus = file_exists($menuFile) ? json_decode(file_get_contents($menuFile), true) : [];
                
                if (isset($menus[$menuId])) {
                    error_log("Menu already exists: " . $menuId);
                    echo json_encode(['success' => false, 'error' => 'Menu with this name already exists']);
                    exit;
                }
                
                $menus[$menuId] = [
                    'label' => $data['name'],
                    'menu_class' => $data['class'] ?? '',
                    'items' => []
                ];
                
                if (file_put_contents($menuFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                    error_log("Menu created successfully");
                    echo json_encode(['success' => true]);
                } else {
                    error_log("Failed to create menu");
                    echo json_encode(['success' => false, 'error' => 'Failed to create menu']);
                }
                exit;

            case 'delete_menu':
                error_log("Processing delete_menu action");
                if (empty($data['menu_id'])) {
                    error_log("Menu ID is required");
                    echo json_encode(['success' => false, 'error' => 'Menu ID is required']);
                    exit;
                }
                
                $menuId = $data['menu_id'];
                $menuFile = CONFIG_DIR . '/menus.json';
                $menus = file_exists($menuFile) ? json_decode(file_get_contents($menuFile), true) : [];
                
                if (isset($menus[$menuId])) {
                    unset($menus[$menuId]);
                    if (file_put_contents($menuFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                        error_log("Menu deleted successfully");
                        echo json_encode(['success' => true]);
                    } else {
                        error_log("Failed to delete menu");
                        echo json_encode(['success' => false, 'error' => 'Failed to delete menu']);
                    }
                } else {
                    error_log("Menu not found: " . $menuId);
                    echo json_encode(['success' => false, 'error' => 'Menu not found']);
                }
                exit;
        }
    }
    
    // Handle save_content action
    if ($action === 'save_content') {
        $path = $_POST['path'] ?? '';
        $title = $_POST['title'] ?? '';
        $template = $_POST['template'] ?? 'page';
        $content = $_POST['content'] ?? '';
        $parent = $_POST['parent'] ?? '';
        
        error_log("Save content - Path: " . $path);
        error_log("Save content - Title: " . $title);
        error_log("Save content - Template: " . $template);
        error_log("Save content - Parent: " . $parent);
        error_log("Save content - Content length: " . strlen($content));
        error_log("Save content - Content preview: " . substr($content, 0, 100));
        
        if (empty($path) || empty($title)) {
            $error = 'Path and title are required';
        } else {
            // Determine the target directory and file path
            $targetDir = CONTENT_DIR;
            $targetPath = $path;
            
            // If parent is specified, move content to parent's directory
            if (!empty($parent)) {
                $targetDir = CONTENT_DIR . '/' . $parent;
                $targetPath = $parent . '/' . $path;
                
                // Create parent directory if it doesn't exist
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
            }
            
            $contentFile = $targetDir . '/' . basename($path) . '.md';
            
            // Create metadata
            $metadata = [
                'title' => $title,
                'template' => $template
            ];
            
            // Add parent if specified
            if (!empty($parent)) {
                $metadata['parent'] = $parent;
            }
            
            // Format content with metadata
            $contentWithMetadata = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->' . "\n\n" . $content;
            
            error_log("Save content - Final content length: " . strlen($contentWithMetadata));
            error_log("Save content - Final content preview: " . substr($contentWithMetadata, 0, 100));
            error_log("Save content - Target file: " . $contentFile);
            
            // If the file is being moved to a new location, delete the old one
            $oldFile = CONTENT_DIR . '/' . $path . '.md';
            if ($oldFile !== $contentFile && file_exists($oldFile)) {
                unlink($oldFile);
            }
            
            if (file_put_contents($contentFile, $contentWithMetadata) !== false) {
                $success = 'Content saved successfully';
                // Reload the content data
                $contentData = $contentWithMetadata;
            } else {
                $error = 'Failed to save content';
                error_log("Save content - Failed to write to file: " . $contentFile);
            }
        }
    }

    // Handle delete_content action
    if (isset($_POST['action']) && $_POST['action'] === 'delete_content') {
        $path = $_POST['path'] ?? '';
        if (!empty($path)) {
            $contentFile = CONTENT_DIR . '/' . $path . '.md';
            if (file_exists($contentFile) && unlink($contentFile)) {
                $success = 'Content deleted successfully';
                // Redirect to manage_content to refresh the list
                header('Location: ?action=manage_content');
                exit;
            } else {
                $error = 'Failed to delete content';
            }
        } else {
            $error = 'No content specified for deletion';
        }
    }
}

// Handle AJAX menu load
if (isset($_GET['action']) && $_GET['action'] === 'load_menu' && isset($_GET['menu_id'])) {
    $menuId = $_GET['menu_id'];
    $menusFile = CONFIG_DIR . '/menus.json';
    if (file_exists($menusFile)) {
        $menus = json_decode(file_get_contents($menusFile), true);
        if (isset($menus[$menuId])) {
            $menu = $menus[$menuId];
            // Normalize keys for JS
            $result = [
                'class' => $menu['menu_class'] ?? '',
                'items' => $menu['items'] ?? [],
                'label' => $menu['label'] ?? $menuId
            ];
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }
    }
    // Not found or error
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['error' => 'Menu not found']);
    exit;
}

// Map actions to their template files
$template_map = [
    'dashboard' => 'dashboard.php',
    'manage_content' => 'content-management.php',
    'manage_plugins' => 'plugins.php',
    'manage_themes' => 'themes.php',
    'manage_menus' => 'menus.php',
    'manage_settings' => 'site-settings.html',
    'edit_content' => 'edit_content.php',
    'new_content' => 'new_content.php',
    'files' => 'file_manager.php',
    'manage_users' => 'users.php',
    'manage_roles' => 'role-management.html',
    'manage_widgets' => 'widgets.php'
];

// Get the correct template file name
$templateFile = ADMIN_TEMPLATE_DIR . '/' . ($template_map[$action] ?? $action . '.php');

// Try .php first, then .html if .php doesn't exist
if (!file_exists($templateFile) && str_ends_with($templateFile, '.php')) {
    $htmlTemplateFile = str_replace('.php', '.html', $templateFile);
    if (file_exists($htmlTemplateFile)) {
        $templateFile = $htmlTemplateFile;
    }
}

// Generate $menu_options for manage_menus
if ($action === 'manage_menus') {
    if (!fcms_check_permission($_SESSION['username'], 'manage_menus')) {
        $error = 'You do not have permission to manage menus';
    } else {
        // Handle AJAX requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            
            // Get JSON input
            $json = file_get_contents('php://input');
            error_log("Received JSON data: " . $json);
            
            $data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON decode error: " . json_last_error_msg());
                echo json_encode(['success' => false, 'error' => 'Invalid JSON data: ' . json_last_error_msg()]);
                exit;
            }

            if (!isset($data['action'])) {
                echo json_encode(['success' => false, 'error' => 'No action specified']);
                exit;
            }

            switch ($data['action']) {
                case 'save_menu':
                    if (empty($data['menu_id']) || empty($data['menu_data'])) {
                        echo json_encode(['success' => false, 'error' => 'Menu ID and data are required']);
                        exit;
                    }
                    
                    $menuId = $data['menu_id'];
                    $menuData = $data['menu_data'];
                    
                    if (!is_array($menuData)) {
                        echo json_encode(['success' => false, 'error' => 'Invalid menu data format']);
                        exit;
                    }
                    
                    $menuFile = CONFIG_DIR . '/menus.json';
                    $menus = file_exists($menuFile) ? json_decode(file_get_contents($menuFile), true) : [];
                    $menus[$menuId] = $menuData;
                    
                    if (file_put_contents($menuFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                        echo json_encode(['success' => true]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Failed to save menu']);
                    }
                    exit;

                case 'create_menu':
                    if (empty($data['name'])) {
                        echo json_encode(['success' => false, 'error' => 'Menu name is required']);
                        exit;
                    }
                    
                    $menuId = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $data['name']));
                    $menuFile = CONFIG_DIR . '/menus.json';
                    $menus = file_exists($menuFile) ? json_decode(file_get_contents($menuFile), true) : [];
                    
                    if (isset($menus[$menuId])) {
                        echo json_encode(['success' => false, 'error' => 'Menu with this name already exists']);
                        exit;
                    }
                    
                    $menus[$menuId] = [
                        'label' => $data['name'],
                        'menu_class' => $data['class'] ?? '',
                        'items' => []
                    ];
                    
                    if (file_put_contents($menuFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                        echo json_encode(['success' => true]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Failed to create menu']);
                    }
                    exit;

                case 'delete_menu':
                    if (empty($data['menu_id'])) {
                        echo json_encode(['success' => false, 'error' => 'Menu ID is required']);
                        exit;
                    }
                    
                    $menuId = $data['menu_id'];
                    $menuFile = CONFIG_DIR . '/menus.json';
                    $menus = file_exists($menuFile) ? json_decode(file_get_contents($menuFile), true) : [];
                    
                    if (isset($menus[$menuId])) {
                        unset($menus[$menuId]);
                        if (file_put_contents($menuFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                            echo json_encode(['success' => true]);
                        } else {
                            echo json_encode(['success' => false, 'error' => 'Failed to delete menu']);
                        }
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Menu not found']);
                    }
                    exit;
            }
        }

        // Load menus for display
        $menuFile = CONFIG_DIR . '/menus.json';
        $menus = file_exists($menuFile) ? json_decode(file_get_contents($menuFile), true) : [];
        
        $menu_options = '';
        foreach ($menus as $id => $menu) {
            $menu_options .= '<option value="' . htmlspecialchars($id) . '">' . htmlspecialchars($menu['label']) . '</option>';
        }
        
        ob_start();
        include ADMIN_TEMPLATE_DIR . '/menus.php';
        $content = ob_get_clean();
    }
}

if (file_exists($templateFile)) {
    error_log("Admin index.php - Loading template: " . $templateFile);
    ob_start();
    
    // Special handling for widgets template
    if ($action === 'manage_widgets') {
        $widget_vars = fcms_render_widget_manager();
        extract($widget_vars);
    }
    
    include $templateFile;
    $content = ob_get_clean();
} else {
    // Check if this is a registered admin section
    $admin_sections = fcms_get_admin_sections();
    if (isset($admin_sections[$action])) {
        error_log("Admin index.php - Loading admin section: " . $action);
        ob_start();
        $section_content = call_user_func($admin_sections[$action]['render_callback']);
        if (is_string($section_content)) {
            echo $section_content;
        }
        $content = ob_get_clean();
    } else {
        error_log("Admin index.php - Invalid action: " . $action . " (Template file not found: " . $templateFile . ")");
        $content = '<div class="alert alert-danger">Invalid action specified.</div>';
    }
}

// Include the base template
include ADMIN_TEMPLATE_DIR . '/base.php';
?>
