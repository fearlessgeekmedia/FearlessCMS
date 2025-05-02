<?php
// index.php (front-end entry point for FearlessCMS)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('PROJECT_ROOT', __DIR__);
define('CONTENT_DIR', __DIR__ . '/content');
define('CONFIG_DIR', __DIR__ . '/config');

require_once __DIR__ . '/includes/ThemeManager.php';
require_once __DIR__ . '/includes/Parsedown.php';

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

// Default to 'home.md' for home
if ($path === '' || $path === 'index.php') {
    $file = CONTENT_DIR . '/home.md';
} else {
    $file = CONTENT_DIR . '/' . $path . '.md';
}

// 404 fallback
if (!file_exists($file)) {
    $file = CONTENT_DIR . '/404.md';
    $is404 = true;
} else {
    $is404 = false;
}

// Load content
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

// Load theme template
$themeManager = new ThemeManager();
$templateName = $is404 ? '404' : 'page';
$template = $themeManager->getTemplate($templateName);

// --- THE IMPORTANT PART ---
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

// Output the final page
echo $before . $contentHtml . $after;
