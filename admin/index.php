<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering at the very beginning
ob_start();

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
require_once __DIR__ . '/newpage-handler.php';

// Get action from GET or POST, default to dashboard
$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';

// Debug logging
error_log("Admin index.php - Action: " . $action);
error_log("Admin index.php - Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Admin index.php - POST data: " . print_r($_POST, true));
error_log("Admin index.php - GET data: " . print_r($_GET, true));

// Handle AJAX requests for menu management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'manage_menus') {
    // Clear any output
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'You must be logged in to perform this action']);
        exit;
    }
    
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['action'])) {
        echo json_encode(['success' => false, 'error' => 'No action specified']);
        exit;
    }
    
    $menuFile = CONFIG_DIR . '/menus.json';
    $menus = file_exists($menuFile) ? json_decode(file_get_contents($menuFile), true) : [];
    
    switch ($data['action']) {
        case 'save_menu':
            if (empty($data['menu_id']) || empty($data['items'])) {
                echo json_encode(['success' => false, 'error' => 'Menu ID and items are required']);
                exit;
            }
            
            $menuId = $data['menu_id'];
            $menus[$menuId] = [
                'label' => $data['label'] ?? ucwords(str_replace('_', ' ', $menuId)),
                'menu_class' => $data['class'] ?? '',
                'items' => $data['items']
            ];
            
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
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            exit;
    }
}

// Handle AJAX requests for loading menu data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'load_menu') {
    // Clear any output
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'You must be logged in to perform this action']);
        exit;
    }
    
    if (!isset($_GET['menu_id'])) {
        echo json_encode(['success' => false, 'error' => 'Menu ID is required']);
        exit;
    }
    
    $menuId = $_GET['menu_id'];
    $menuFile = CONFIG_DIR . '/menus.json';
    
    if (!file_exists($menuFile)) {
        echo json_encode(['success' => false, 'error' => 'Menu file not found']);
        exit;
    }
    
    $menus = json_decode(file_get_contents($menuFile), true);
    if (!isset($menus[$menuId])) {
        echo json_encode(['success' => false, 'error' => 'Menu not found']);
        exit;
    }
    
    echo json_encode($menus[$menuId]);
    exit;
}

// Handle plugin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['activate_plugin', 'deactivate_plugin'])) {
    require_once __DIR__ . '/plugin-handler.php';
    exit;
}

// If not logged in and not on login page, redirect to login
if (!isLoggedIn() && $action !== 'login') {
    header('Location: /' . $adminPath . '/login');
    exit;
}

// If logged in and trying to access login page, redirect to dashboard
if (isLoggedIn() && $action === 'login') {
    header('Location: /' . $adminPath . '?action=dashboard');
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
$siteDescription = ''; // Default empty tagline
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if (isset($config['site_name'])) {
        $siteName = $config['site_name'];
    }
    if (isset($config['site_description'])) {
        $siteDescription = $config['site_description'];
    }
    // Load custom code
    $custom_css = $config['custom_css'] ?? '';
    $custom_js = $config['custom_js'] ?? '';
}

// Load menu options for menu management
$menu_options = '';
if (file_exists($menusFile)) {
    $menus = json_decode(file_get_contents($menusFile), true);
    foreach ($menus as $menuId => $menu) {
        $menu_options .= sprintf(
            '<option value="%s">%s</option>',
            htmlspecialchars($menuId),
            htmlspecialchars($menu['label'] ?? ucwords(str_replace('_', ' ', $menuId)))
        );
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
    // Recursively get all .md files
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($contentDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    $files = new RegexIterator($files, '/\.md$/');
    
    // Convert to array and sort by modification time
    $fileArray = iterator_to_array($files);
    usort($fileArray, function($a, $b) {
        return $b->getMTime() - $a->getMTime();
    });
    
    // Get 5 most recent files, excluding preview directory
    $count = 0;
    foreach ($fileArray as $file) {
        // Skip files in preview directory or with preview in the path
        if (strpos($file->getPathname(), '/preview/') !== false || strpos($file->getPathname(), 'preview') !== false) {
            continue;
        }
        
        $content = file_get_contents($file->getPathname());
        $title = '';
        if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $content, $matches)) {
            $metadata = json_decode($matches[1], true);
            if ($metadata && isset($metadata['title'])) {
                $title = $metadata['title'];
            }
        }
        if (!$title) {
            $title = ucwords(str_replace(['-', '_'], ' ', $file->getBasename('.md')));
        }
        
        // Get relative path from content directory
        $relativePath = str_replace($contentDir . '/', '', $file->getPathname());
        $path = substr($relativePath, 0, -3); // Remove .md extension
        
        $recentContent[] = [
            'title' => $title,
            'path' => $path,
            'modified' => $file->getMTime()
        ];
        
        $count++;
        if ($count >= 5) break;
    }
}

