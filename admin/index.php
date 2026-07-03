<?php
/**
 * FearlessCMS Admin Index - Minimal debug version
 */
define('ADMIN_MODE', true);

// Load early POST handlers
require_once __DIR__ . '/includes/post-handlers.php';

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/ThemeManager.php';
require_once dirname(__DIR__) . '/includes/plugins.php';
require_once dirname(__DIR__) . '/includes/CMSModeManager.php';
require_once dirname(__DIR__) . '/includes/CacheManager.php';

// Create managers
$cmsModeManager = new CMSModeManager();
$GLOBALS['cmsModeManager'] = $cmsModeManager;
$themeManager = new ThemeManager(THEMES_DIR);
$cacheManager = new CacheManager();
$GLOBALS['cacheManager'] = $cacheManager;

if (!isLoggedIn()) {
    $redirectAdminPath = $adminPath ?? 'admin';
    header('Location: /' . $redirectAdminPath . '/login');
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';
$pageTitle = ucwords(str_replace(['_', '-'], ' ', $action));

// Load content handlers (deletion, etc.)
$contentHandlersFile = __DIR__ . '/includes/content-handlers.php';
if (file_exists($contentHandlersFile)) {
    require_once $contentHandlersFile;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $postAction = $_POST['action'];
    if ($postAction === 'logout') {
        logout();
        $redirectAdminPath = $adminPath ?? 'admin';
        header('Location: /' . $redirectAdminPath . '/login');
        exit;
    }
    require_once __DIR__ . '/includes/site-handlers.php';
    require_once __DIR__ . '/includes/save-handlers.php';
}

// Calculate total pages and load content list for dashboard and content management
$totalPages = 0;
$contentList = [];

require_once PROJECT_ROOT . '/includes/DemoModeManager.php';
$demoManager = new DemoModeManager();
$isDemoUser = $demoManager->isDemoSession() || $demoManager->isDemoUserSession();
if (!$isDemoUser && isset($_SESSION['username']) && $_SESSION['username'] === 'demo') {
    $isDemoUser = true;
}

$countContentDir = $isDemoUser ? $demoManager->getDemoContentDir() : CONTENT_DIR;

if (is_dir($countContentDir)) {
    // Glob for count
    $files = array_merge(glob($countContentDir . '/*.md'), glob($countContentDir . '/*.html'));
    $totalPages = is_array($files) ? count($files) : 0;

    // Recursively get all .md and .html files for content list
    try {
        $filesIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($countContentDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $filesIterator = new RegexIterator($filesIterator, '/\\.(md|html)$/');

        foreach ($filesIterator as $file) {
            if (strpos($file->getPathname(), '/content/_preview/') !== false) {
                continue;
            }

            $fileContent = file_get_contents($file->getPathname());
            $title = '';
            if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $fileContent, $matches)) {
                $metadata = json_decode($matches[1], true);
                if ($metadata && isset($metadata['title'])) {
                    $title = $metadata['title'];
                }
            }
            if (!$title) {
                $basename = $file->getBasename();
                $basename = preg_replace('/\\.(md|html)$/', '', $basename);
                $title = ucwords(str_replace(['-', '_'], ' ', $basename));
            }

            $relativePath = str_replace($countContentDir . '/', '', $file->getPathname());
            $path = preg_replace('/\\.(md|html)$/', '', $relativePath);

            $contentList[] = [
                'title' => $title,
                'path' => $path,
                'modified' => date('Y-m-d H:i:s', $file->getMTime())
            ];
        }

        // Sort by modified date descending
        usort($contentList, function($a, $b) {
            return strtotime($b['modified']) - strtotime($a['modified']);
        });
    } catch (Exception $e) {
        error_log("Error generating contentList: " . $e->getMessage());
    }
}

// Load active plugins
$activePlugins = file_exists(PLUGIN_CONFIG) ? json_decode(file_get_contents(PLUGIN_CONFIG), true) : [];
if (!is_array($activePlugins)) {
    $activePlugins = [];
}

// Map actions to their template files
$template_map = [
    'dashboard' => 'dashboard.php',
    'manage_content' => 'content-management.php',
    'manage_plugins' => 'plugins.php',
    'manage_themes' => 'themes.php',
    'manage_menus' => 'menus.php',
    'manage_cache_settings' => 'cache-settings.php',
    'edit_content' => 'edit_content_quill.php',
    'new_content' => 'new_content.php',
    'create_page' => 'new_content.php',
    'manage_users' => 'users.php',
    'manage_roles' => 'role-management.html',
    'manage_widgets' => 'widgets.php',
    'site_settings' => 'site-settings.php',
    'updates' => 'updater.php'
];

$templateFile = ADMIN_TEMPLATE_DIR . '/' . ($template_map[$action] ?? $action . '.php');

