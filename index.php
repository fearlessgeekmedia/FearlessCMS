<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/lib/Parsedown.php';
$Parsedown = new Parsedown();


define('ADMIN_CONFIG_DIR', __DIR__ . '/admin/config');
define('THEME_DIR', __DIR__ . '/themes/default/templates');
define('CONTENT_DIR', __DIR__ . '/content');

// Menu rendering function
function renderMenu($menuName) {
    $menusFile = ADMIN_CONFIG_DIR . '/menus.json';
    if (!file_exists($menusFile)) return '';
    $menus = json_decode(file_get_contents($menusFile), true);
    if (!isset($menus[$menuName])) return '';
    $menu = $menus[$menuName];
    $menuClass = htmlspecialchars($menu['menu_class'] ?? '');
    $html = "<nav class=\"$menuClass\"><ul>";
    foreach ($menu['items'] as $item) {
        $label = htmlspecialchars($item['label']);
        $url = htmlspecialchars($item['url']);
        $itemClass = htmlspecialchars($item['item_class'] ?? '');
        $html .= "<li class=\"$itemClass\"><a href=\"$url\">$label</a></li>";
    }
    $html .= "</ul></nav>";
    return $html;
}

// Determine which page to load (supports both ?page=... and pretty URLs)
if (isset($_GET['page']) && $_GET['page'] !== '') {
    $page = $_GET['page'];
} else {
    // Parse path from REQUEST_URI for pretty URLs
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $page = trim($path, '/');
    $page = preg_replace('/\.html?$/', '', $page);
    if ($page === '' || $page === 'index.php') $page = 'home';
}
$contentFile = CONTENT_DIR . "/$page.md";

// Load content
if (!file_exists($contentFile)) {
    $content = "Page not found!";
    $title = "404";
} else {
    $content = file_get_contents($contentFile);
    $title = ucfirst($page);
}

// Load theme template
$templateFile = THEME_DIR . '/page.html';
if (!file_exists($templateFile)) {
    die("Theme template not found.");
}
$template = file_get_contents($templateFile);

// Replace placeholders
$template = str_replace('{{title}}', htmlspecialchars($title), $template);
$template = str_replace('{{content}}', $Parsedown->text($content), $template);

// Replace all menu tags like {{menu=main}}
$template = preg_replace_callback('/\{\{menu=([a-zA-Z0-9_-]+)\}\}/', function($matches) {
    return renderMenu($matches[1]);
}, $template);

echo $template;
