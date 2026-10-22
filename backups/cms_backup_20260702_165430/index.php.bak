<?php
// Set error reporting based on debug mode
ini_set('log_errors', 1);

// Only enable debug mode if explicitly requested
if (getenv('FCMS_DEBUG') === 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
}

// Initialize session first
require_once __DIR__ . '/includes/session.php';

// Only log debug info in debug mode
if (getenv('FCMS_DEBUG') === 'true') {
    error_log("Main index - Session ID: " . (function_exists('session_id') ? session_id() : 'function_not_available'));
    // Session debugging removed for security
    // Cookie debugging removed for security
}

require_once __DIR__ . '/includes/config.php';
require_once PROJECT_ROOT . '/includes/ThemeManager.php';
require_once PROJECT_ROOT . '/includes/MenuManager.php';
require_once PROJECT_ROOT . '/includes/WidgetManager.php';
require_once PROJECT_ROOT . '/includes/TemplateRenderer.php';
require_once PROJECT_ROOT . '/includes/plugins.php';

// --- Routing: get the requested path ---
$requestPath = trim($_SERVER['REQUEST_URI'], '/');
if (getenv('FCMS_DEBUG') === 'true') {
    error_log("Request path: " . $requestPath);
    error_log("Raw REQUEST_URI: " . $_SERVER['REQUEST_URI']);
}

// Remove query parameters from the path
if (($queryPos = strpos($requestPath, '?')) !== false) {
    $requestPath = substr($requestPath, 0, $queryPos);
    if (getenv('FCMS_DEBUG') === 'true') {
        error_log("Path after removing query parameters: " . $requestPath);
    }
}

// Remove any subdomain prefix if present

// Load configuration for admin routing
$configFile = CONFIG_DIR . "/config.json";
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$adminPath = $config["admin_path"] ?? "admin";

// Admin routes are now handled by router.php, so we don't need this logic here
// The router.php will send admin routes to admin/index.php or admin/login.php as appropriate

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
            // error_log("Preview metadata: " . json_encode($metadata));
        } else {
            error_log("No metadata found in preview file");
            $content = $contentData;
        }

        // Set page title
        $pageTitle = $metadata['title'] ?? 'Preview';

        // Check editor mode to determine content processing
        $editorMode = $metadata['editor_mode'] ?? 'markdown';

        // Convert markdown to HTML or use HTML directly
        if ($editorMode === 'easy' || $editorMode === 'html') {
            // Use content as-is for HTML mode
            $pageContentHtml = $content;
        } else {
            // Convert markdown to HTML
            require_once PROJECT_ROOT . '/includes/Parsedown.php';
                    $Parsedown = new Parsedown();
        $Parsedown->setMarkupEscaped(false); // Allow HTML in markdown
        $pageContentHtml = $Parsedown->text($content);
        }

        // Get site name from config
        $configFile = CONFIG_DIR . '/config.json';
        $siteName = 'FearlessCMS';
        $siteDescription = '';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            if (isset($config['site_name'])) {
                $siteName = $config['site_name'];
            }
            if (isset($config['site_description'])) {
                $siteDescription = $config['site_description'];
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
            'siteDescription' => $siteDescription,
            'currentYear' => date('Y'),
            'logo' => $themeOptions['logo'] ?? null,
            'heroBanner' => $themeOptions['herobanner'] ?? null,
            'mainMenu' => $menuManager->renderMenu('main'),

        ];

        // Add custom variables from JSON frontmatter
        if (isset($metadata) && is_array($metadata)) {
            foreach ($metadata as $key => $value) {
                $templateData[$key] = $value;
            }
        }

        // Process template variables in the content
        $pageContentHtml = $templateRenderer->replaceVariables($pageContentHtml, $templateData);

        // Update the content in template data
        $templateData['content'] = $pageContentHtml;

        // Debug: Check if content has curly braces
        if (strpos($pageContentHtml, '{') !== false || strpos($pageContentHtml, '}') !== false) {
            error_log("CONTENT HAS CURLY BRACES: " . substr($pageContentHtml, 0, 200));
        }

        // Render template
        $templateName = $metadata['template'] ?? 'page-with-sidebar';
        fcms_do_hook_ref('before_render', $templateName);
        $template = $templateRenderer->render($templateName, $templateData);

        // Output the preview
        echo $template;
        exit;
    } else {
        error_log("Preview file not found: " . $previewFile);
        // If preview file doesn't exist, show 404
        fcms_flush_output(); // Flush output buffer before setting headers
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

error_log("DEBUG: Reached top of index.php");
// --- Advanced page caching using CacheManager ---
// Only cache GET requests, non-admin, non-logged-in
$cacheEnabled = false;
$cacheFile = null;

// Ensure session and config are loaded before checking login status
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/config.php';
require_once PROJECT_ROOT . '/includes/auth.php';
require_once PROJECT_ROOT . '/includes/CacheManager.php';

// Determine if this is a public page (not admin, not logged in, GET request)
$requestPath = trim($_SERVER['REQUEST_URI'], '/');
$configFile = CONFIG_DIR . "/config.json";
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$adminPath = $config["admin_path"] ?? "admin";
$isAdminRoute = (strpos($requestPath, $adminPath) === 0);
$isLoggedIn = function_exists('isLoggedIn') ? isLoggedIn() : false;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$isAdminRoute && !$isLoggedIn) {
    // Initialize CacheManager
    $cacheManager = new CacheManager();
    
    // Check if caching is enabled and configured for pages
    if ($cacheManager->isEnabled() && ($cacheManager->getConfig()['cache_pages'] ?? false)) {
        $cacheEnabled = true;
        
        // Generate cache key for this request
        $cacheKey = md5($_SERVER['REQUEST_URI']);
        $cacheFile = $cacheManager->getCacheDir() . '/page_' . $cacheKey . '.html';
        
        // Check if cached version exists and is still valid
        if (file_exists($cacheFile)) {
            $cacheAge = time() - filemtime($cacheFile);
            $cacheDuration = $cacheManager->getCacheDuration();
            
            if ($cacheAge < $cacheDuration) {
                // Serve cached file and record hit
                $cacheManager->recordHit();
                readfile($cacheFile);
                exit;
            }
        }
        
        // Start output buffering to capture output for caching
        ob_start();
    }
}

