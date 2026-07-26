<?php
// Set error reporting based on debug mode
ini_set('log_errors', 1);

global $config, $demoManager, $pageRenderer, $router, $cmsModeManager;

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
require_once PROJECT_ROOT . '/includes/Router.php';
require_once PROJECT_ROOT . '/includes/ContentLoader.php';
require_once PROJECT_ROOT . '/includes/PageRenderer.php';

// Check for demo mode session and handle demo content
require_once PROJECT_ROOT . '/includes/DemoModeManager.php';
$demoManager = new DemoModeManager();

// --- Initialize Router ---
$router = new Router($demoManager, $config);





// If this is a demo session, check for session expiration
if ($demoManager->isDemoSession() || $demoManager->isDemoUserSession()) {
    if ($demoManager->isDemoSessionExpired()) {
        $demoManager->endDemoSession();
        // Redirect to login with demo expired message
        header('Location: /admin/login?demo_expired=1');
        exit;
    }
}

// Handle preview URLs
if ($router->isPreviewRequest()) {
$router->handlePreviewRequest();
}

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

// Get the path and default template
$path = $router->getRequestPath();
$templateName = $router->getDefaultTemplate($path);

// Let plugins handle the route first
$handled = false;
$title = '';
$content = '';
$metadata = [];
$router->handlePluginRoutes($handled, $title, $content, $metadata, $path);

// Initialize managers and renderers
$themeManager = new ThemeManager();
$menuManager = new MenuManager();
$widgetManager = new WidgetManager();

if (class_exists('CMSModeManager')) {
    $cmsModeManager = new CMSModeManager();
} else {
    $cmsModeManager = null;
}

$contentLoader = new ContentLoader($demoManager);
$pageRenderer = new PageRenderer($themeManager, $menuManager, $widgetManager, $cmsModeManager, $demoManager);

$isExportMode = defined('FCMS_EXPORT_MODE');

// If a plugin handled the route, render its content
if ($handled) {
echo $pageRenderer->renderPluginContent($title, $content, $path, $metadata);
if (!$isExportMode) exit;
return;
}

// Determine content directory based on demo mode
$contentDir = CONTENT_DIR;
$isDemoUser = $demoManager->isDemoUser();

// Load content
$contentFile = $contentLoader->findContentFile($path);

// 404 fallback
if (!$contentFile) {
    echo $pageRenderer->render404();
    if (!$isExportMode) exit;
    return;
}

// --- Load and process content ---
$contentData = $contentLoader->loadContent($contentFile);
$pageContentHtml = $contentLoader->processContent($contentData['content'], $contentData['editor_mode']);

// --- Render page ---
echo $pageRenderer->renderPage($contentData, $pageContentHtml, $path);

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
