<?php
define('PROJECT_ROOT', __DIR__);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('CONTENT_DIR', PROJECT_ROOT . '/content');
define('INCLUDES_DIR', PROJECT_ROOT . '/includes');

require_once INCLUDES_DIR . '/ThemeManager.php';
require_once INCLUDES_DIR . '/Parsedown.php';

$themeManager = new ThemeManager();

$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$path = trim($path, '/');

// Default to 'home' if no path
if ($path === '') {
    $path = 'home';
}

$contentFile = CONTENT_DIR . '/' . $path . '.md';

if (!file_exists($contentFile)) {
    header('HTTP/1.0 404 Not Found');
    $contentFile = CONTENT_DIR . '/404.md';
    $is404 = true;
} else {
    $is404 = false;
}

$contentRaw = file_exists($contentFile) ? file_get_contents($contentFile) : '';
$title = ucfirst($path);

if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $contentRaw, $matches)) {
    $metadata = json_decode($matches[1], true);
    if ($metadata && isset($metadata['title'])) {
        $title = $metadata['title'];
    }
    $contentRaw = preg_replace('/^<!--\s*json\s*(.*?)\s*-->/s', '', $contentRaw);
}

$parsedown = new Parsedown();
$contentHtml = $parsedown->text($contentRaw);

if ($is404) {
    $templateName = '404';
} elseif ($path === 'home') {
    $templateName = 'home';
} else {
    $templateName = 'page';
}

try {
    $templateHtml = $themeManager->getTemplate($templateName, 'page');
} catch (Exception $e) {
    $templateHtml = "<!DOCTYPE html><html><head><title>{{title}}</title></head><body>{{content}}</body></html>";
}

// Process menus in template
if (preg_match_all('/\{\{menu=([a-zA-Z0-9_-]+)\}\}/', $templateHtml, $menuMatches)) {
    $menusFile = PROJECT_ROOT . '/admin/config/menus.json';
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

$templateHtml = str_replace('{{title}}', htmlspecialchars($title), $templateHtml);
$templateHtml = str_replace('{{content}}', $contentHtml, $templateHtml);

echo $templateHtml;