// Default to home if root
if ($requestPath === '') {
    $path = 'home';
    $templateName = 'home'; // Set template to home for root path
} else {
    $path = $requestPath;
    $templateName = 'page-with-sidebar'; // Default to page-with-sidebar template for other paths
}
error_log("Processed path: " . $path);

// Initialize variables for plugin handling
$handled = false;
$title = '';
$content = '';

// Let plugins handle the route first
fcms_do_hook_ref('route', $handled, $title, $content, $path);

// If a plugin handled the route, render its content
if ($handled) {
    // Get site name from config
    $configFile = CONFIG_DIR . '/config.json';
    $siteName = 'FearlessCMS';
    $siteDescription = '';
    if (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true);
        if (isset($config['site_name'])) {
            $siteName = $config['site_name'];
        }
        if (isset($config['site_description'])) {
            $siteDescription = $config['site_description'];
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
    fcms_do_hook_ref('before_render', $template, $path);

    // Prepare template data
    $templateData = [
        'title' => $title,
        'content' => $content,
        'siteName' => $siteName,
        'siteDescription' => $siteDescription,
        'currentYear' => date('Y'),
        'logo' => $themeOptions['logo'] ?? null,
        'heroBanner' => $themeOptions['herobanner'] ?? null,
        'mainMenu' => $menuManager->renderMenu('main'),

    ];

    // Add custom variables from JSON frontmatter
    if (isset($metadata) && is_array($metadata)) {
        foreach ($metadata as $key => $value) {
            $templateData[$key] = $value;
        }
    }

    // Debug: Log the template data
    error_log("TEMPLATE DATA: " . json_encode($templateData));

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
    fcms_flush_output(); // Flush output buffer before setting headers
    http_response_code(404);

    // Trigger 404 error hook for monitoring
    fcms_do_hook('404_error', $_SERVER['REQUEST_URI']);

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

        // Get site name and description from config
        $configFile = CONFIG_DIR . '/config.json';
        $siteName = 'FearlessCMS';
        $siteDescription = '';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            if (isset($config['site_name'])) {
                $siteName = $config['site_name'];
            }
            if (isset($config['site_description'])) {
                $siteDescription = $config['site_description'];
            }
        }

        // Prepare template data
        $templateData = [
            'title' => $pageTitle,
            'content' => $pageContent,
            'siteName' => $siteName,
            'siteDescription' => $siteDescription,
            'currentYear' => date('Y'),
            'logo' => $themeOptions['logo'] ?? null,
            'heroBanner' => $themeOptions['herobanner'] ?? null,
            'mainMenu' => $menuManager->renderMenu('main'),

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
    } else {
        $metadata = [];
    }
    // Remove frontmatter from content
    $pageContent = preg_replace('/^<!--\s*json\s*.*?\s*-->\s*/s', '', $fileContent);
} else {
    $metadata = [];
}

