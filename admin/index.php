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