// Load content list for content management
$contentList = [];
if (is_dir($contentDir)) {
    // Recursively get all .md files
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($contentDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    $files = new RegexIterator($files, '/\.md$/');
    
    foreach ($files as $file) {
        // Skip files in preview directory or with preview in the path
        if (strpos($file->getPathname(), '/preview/') !== false || strpos($file->getPathname(), 'preview') !== false) {
            continue;
        }
        
        $content = file_get_contents($file->getPathname());
        $title = '';
        if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $content, $matches)) {
            $metadata = json_decode($matches[1], true);
            if ($metadata && isset($metadata['title'])) {
                $title = $metadata['title'];
            }
        }
        if (!$title) {
            $title = ucwords(str_replace(['-', '_'], ' ', $file->getBasename('.md')));
        }
        
        // Get relative path from content directory
        $relativePath = str_replace($contentDir . '/', '', $file->getPathname());
        $path = substr($relativePath, 0, -3); // Remove .md extension
        
        $contentList[] = [
            'title' => $title,
            'path' => $path,
            'modified' => date('Y-m-d H:i:s', $file->getMTime())
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

// Handle POST requests for admin sections
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $postAction = $_POST['action'];
    
    // Check if this is a file manager action
    if (in_array($postAction, ['upload_file', 'delete_file'])) {
        $action = 'files'; // Set the action to 'files' to use the file manager's render callback
    }
    // Check if this is a blog action
    else if (in_array($postAction, ['save_post', 'delete_post'])) {
        $action = 'blog';
    }
    // Add other section-specific actions here as needed
}

// Handle POST for site name and tagline update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_site_name') {
    $newSiteName = trim($_POST['site_name'] ?? '');
    $newTagline = trim($_POST['site_description'] ?? '');
    $configFile = CONFIG_DIR . '/config.json';
    $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
    if ($newSiteName !== '') {
        $config['site_name'] = $newSiteName;
    }
    $config['site_description'] = $newTagline;
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    // Optionally, reload config for this request
    $siteName = $config['site_name'];
    $siteDescription = $config['site_description'];
    $success = 'Site name and tagline updated.';
}

// Handle POST requests for saving content
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_content') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to edit files';
    } else {
        $fileName = $_POST['path'] ?? '';
        $content = $_POST['content'] ?? '';
        $pageTitle = $_POST['title'] ?? '';
        $parentPage = $_POST['parent'] ?? '';
        $template = $_POST['template'] ?? 'page';
        $editorMode = $_POST['editor_mode'] ?? 'easy';

        // Validate filename: allow slashes for subfolders
        if (empty($fileName) || !preg_match('/^[a-zA-Z0-9_\/-]+$/', $fileName)) {
            $error = 'Invalid filename';
        } else {
            $filePath = CONTENT_DIR . '/' . $fileName . '.md';
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            // Check if content already has JSON frontmatter
            $hasFrontmatter = preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $content, $matches);
            if ($hasFrontmatter) {
                $metadata = json_decode($matches[1], true) ?: [];
                $metadata['title'] = $pageTitle;
                $metadata['editor_mode'] = $editorMode;
                $metadata['template'] = $template;
                if (!empty($parentPage)) {
                    $metadata['parent'] = $parentPage;
                } elseif (isset($metadata['parent'])) {
                    unset($metadata['parent']);
                }
                $newFrontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
                $content = preg_replace('/^<!--\s*json\s*.*?\s*-->/s', $newFrontmatter, $content);
            } else {
                $metadata = [
                    'title' => $pageTitle,
                    'editor_mode' => $editorMode,
                    'template' => $template
                ];
                if (!empty($parentPage)) {
                    $metadata['parent'] = $parentPage;
                }
                $newFrontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
                $content = $newFrontmatter . "\n\n" . $content;
            }
            if (file_put_contents($filePath, $content) !== false) {
                $success = 'File saved successfully';
            } else {
                $error = 'Failed to save file';
            }
        }
    }
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

// Check if this is a registered admin section
$admin_sections = fcms_get_admin_sections();
$section_found = false;

// First check direct sections
if (isset($admin_sections[$action])) {
    error_log("Admin index.php - Loading admin section: " . $action);
    if (isset($admin_sections[$action]['render_callback']) && is_callable($admin_sections[$action]['render_callback'])) {
        ob_start();
        $section_content = call_user_func($admin_sections[$action]['render_callback']);
        if (is_string($section_content)) {
            echo $section_content;
        }
        $content = ob_get_clean();
        $section_found = true;
    } else {
        error_log("Admin index.php - Invalid render callback for section: " . $action);
    }
} else {
    // Then check child sections
    foreach ($admin_sections as $parent_id => $parent) {
        if (isset($parent['children']) && isset($parent['children'][$action])) {
            error_log("Admin index.php - Loading child section: " . $action);
            if (isset($parent['children'][$action]['render_callback']) && is_callable($parent['children'][$action]['render_callback'])) {
                ob_start();
                $section_content = call_user_func($parent['children'][$action]['render_callback']);
                if (is_string($section_content)) {
                    echo $section_content;
                }
                $content = ob_get_clean();
                $section_found = true;
                break;
            } else {
                error_log("Admin index.php - Invalid render callback for child section: " . $action);
            }
        }
    }
}

// If no admin section was found, try to load the template file
if (!$section_found) {
    if (file_exists($templateFile)) {
        error_log("Admin index.php - Loading template: " . $templateFile);
        ob_start();
        include $templateFile;
        $content = ob_get_clean();
        $section_found = true;
    } else {
        error_log("Admin index.php - Invalid action: " . $action . " (Template file not found: " . $templateFile . ")");
        $content = '<div class="alert alert-danger">Invalid action specified.</div>';
    }
}

// Include the base template
include ADMIN_TEMPLATE_DIR . '/base.php';
?>
