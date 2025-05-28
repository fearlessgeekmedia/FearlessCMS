<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('PROJECT_ROOT', __DIR__);
define('CONTENT_DIR', __DIR__ . '/content');
define('CONFIG_DIR', __DIR__ . '/config');

require_once PROJECT_ROOT . '/includes/ThemeManager.php';
require_once PROJECT_ROOT . '/includes/MenuManager.php';
require_once PROJECT_ROOT . '/includes/WidgetManager.php';
require_once PROJECT_ROOT . '/includes/TemplateRenderer.php';
require_once PROJECT_ROOT . '/includes/plugins.php';

// --- Routing: get the requested path ---
$requestPath = trim($_SERVER['REQUEST_URI'], '/');
error_log("Request path: " . $requestPath);

// Remove query parameters from the path
if (($queryPos = strpos($requestPath, '?')) !== false) {
    $requestPath = substr($requestPath, 0, $queryPos);
    error_log("Path after removing query parameters: " . $requestPath);
}

// Remove any subdomain prefix if present
if (strpos($requestPath, 'fearlesscms.hstn.me/') === 0) {
    $requestPath = substr($requestPath, strlen('fearlesscms.hstn.me/'));
}

// Handle preview URLs
if (strpos($requestPath, '_preview/') === 0) {
    $previewPath = substr($requestPath, 9); // Remove '_preview/' prefix
    $previewFile = CONTENT_DIR . '/_preview/' . $previewPath . '.md';
    
    error_log("Looking for preview file: " . $previewFile);
    
    if (file_exists($previewFile)) {
        error_log("Preview file found");
        $contentData = file_get_contents($previewFile);
        error_log("Preview content loaded: " . substr($contentData, 0, 100) . "...");
        
        $metadata = [];
        if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $contentData, $matches)) {
            $metadata = json_decode($matches[1], true);
            $content = substr($contentData, strlen($matches[0]));
            error_log("Preview metadata: " . json_encode($metadata));
        } else {
            error_log("No metadata found in preview file");
            $content = $contentData;
        }
        
        // Set page title
        $pageTitle = $metadata['title'] ?? 'Preview';
        
        // Convert markdown to HTML
        require_once PROJECT_ROOT . '/includes/Parsedown.php';
        $parsedown = new Parsedown();
        $pageContentHtml = $parsedown->text($content);
        
        // Get site name from config
        $configFile = CONFIG_DIR . '/config.json';
        $siteName = 'FearlessCMS';
        $custom_css = '';
        $custom_js = '';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            if (isset($config['site_name'])) {
                $siteName = $config['site_name'];
            }
            if (isset($config['custom_css'])) {
                $custom_css = $config['custom_css'];
            }
            if (isset($config['custom_js'])) {
                $custom_js = $config['custom_js'];
            }
        }
        
        // Load theme options
        $themeOptionsFile = CONFIG_DIR . '/theme_options.json';
        $themeOptions = file_exists($themeOptionsFile) ? json_decode(file_get_contents($themeOptionsFile), true) : [];
        
        // Initialize managers
        require_once PROJECT_ROOT . '/includes/ThemeManager.php';
        require_once PROJECT_ROOT . '/includes/MenuManager.php';
        require_once PROJECT_ROOT . '/includes/WidgetManager.php';
        require_once PROJECT_ROOT . '/includes/TemplateRenderer.php';
        
        $themeManager = new ThemeManager();
        $menuManager = new MenuManager();
        $widgetManager = new WidgetManager();
        
        // Initialize template renderer
        $templateRenderer = new TemplateRenderer(
            $themeManager->getActiveTheme(),
            $themeOptions,
            $menuManager,
            $widgetManager
        );
        
        // Prepare template data
        $templateData = [
            'title' => $pageTitle,
            'content' => $pageContentHtml,
            'siteName' => $siteName,
            'currentYear' => date('Y'),
            'logo' => $themeOptions['logo'] ?? null,
            'heroBanner' => $themeOptions['herobanner'] ?? null,
            'mainMenu' => $menuManager->renderMenu('main'),
            'custom_css' => $custom_css,
            'custom_js' => $custom_js
        ];
        
        error_log("Template data prepared: " . json_encode($templateData));
        
        // Render template
        $templateName = $metadata['template'] ?? 'page';
        fcms_do_hook('before_render', $templateName);
        $template = $templateRenderer->render($templateName, $templateData);
        
        // Output the preview
        echo $template;
        exit;
    } else {
        error_log("Preview file not found: " . $previewFile);
        // If preview file doesn't exist, show 404
        http_response_code(404);
        $pageTitle = 'Preview Not Found';
        $pageContent = '<p>The preview you requested could not be found.</p>';
        
        require_once PROJECT_ROOT . '/includes/ThemeManager.php';
        $themeManager = new ThemeManager();
        $template = $themeManager->getTemplate('404', 'page');
        $template = str_replace('{{title}}', $pageTitle, $template);
        $template = str_replace('{{content}}', $pageContent, $template);
        $template = str_replace('{{site_name}}', 'FearlessCMS', $template);
        echo $template;
        exit;
    }
}

