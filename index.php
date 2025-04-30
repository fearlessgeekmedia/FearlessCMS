<?php
define('PROJECT_ROOT', __DIR__);

// Define content directory
if (!defined('CONTENT_DIR')) {
    define('CONTENT_DIR', PROJECT_ROOT . '/content');
}

require_once PROJECT_ROOT . '/includes/ThemeManager.php';
require_once PROJECT_ROOT . '/includes/plugins.php';

// Debug: Check if blog plugin is loaded
if (isset($_GET['debug'])) {
    echo "<pre>";
    echo "Active plugins: ";
    print_r(json_decode(file_exists(PLUGIN_CONFIG) ? file_get_contents(PLUGIN_CONFIG) : '[]', true));
    echo "\nRegistered hooks: ";
    print_r(array_keys($GLOBALS['fcms_hooks']));
    echo "\nRoute hook callbacks: ";
    print_r(count($GLOBALS['fcms_hooks']['route'] ?? []));
    echo "</pre>";
    exit;
}

require_once PROJECT_ROOT . '/includes/Parsedown.php';

// Get the requested URL path
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$path = trim($path, '/');
$path = empty($path) ? 'index' : $path;

// Initialize variables
$title = '';
$content = '';
$handled = false;

// Let plugins handle the route first
fcms_do_hook('route', $handled, $title, $content, $path);

// If no plugin handled the route, process it normally
if (!$handled) {
    // Your existing page loading code
    $filePath = PROJECT_ROOT . '/content/' . $path . '.md';
    if (!file_exists($filePath)) {
        $filePath = PROJECT_ROOT . '/content/index.md';
        if ($path !== 'index') {
            http_response_code(404);
            $filePath = PROJECT_ROOT . '/content/404.md';
            if (!file_exists($filePath)) {
                $title = '404 - Page Not Found';
                $content = '<p>The page you requested could not be found.</p>';
            }
        }
    }

    if (file_exists($filePath)) {
        $fileContent = file_get_contents($filePath);
        
        // Extract metadata if present
        if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $fileContent, $matches)) {
            $metadata = json_decode($matches[1], true);
            if ($metadata && isset($metadata['title'])) {
                $title = $metadata['title'];
            }
            // Remove the metadata from the content
            $fileContent = str_replace($matches[0], '', $fileContent);
        }
        
        if (empty($title)) {
            $title = ucfirst($path);
        }
        
        // Parse markdown to HTML
        $Parsedown = new Parsedown();
        $content = $Parsedown->text($fileContent);
    }
}

// Load and render the template
$themeManager = new ThemeManager();
$template = $themeManager->getTemplate('page');

// Apply hooks before rendering
fcms_do_hook('before_render', $title, $content, $template);

// Replace placeholders in the template
$template = str_replace('{{title}}', htmlspecialchars($title), $template);
$template = str_replace('{{content}}', $content, $template);

// Process menu tags
if (preg_match_all('/\{\{menu=([a-zA-Z0-9_-]+)\}\}/', $template, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $match) {
        $menuName = $match[1];
        $menuHtml = renderMenu($menuName);
        $template = str_replace($match[0], $menuHtml, $template);
    }
}

// Apply hooks after rendering
fcms_do_hook('after_render', $template);

// Output the final HTML
echo $template;

// Function to render a menu
function renderMenu($menuName) {
    $menusFile = PROJECT_ROOT . '/admin/config/menus.json';
    if (!file_exists($menusFile)) {
        return '';
    }
    
    $menus = json_decode(file_get_contents($menusFile), true);
    if (!isset($menus[$menuName])) {
        return '';
    }
    
    $menu = $menus[$menuName];
    $menuClass = !empty($menu['menu_class']) ? ' class="' . htmlspecialchars($menu['menu_class']) . '"' : '';
    
    $html = "<ul$menuClass>";
    foreach ($menu['items'] as $item) {
        $itemClass = !empty($item['item_class']) ? ' class="' . htmlspecialchars($item['item_class']) . '"' : '';
        $target = !empty($item['target']) ? ' target="' . htmlspecialchars($item['target']) . '"' : '';
        $html .= "<li$itemClass><a href=\"" . htmlspecialchars($item['url']) . "\"$target>" . htmlspecialchars($item['label']) . "</a></li>";
    }
    $html .= "</ul>";
    
    return $html;
}
?>