if ($action === 'edit_content' && !empty($_GET['path'])) {
    $path = preg_replace('/\.(md|html)$/i', '', $_GET['path']);
    $contentFile = null;
    foreach (['.html', '.md'] as $ext) {
        $candidate = CONTENT_DIR . '/' . $path . $ext;
        if (file_exists($candidate)) {
            $contentFile = $candidate;
            break;
        }
    }

    $contentData = $contentFile ? file_get_contents($contentFile) : '';
    $title = '';
    $currentTemplate = 'page-with-sidebar';
    $editorMode = 'html';

    if (!empty($contentData) && preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $contentData, $matches)) {
        $metadata = json_decode($matches[1], true);
        if ($metadata && is_array($metadata)) {
            $title = $metadata['title'] ?? '';
            $currentTemplate = $metadata['template'] ?? $currentTemplate;
            $editorMode = $metadata['editor_mode'] ?? $editorMode;
        }
    }
    if ($editorMode === 'html' && str_ends_with($contentFile, '.md')) {
        $editorMode = 'markdown';
    }

    if (!$title) {
        $title = ucwords(str_replace(['-', '_'], ' ', basename($path)));
    }

    $templates = [];
    $templateDir = PROJECT_ROOT . '/themes/' . ($themeManager->getActiveTheme() ?? 'default') . '/templates';
    if (is_dir($templateDir)) {
        foreach (glob($templateDir . '/*.html') as $template) {
            $templateName = basename($template, '.html');
            if ($templateName !== '404' && !str_ends_with($template, '.html.mod')) {
                $templates[] = $templateName;
            }
        }
    }

    if (empty($templates)) {
        $templates = ['page-with-sidebar', 'page', 'home', 'blog', 'documentation'];
    }

    if (in_array('page-with-sidebar', $templates)) {
        $templates = array_merge(['page-with-sidebar'], array_filter($templates, function($t) { return $t !== 'page-with-sidebar'; }));
    }
}

$menu_options = '';
$selectedMenuId = $_GET['menu_id'] ?? '';
$menuFile = CONFIG_DIR . '/menus.json';
$menus = file_exists($menuFile) ? json_decode(file_get_contents($menuFile), true) : [];
if (is_array($menus)) {
    foreach ($menus as $menuId => $menu) {
        $label = isset($menu['label']) ? $menu['label'] : ucwords(str_replace(['_', '-'], ' ', $menuId));
        $selected = ($selectedMenuId === $menuId) ? ' selected' : '';
        $menu_options .= '<option value="' . htmlspecialchars($menuId) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
}
global $menu_options;

// Check if this is a registered admin section
$admin_sections = fcms_get_admin_sections();
$section_found = false;
$content = '';

if (isset($admin_sections[$action])) {
    if (isset($admin_sections[$action]['render_callback']) && is_callable($admin_sections[$action]['render_callback'])) {
        ob_start();
        $section_content = call_user_func($admin_sections[$action]['render_callback']);
        $content = ob_get_clean();
        if (empty($content) && !empty($section_content) && is_string($section_content)) {
            $content = $section_content;
        }
        $section_found = true;
        $templateFile = null;
    }
} else {
    foreach ($admin_sections as $parent_id => $parent) {
        if (isset($parent['children']) && isset($parent['children'][$action])) {
            if (isset($parent['children'][$action]['render_callback']) && is_callable($parent['children'][$action]['render_callback'])) {
                ob_start();
                $section_content = call_user_func($parent['children'][$action]['render_callback']);
                $content = ob_get_clean();
                if (empty($content) && !empty($section_content) && is_string($section_content)) {
                    $content = $section_content;
                }
                $section_found = true;
                $templateFile = null;
                break;
            }
        }
    }
}

if (!$section_found) {
    if ($action === 'demo_mode') {
        $demoTemplateFile = ADMIN_TEMPLATE_DIR . '/demo-mode.php';
        if (file_exists($demoTemplateFile)) {
            $templateFile = $demoTemplateFile;
        } else {
            $content = '<div class="alert alert-danger">Demo mode template not found.</div>';
            $templateFile = null;
        }
    } elseif (!file_exists($templateFile)) {
        $content = '<div class="alert alert-danger">Invalid action specified.</div>';
        $templateFile = null;
    } else {
        if (str_ends_with($templateFile, '.html')) {
            $templateContent = file_get_contents($templateFile);
            // Replace templates placeholder
            $replacements = [
                '{{site_name}}' => $siteName ?? 'FearlessCMS',
                '{{site_description}}' => $siteDescription ?? '',
                '{{total_pages}}' => $totalPages ?? 0,
                '{{active_plugins}}' => count($activePlugins)
            ];
            $content = str_replace(array_keys($replacements), array_values($replacements), $templateContent);
            $templateFile = null;
        }
    }
}

$username = $_SESSION['username'] ?? '';
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

if (isset($_GET['error']) && $_GET['error'] === 'access_denied') {
    $error = 'Access denied. This feature is not available in the current CMS mode.';
}

// Include base template
include ADMIN_TEMPLATE_DIR . '/base.php';