// Default to home if root
if ($requestPath === '') {
    $path = 'home';
} else {
    $path = $requestPath;
}
error_log("Processed path: " . $path);

// Initialize variables for plugin handling
$handled = false;
$title = '';
$content = '';

// Let plugins handle the route first
fcms_do_hook('route', $handled, $title, $content, $path);

// If a plugin handled the route, render its content
if ($handled) {
    // Get site name from config
    $configFile = CONFIG_DIR . '/config.json';
    $siteName = 'FearlessCMS';
    $custom_css = '';
    $custom_js = '';
    if (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true);
        if (isset($config['site_name'])) {
            $siteName = $config['site_name'];
        }
        if (isset($config['custom_css'])) {
            $custom_css = $config['custom_css'];
        }
        if (isset($config['custom_js'])) {
            $custom_js = $config['custom_js'];
        }
    }
    
    // Load theme options
    $themeOptionsFile = CONFIG_DIR . '/theme_options.json';
    $themeOptions = file_exists($themeOptionsFile) ? json_decode(file_get_contents($themeOptionsFile), true) : [];
    
    // Initialize managers
    require_once PROJECT_ROOT . '/includes/ThemeManager.php';
    require_once PROJECT_ROOT . '/includes/MenuManager.php';
    require_once PROJECT_ROOT . '/includes/WidgetManager.php';
    require_once PROJECT_ROOT . '/includes/TemplateRenderer.php';
    
    $themeManager = new ThemeManager();
    $menuManager = new MenuManager();
    $widgetManager = new WidgetManager();
    
    // Initialize template renderer
    $templateRenderer = new TemplateRenderer(
        $themeManager->getActiveTheme(),
        $themeOptions,
        $menuManager,
        $widgetManager
    );
    
    // Let plugins determine the template
    $template = 'page';
    fcms_do_hook('before_render', $template, $path);
    
    // Prepare template data
    $templateData = [
        'title' => $title,
        'content' => $content,
        'siteName' => $siteName,
        'currentYear' => date('Y'),
        'logo' => $themeOptions['logo'] ?? null,
        'heroBanner' => $themeOptions['herobanner'] ?? null,
        'mainMenu' => $menuManager->renderMenu('main'),
        'custom_css' => $custom_css,
        'custom_js' => $custom_js
    ];
    
    // Render template
    echo $templateRenderer->render($template, $templateData);
    exit;
}

// Try direct path first
$contentFile = CONTENT_DIR . '/' . $path . '.md';
error_log("Looking for content file: " . $contentFile);

