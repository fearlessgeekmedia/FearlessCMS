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

// Get action from GET or POST, default to dashboard
$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';

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

// Process POST actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle logout
        if (isset($_POST['action']) && $_POST['action'] === 'logout') {
            logout();
        header('Location: /admin/login');
                    exit;
    }
    // Handle other POST actions...
}

// Load the appropriate template based on action
$templateFile = ADMIN_TEMPLATE_DIR . '/' . $action . '.php';
if (file_exists($templateFile)) {
            ob_start();
    include $templateFile;
                    $content = ob_get_clean();
                } else {
    $content = '<div class="alert alert-danger">Invalid action specified.</div>';
}

// Load base template
include ADMIN_TEMPLATE_DIR . '/base.php';
?>
