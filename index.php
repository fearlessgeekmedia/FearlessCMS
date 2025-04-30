<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('PROJECT_ROOT', __DIR__);
define('CONTENT_DIR', __DIR__ . '/content');
define('ADMIN_CONFIG_DIR', __DIR__ . '/admin/config');

require_once __DIR__ . '/includes/ThemeManager.php';
require_once __DIR__ . '/includes/Parsedown.php';

// Load menus
$menusFile = ADMIN_CONFIG_DIR . '/menus.json';
$menus = file_exists($menusFile) ? json_decode(file_get_contents($menusFile), true) : [];

// Get requested page from URL (e.g. /about -> about.md)
$requestUri = $_SERVER['REQUEST_URI'];
$pageSlug = trim(parse_url($requestUri, PHP_URL_PATH), '/');
if ($pageSlug === '' || $pageSlug === 'index.php') {
    $pageSlug = 'home';
}
$contentFile = CONTENT_DIR . '/' . $pageSlug . '.md';

// Load content or 404
if (file_exists($contentFile)) {
    $rawContent = file_get_contents($contentFile);

    // Extract title from JSON frontmatter if present
    $title = ucfirst($pageSlug);
    $content = $rawContent;
    if (preg_match('/^<!--\s*json\s*(.*?)\s*-->\s*/s', $rawContent, $matches)) {
        $metadata = json_decode($matches[1], true);
        if ($metadata && isset($metadata['title'])) {
            $title = $metadata['title'];
        }
        // Remove frontmatter from content
        $content = preg_replace('/^<!--\s*json\s*(.*?)\s*-->\s*/s', '', $rawContent, 1);
    }

    // Parse Markdown to HTML
    $Parsedown = new Parsedown();
    $content = $Parsedown->text($content);

    // Load theme template
    $themeManager = new ThemeManager();
    $template = $themeManager->getTemplate('page');
} else {
    // 404
    $title = 'Page Not Found';
    $content = '<p>The page you requested could not be found.</p><p><a href="/">Return to Home</a></p>';
    $themeManager = new ThemeManager();
    $template = $themeManager->getTemplate('404', 'page');
}

// --- Menu rendering function ---
function render_menu($menu) {
    if (empty($menu['items'])) return '';
    $html = '<ul' . (!empty($menu['menu_class']) ? ' class="' . htmlspecialchars($menu['menu_class']) . '"' : '') . '>';
    foreach ($menu['items'] as $item) {
        $target = !empty($item['target']) ? ' target="' . htmlspecialchars($item['target']) . '"' : '';
        $class = !empty($item['item_class']) ? ' class="' . htmlspecialchars($item['item_class']) . '"' : '';
        $html .= '<li><a href="' . htmlspecialchars($item['url']) . '"' . $class . $target . '>' . htmlspecialchars($item['label']) . '</a></li>';
    }
    $html .= '</ul>';
    return $html;
}

// Replace all menu placeholders in the template
$template = preg_replace_callback('/\{\{menu=([a-zA-Z0-9_-]+)\}\}/', function($matches) use ($menus) {
    $menuName = $matches[1];
    return isset($menus[$menuName]) ? render_menu($menus[$menuName]) : '';
}, $template);

// Replace other placeholders
$template = str_replace('{{title}}', htmlspecialchars($title), $template);
$template = str_replace('{{content}}', $content, $template);

// Output the final page
echo $template;