// Fallback to filename as title if not set
if (!$pageTitle) {
    $pageTitle = ucwords(str_replace(['-', '_'], ' ', basename($path)));
}

// --- Content rendering ---
// Check editor mode to determine content processing
$editorMode = $metadata['editor_mode'] ?? 'markdown';

// Load plugins BEFORE markdown processing to handle shortcodes
fcms_load_plugins();

if ($editorMode === 'html') {
    // Use content as-is for HTML mode - no processing needed
    // Process shortcodes in HTML content
    $pageContent = fcms_apply_filter('content', $pageContent);
    $pageContentHtml = $pageContent;
} else {
    // Process shortcodes in raw content first for markdown/easy modes
    $pageContent = fcms_apply_filter('content', $pageContent);
    
    // Convert markdown to HTML (default for both 'easy' and 'markdown' modes)
    if (!class_exists('Parsedown')) {
        require_once PROJECT_ROOT . '/includes/Parsedown.php';
    }
    $Parsedown = new Parsedown();
    $Parsedown->setMarkupEscaped(false); // Allow HTML in markdown
    $pageContentHtml = $Parsedown->text($pageContent);
}

// Apply after_content filters only to non-HTML content to prevent form interference
if ($editorMode !== 'html') {
    $pageContentHtml = fcms_apply_filter('after_content', $pageContentHtml);
}

// Debug: Check if content has curly braces
if (strpos($pageContentHtml, '{') !== false || strpos($pageContentHtml, '}') !== false) {
    error_log("CONTENT HAS CURLY BRACES: " . substr($pageContentHtml, 0, 200));
}

// Content filters already applied before markdown processing

// --- Theme and template ---
$themeManager = new ThemeManager();

// --- Get site name ---
$configFile = CONFIG_DIR . '/config.json';
$siteName = 'FearlessCMS';
$siteDescription = '';
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if (isset($config['site_name'])) {
        $siteName = $config['site_name'];
    }
    if (isset($config['site_description'])) {
        $siteDescription = $config['site_description'];
    }
}

// --- Get theme options ---
$themeOptionsFile = CONFIG_DIR . '/theme_options.json';
$themeOptions = file_exists($themeOptionsFile) ? json_decode(file_get_contents($themeOptionsFile), true) : [];

require_once PROJECT_ROOT . '/includes/MenuManager.php';
require_once PROJECT_ROOT . '/includes/WidgetManager.php';
require_once PROJECT_ROOT . '/includes/TemplateRenderer.php';
require_once PROJECT_ROOT . '/includes/CMSModeManager.php';

$menuManager = new MenuManager();
$widgetManager = new WidgetManager();
$cmsModeManager = new CMSModeManager();
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
    'siteDescription' => $siteDescription,
    'currentYear' => date('Y'),
    'logo' => $themeOptions['logo'] ?? null,
    'heroBanner' => $themeOptions['herobanner'] ?? null,
    'mainMenu' => $menuManager->renderMenu('main'),
    'cmsMode' => $cmsModeManager->getCurrentMode(),
    'isHostingServiceMode' => $cmsModeManager->isRestricted(),
    'cmsModeName' => $cmsModeManager->getModeName(),
];

// Add custom variables from JSON frontmatter
if (isset($metadata) && is_array($metadata)) {
    foreach ($metadata as $key => $value) {
        $templateData[$key] = $value;
    }
}

// Debug: Log the template data
error_log("TEMPLATE DATA: " . json_encode($templateData));

// --- Render template ---
    $templateName = $metadata['template'] ?? 'page-with-sidebar';
fcms_do_hook_ref('before_render', $templateName);
$template = $templateRenderer->render($templateName, $templateData);

// --- Output ---
echo $template;

// --- Save to cache if enabled ---
if (isset($cacheEnabled) && $cacheEnabled && isset($cacheManager) && $cacheFile) {
    // Get the buffered content
    $cachedContent = ob_get_contents();
    
    // Save to cache file
    if (file_put_contents($cacheFile, $cachedContent) !== false) {
        // Record cache miss (since we had to generate the content)
        $cacheManager->recordMiss();
    }
    
    // End output buffering
    ob_end_flush();
}