// If not found, try parent/child relationship
if (!file_exists($contentFile)) {
    error_log("Content file not found, trying parent/child relationship");
    $parts = explode('/', $path);
    if (count($parts) > 1) {
        $childPath = array_pop($parts);
        $parentPath = implode('/', $parts);
        error_log("Parent path: " . $parentPath . ", Child path: " . $childPath);
        
        // Check if parent exists
        $parentFile = CONTENT_DIR . '/' . $parentPath . '.md';
        error_log("Looking for parent file: " . $parentFile);
        if (file_exists($parentFile)) {
            error_log("Parent file found");
            $parentContent = file_get_contents($parentFile);
            $parentMetadata = [];
            if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $parentContent, $matches)) {
                $parentMetadata = json_decode($matches[1], true);
            }
            
            // Check if this is a child page
            $childFile = CONTENT_DIR . '/' . $childPath . '.md';
            error_log("Looking for child file: " . $childFile);
            if (file_exists($childFile)) {
                error_log("Child file found");
                $childContent = file_get_contents($childFile);
                $childMetadata = [];
                if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $childContent, $matches)) {
                    $childMetadata = json_decode($matches[1], true);
                }
                
                // If child has this parent, use it
                if (isset($childMetadata['parent']) && $childMetadata['parent'] === $parentPath) {
                    $contentFile = $childFile;
                    error_log("Using child file as content file");
                }
            }
        }
    }
}

// 404 fallback
if (!file_exists($contentFile)) {
    error_log("No content file found, showing 404");
    http_response_code(404);
    $contentFile = CONTENT_DIR . '/404.md';
    error_log("Looking for 404 file: " . $contentFile);
    if (!file_exists($contentFile)) {
        error_log("No 404 file found, showing default 404 message");
        // If no 404.md, show a default message
        $pageTitle = 'Page Not Found';
        $pageContent = '<p>The page you requested could not be found.</p>';
        
        // Initialize managers
        $themeManager = new ThemeManager();
        $menuManager = new MenuManager();
        $widgetManager = new WidgetManager();
        
        // Load theme options
        $themeOptionsFile = CONFIG_DIR . '/theme_options.json';
        $themeOptions = file_exists($themeOptionsFile) ? json_decode(file_get_contents($themeOptionsFile), true) : [];
        
        // Initialize template renderer
        $templateRenderer = new TemplateRenderer(
            $themeManager->getActiveTheme(),
            $themeOptions,
            $menuManager,
            $widgetManager
        );
        
        // Prepare template data
        $templateData = [
            'title' => $pageTitle,
            'content' => $pageContent,
            'siteName' => 'FearlessCMS',
            'currentYear' => date('Y'),
            'logo' => $themeOptions['logo'] ?? null,
            'heroBanner' => $themeOptions['herobanner'] ?? null,
            'mainMenu' => $menuManager->renderMenu('main'),
            'custom_css' => '',
            'custom_js' => ''
        ];
        
        // Render template
        echo $templateRenderer->render('404', $templateData);
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

// --- Get site name ---
$configFile = CONFIG_DIR . '/config.json';
$siteName = 'FearlessCMS';
$custom_css = '';
$custom_js = '';
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if (isset($config['site_name'])) {
        $siteName = $config['site_name'];
    }
    if (isset($config['custom_css'])) {
        $custom_css = $config['custom_css'];
    }
    if (isset($config['custom_js'])) {
        $custom_js = $config['custom_js'];
    }
}

// --- Get theme options ---
$themeOptionsFile = CONFIG_DIR . '/theme_options.json';
$themeOptions = file_exists($themeOptionsFile) ? json_decode(file_get_contents($themeOptionsFile), true) : [];

require_once PROJECT_ROOT . '/includes/MenuManager.php';
require_once PROJECT_ROOT . '/includes/WidgetManager.php';
require_once PROJECT_ROOT . '/includes/TemplateRenderer.php';

$menuManager = new MenuManager();
$widgetManager = new WidgetManager();
$templateRenderer = new TemplateRenderer(
    $themeManager->getActiveTheme(),
    $themeOptions,
    $menuManager,
    $widgetManager
);

// --- Prepare template data ---
$templateData = [
    'title' => $pageTitle,
    'content' => $pageContentHtml,
    'siteName' => $siteName,
    'currentYear' => date('Y'),
    'logo' => $themeOptions['logo'] ?? null,
    'heroBanner' => $themeOptions['herobanner'] ?? null,
    'mainMenu' => $menuManager->renderMenu('main'),
    'custom_css' => $custom_css,
    'custom_js' => $custom_js
];

// --- Render template ---
$templateName = $metadata['template'] ?? 'page';
fcms_do_hook('before_render', $templateName);
$template = $templateRenderer->render($templateName, $templateData);

// --- Output ---
echo $template;
