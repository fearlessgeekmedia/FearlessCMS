<?php
// index.php - FearlessCMS front-end entry point

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('PROJECT_ROOT', __DIR__);
define('CONTENT_DIR', __DIR__ . '/content');
define('CONFIG_DIR', __DIR__ . '/config');

require_once __DIR__ . '/includes/ThemeManager.php';
require_once __DIR__ . '/includes/Parsedown.php';
require_once __DIR__ . '/includes/plugins.php';

$configFile = CONFIG_DIR . '/config.json';
$config = [];
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
}
$siteName = $config['site_name'] ?? 'FearlessCMS';

// Helper: Render a menu by ID
function render_menu($menuId) {
    $menuFile = PROJECT_ROOT . '/admin/config/menus.json';
    if (!file_exists($menuFile)) return '';
    $menus = json_decode(file_get_contents($menuFile), true);
    if (!isset($menus[$menuId])) return '';
    $menu = $menus[$menuId];
    $html = '<ul class="' . htmlspecialchars($menu['menu_class'] ?? '') . '">';
    foreach ($menu['items'] as $item) {
        $class = htmlspecialchars($item['item_class'] ?? '');
        $target = $item['target'] ? ' target="' . htmlspecialchars($item['target']) . '"' : '';
        $html .= '<li class="' . $class . '"><a href="' . htmlspecialchars($item['url']) . '"' . $target . '>' . htmlspecialchars($item['label']) . '</a></li>';
    }
    $html .= '</ul>';
    return $html;
}

// Routing: get the requested path
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// --- Plugin Routing ---
$handled = false;
$title = '';
$content = '';

// Call all route hooks (plugins)
fcms_do_hook('route', $handled, $title, $content, $path);

if ($handled) {
    // Plugin (e.g. blog) provided the content
    $pageTitle = $title;
    $contentHtml = $content;
    $is404 = false;
} else {
    // --- File-based Content Fallback ---
    if ($path === '' || $path === 'index.php') {
        $file = CONTENT_DIR . '/home.md';
    } else {
        $file = CONTENT_DIR . '/' . $path . '.md';
    }

    if (!file_exists($file)) {
        $file = CONTENT_DIR . '/404.md';
        $is404 = true;
    } else {
        $is404 = false;
    }

    if (file_exists($file)) {
        $pageContent = file_get_contents($file);

        // Extract title from frontmatter if present
        $pageTitle = '';
        if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $pageContent, $matches)) {
            $metadata = json_decode($matches[1], true);
            if ($metadata && isset($metadata['title'])) {
                $pageTitle = $metadata['title'];
            }
        }

        // Remove frontmatter from content before rendering
        $pageContent = preg_replace('/^<!--\s*json\s*.*?\s*-->\s*/s', '', $pageContent);

        // Render Markdown to HTML
        $Parsedown = new Parsedown();
        $contentHtml = $Parsedown->text($pageContent);
    } else {
        // If 404.md is missing, show a default message
        $pageTitle = 'Page Not Found';
        $contentHtml = '<h1>404 - Page Not Found</h1><p>The page you requested could not be found.</p>';
        $is404 = true;
    }
}

// --- Theme Template Rendering ---
$themeManager = new ThemeManager();
$templateName = $is404 ? '404' : 'page';
$template = $themeManager->getTemplate($templateName);

// --- Safe Menu Processing ---
// Split the template at {{content}}
$parts = explode('{{content}}', $template, 2);
$before = $parts[0];
$after = $parts[1] ?? '';

// Process menus in the template parts only (not in $contentHtml)
$before = preg_replace_callback('/\{\{menu=([\w-]+)\}\}/', function($m) {
    return render_menu($m[1]);
}, $before);
$after = preg_replace_callback('/\{\{menu=([\w-]+)\}\}/', function($m) {
    return render_menu($m[1]);
}, $after);

// Insert content and title into template
$before = str_replace('{{title}}', htmlspecialchars($pageTitle ?: ($is404 ? 'Page Not Found' : 'Untitled')), $before);
$after = str_replace('{{title}}', htmlspecialchars($pageTitle ?: ($is404 ? 'Page Not Found' : 'Untitled')), $after);

// Insert content and site name into template
$before = str_replace('{{site_name}}', htmlspecialchars($siteName), $before);
$after = str_replace('{{site_name}}', htmlspecialchars($siteName), $after);

// Output the final page
echo $before . $contentHtml . $after;
