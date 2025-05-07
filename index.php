<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('PROJECT_ROOT', __DIR__);
define('CONTENT_DIR', __DIR__ . '/content');
define('CONFIG_DIR', __DIR__ . '/config');

require_once PROJECT_ROOT . '/includes/ThemeManager.php';
require_once PROJECT_ROOT . '/includes/plugins.php';

// --- Routing: get the requested path ---
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = urldecode($uri);
$path = trim($uri, '/');

// Default to home if root
if ($path === '') {
    $path = 'home';
}

// Try direct path first
$contentFile = CONTENT_DIR . '/' . $path . '.md';

// If not found, try parent/child relationship
if (!file_exists($contentFile)) {
    $parts = explode('/', $path);
    if (count($parts) > 1) {
        $childPath = array_pop($parts);
        $parentPath = implode('/', $parts);
        
        // Check if parent exists
        $parentFile = CONTENT_DIR . '/' . $parentPath . '.md';
        if (file_exists($parentFile)) {
            $parentContent = file_get_contents($parentFile);
            $parentMetadata = [];
            if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $parentContent, $matches)) {
                $parentMetadata = json_decode($matches[1], true);
            }
            
            // Check if this is a child page
            $childFile = CONTENT_DIR . '/' . $childPath . '.md';
            if (file_exists($childFile)) {
                $childContent = file_get_contents($childFile);
                $childMetadata = [];
                if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $childContent, $matches)) {
                    $childMetadata = json_decode($matches[1], true);
                }
                
                // If child has this parent, use it
                if (isset($childMetadata['parent']) && $childMetadata['parent'] === $parentPath) {
                    $contentFile = $childFile;
                }
            }
        }
    }
}

// 404 fallback
if (!file_exists($contentFile)) {
    http_response_code(404);
    $contentFile = CONTENT_DIR . '/404.md';
    if (!file_exists($contentFile)) {
        // If no 404.md, show a default message
        $pageTitle = 'Page Not Found';
        $pageContent = '<p>The page you requested could not be found.</p>';
        $themeManager = new ThemeManager();
        $template = $themeManager->getTemplate('404', 'page');
        $template = str_replace('{{title}}', $pageTitle, $template);
        $template = str_replace('{{content}}', $pageContent, $template);
        $template = str_replace('{{site_name}}', 'FearlessCMS', $template);

        // Logo/Herobanner replacement (even on 404)
        $themeOptionsFile = CONFIG_DIR . '/theme_options.json';
        $themeOptions = file_exists($themeOptionsFile) ? json_decode(file_get_contents($themeOptionsFile), true) : [];
        $logoHtml = !empty($themeOptions['logo']) ? '<img src="' . htmlspecialchars($themeOptions['logo']) . '" class="logo" alt="Logo">' : '';
        $herobannerHtml = !empty($themeOptions['herobanner']) ? '<img src="' . htmlspecialchars($themeOptions['herobanner']) . '" class="hero-banner" alt="Hero Banner">' : '';
        $template = str_replace('{{logo}}', $logoHtml, $template);
        $template = str_replace('{{herobanner}}', $herobannerHtml, $template);

        echo $template;
        exit;
    }
}

// --- Load content and metadata ---
$fileContent = file_get_contents($contentFile);
$pageTitle = '';
$pageDescription = '';
$pageContent = $fileContent;

// Extract JSON frontmatter if present
if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $fileContent, $matches)) {
    $metadata = json_decode($matches[1], true);
    if ($metadata) {
        if (isset($metadata['title'])) $pageTitle = $metadata['title'];
        if (isset($metadata['description'])) $pageDescription = $metadata['description'];
    }
    // Remove frontmatter from content
    $pageContent = preg_replace('/^<!--\s*json\s*.*?\s*-->\s*/s', '', $fileContent);
}

// Fallback to filename as title if not set
if (!$pageTitle) {
    $pageTitle = ucwords(str_replace(['-', '_'], ' ', basename($path)));
}

// --- Markdown rendering ---
if (!class_exists('Parsedown')) {
    require_once PROJECT_ROOT . '/includes/Parsedown.php';
}
$Parsedown = new Parsedown();
$pageContentHtml = $Parsedown->text($pageContent);

// --- Theme and template ---
$themeManager = new ThemeManager();
$template = $themeManager->getTemplate('page', 'page');

// --- Menu rendering (for {{menu=main}} etc.) ---
function render_menu($menuId = 'main') {
    $menuFile = CONFIG_DIR . '/menus.json';
    if (!file_exists($menuFile)) return '';
    $menus = json_decode(file_get_contents($menuFile), true);
    if (!isset($menus[$menuId]['items'])) return '';
    $html = '<ul class="' . htmlspecialchars($menus[$menuId]['menu_class'] ?? 'main-nav') . '">';
    foreach ($menus[$menuId]['items'] as $item) {
        $label = htmlspecialchars($item['label']);
        $url = htmlspecialchars($item['url']);
        $class = htmlspecialchars($item['item_class'] ?? '');
        $target = $item['target'] ? ' target="' . htmlspecialchars($item['target']) . '"' : '';
        $html .= "<li><a href=\"$url\" class=\"$class\"$target>$label</a></li>";
    }
    $html .= '</ul>';
    return $html;
}

// --- Replace template placeholders ---

// Title, content, site name
$template = str_replace('{{title}}', htmlspecialchars($pageTitle), $template);
$template = str_replace('{{content}}', $pageContentHtml, $template);

$configFile = CONFIG_DIR . '/config.json';
$siteName = 'FearlessCMS';
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if (isset($config['site_name'])) $siteName = $config['site_name'];
}
$template = str_replace('{{site_name}}', htmlspecialchars($siteName), $template);

// Menus
$template = preg_replace_callback('/\{\{menu=([a-zA-Z0-9_-]+)\}\}/', function($m) {
    return render_menu($m[1]);
}, $template);

// --- Theme options: logo and herobanner ---
$themeOptionsFile = CONFIG_DIR . '/theme_options.json';
$themeOptions = file_exists($themeOptionsFile) ? json_decode(file_get_contents($themeOptionsFile), true) : [];

$logoHtml = '';
if (!empty($themeOptions['logo'])) {
    $logoHtml = '<img src="' . htmlspecialchars($themeOptions['logo']) . '" class="logo" alt="Logo">';
}
$herobannerHtml = '';
if (!empty($themeOptions['herobanner'])) {
    $herobannerHtml = '<img src="' . htmlspecialchars($themeOptions['herobanner']) . '" class="hero-banner" alt="Hero Banner">';
}

$template = str_replace('{{logo}}', $logoHtml, $template);
$template = str_replace('{{herobanner}}', $herobannerHtml, $template);

// --- SEO plugin hook (optional) ---
global $title, $content;
$title = $pageTitle;
$content = $fileContent;
fcms_do_hook('before_render', $template);

// --- Output ---
echo $template;
