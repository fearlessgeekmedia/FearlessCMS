<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('CONTENT_DIR', __DIR__ . '/content');
define('INCLUDES_DIR', __DIR__ . '/includes');

// Load dependencies
require_once INCLUDES_DIR . '/ThemeManager.php';
require_once INCLUDES_DIR . '/Parsedown.php';

// Initialize theme manager
$themeManager = new ThemeManager();

// Get the requested path
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$path = trim($path, '/');

// Default to 'home' if no path
if ($path === '') {
    $path = 'home';
}

// Determine which content file to load
$contentFile = CONTENT_DIR . '/' . $path . '.md';

// If the file doesn't exist, try 404
if (!file_exists($contentFile)) {
    header('HTTP/1.0 404 Not Found');
    $contentFile = CONTENT_DIR . '/404.md';
    $is404 = true;
} else {
    $is404 = false;
}

// Load content
$contentRaw = file_exists($contentFile) ? file_get_contents($contentFile) : '';
$title = ucfirst($path);

// Extract JSON frontmatter (for title, etc)
if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $contentRaw, $matches)) {
    $metadata = json_decode($matches[1], true);
    if ($metadata && isset($metadata['title'])) {
        $title = $metadata['title'];
    }
    // Remove frontmatter from content
    $contentRaw = preg_replace('/^<!--\s*json\s*(.*?)\s*-->/s', '', $contentRaw);
}

// Parse Markdown to HTML
$parsedown = new Parsedown();
$contentHtml = $parsedown->text($contentRaw);

// Choose template: 'home' for homepage, 'page' for others, '404' for not found
if ($is404) {
    $templateName = '404';
} elseif ($path === 'home') {
    $templateName = 'home';
} else {
    $templateName = 'page';
}

// Get template HTML
try {
    $templateHtml = $themeManager->getTemplate($templateName, 'page');
} catch (Exception $e) {
    // Fallback template
    $templateHtml = "<!DOCTYPE html><html><head><title>{{title}}</title></head><body>{{content}}</body></html>";
}

// Process menus in template
if (preg_match_all('/\{\{menu=([a-zA-Z0-9_-]+)\}\}/', $templateHtml, $menuMatches)) {
    $menusFile = __DIR__ . '/admin/config/menus.json';
    $menus = file_exists($menusFile) ? json_decode(file_get_contents($menusFile), true) : [];
    foreach ($menuMatches[1] as $i => $menuName) {
        $menuHtml = '';
        if (isset($menus[$menuName])) {
            $menuClass = $menus[$menuName]['menu_class'] ?? '';
            $menuHtml = "<ul class=\"$menuClass\">";
            foreach ($menus[$menuName]['items'] as $item) {
                $itemClass = $item['item_class'] ?? '';
                $menuHtml .= "<li class=\"$itemClass\"><a href=\"{$item['url']}\">{$item['label']}</a></li>";
            }
            $menuHtml .= "</ul>";
        }
        $templateHtml = str_replace($menuMatches[0][$i], $menuHtml, $templateHtml);
    }
}

// Replace placeholders
$templateHtml = str_replace('{{title}}', htmlspecialchars($title), $templateHtml);
$templateHtml = str_replace('{{content}}', $contentHtml, $templateHtml);

// Output the final HTML
echo $templateHtml;
