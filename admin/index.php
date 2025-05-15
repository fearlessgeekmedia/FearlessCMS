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
    // Handle logout
    if (isset($_POST['action']) && $_POST['action'] === 'logout') {
        logout();
        header('Location: /admin/login');
        exit;
    }
    
    // Handle save_content action
    if (isset($_POST['action']) && $_POST['action'] === 'save_content') {
        $path = $_POST['path'] ?? '';
        $title = $_POST['title'] ?? '';
        $template = $_POST['template'] ?? 'page';
        $content = $_POST['content'] ?? '';
        
        error_log("Save content - Path: " . $path);
        error_log("Save content - Title: " . $title);
        error_log("Save content - Template: " . $template);
        error_log("Save content - Content length: " . strlen($content));
        error_log("Save content - Content preview: " . substr($content, 0, 100));
        
        if (empty($path) || empty($title)) {
            $error = 'Path and title are required';
        } else {
            $contentFile = CONTENT_DIR . '/' . $path . '.md';
            
            // Create metadata
            $metadata = [
                'title' => $title,
                'template' => $template
            ];
            
            // Format content with metadata
            $contentWithMetadata = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->' . "\n\n" . $content;
            
            error_log("Save content - Final content length: " . strlen($contentWithMetadata));
            error_log("Save content - Final content preview: " . substr($contentWithMetadata, 0, 100));
            
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
