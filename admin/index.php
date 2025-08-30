<?php
// Handle image uploads for Quill.js editor FIRST - before any headers or output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_image') {
    require_once dirname(__DIR__) . '/includes/config.php';
    require_once dirname(__DIR__) . '/includes/auth.php';
    require_once dirname(__DIR__) . '/includes/session.php';
    require_once __DIR__ . '/quill-upload-handler.php';
    exit;
}

// Session is already started by main index.php, no need to start again
// Just ensure we have access to the required functions

// Define admin mode constant to prevent plugins from outputting content during header operations
define('ADMIN_MODE', true);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/session.php';

// Load admin path from config early
$configFile = CONFIG_DIR . '/config.json';
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$adminPath = $config['admin_path'] ?? 'admin';

// Check for serve-js.php route
if ($_SERVER['REQUEST_URI'] === '/' . $adminPath . '/serve-js.php') {
    require_once __DIR__ . '/serve-js.php';
    exit;
}

// Check authentication FIRST - redirect to login if not authenticated
if (!isLoggedIn()) {
    header('Location: /' . $adminPath . '/login');
    exit;
}

// Apply security headers for admin interface
set_security_headers();

// Prevent browser caching of admin pages to ensure fresh content
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Ensure output is not buffered to prevent stale content
if (ob_get_level()) {
    ob_end_clean();
}



// Set appropriate error reporting for production
if (getenv('FCMS_DEBUG') === 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 1); // Temporarily enable for debugging
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Log admin access for security monitoring
if (isset($_SESSION['username'])) {
    error_log("Admin access by user: " . $_SESSION['username'] . " - Action: " . ($_GET['action'] ?? 'dashboard'));
}

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/ThemeManager.php';
require_once dirname(__DIR__) . '/includes/plugins.php';
require_once dirname(__DIR__) . '/includes/CMSModeManager.php';
require_once dirname(__DIR__) . '/includes/CacheManager.php';

// Load admin includes
// require_once __DIR__ . '/includes/filemanager.php'; // Already included via plugins.php

// Generate CSRF token for forms
generate_csrf_token();

// Create CMS mode manager early so it's available for admin section filtering
$cmsModeManager = new CMSModeManager();
$GLOBALS['cmsModeManager'] = $cmsModeManager;

$themeManager = new ThemeManager(THEMES_DIR);

// Create cache manager
$cacheManager = new CacheManager();
$GLOBALS['cacheManager'] = $cacheManager;

// Handle plugin actions BEFORE other handlers are included
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['activate_plugin', 'deactivate_plugin', 'delete_plugin'])) {
    error_log("Plugin action: " . $_POST['action'] . " by user: " . ($_SESSION['username'] ?? 'unknown'));
    require_once __DIR__ . '/plugin-handler.php';
    exit;
}

// Image uploads are now handled at the beginning of the file

require_once __DIR__ . '/widget-handler.php';
require_once __DIR__ . '/theme-handler.php';
require_once __DIR__ . '/store-handler.php';
require_once __DIR__ . '/newpage-handler.php';
require_once __DIR__ . '/widgets-handler.php';
// User handlers - only include when needed
// require_once __DIR__ . '/user-handler.php';
// require_once __DIR__ . '/newuser-handler.php';
// require_once __DIR__ . '/edituser-handler.php';
// require_once __DIR__ . '/deluser-handler.php';
// Updater handler - only include when needed
// // Updater handler - only include when needed
// require_once __DIR__ . '/updater-handler.php';

// Check if a page was just created and redirect to editor (BEFORE any other includes)
error_log("DEBUG: Checking session variables - just_created_page: " . ($_SESSION['just_created_page'] ?? 'not set') . ", just_created_message: " . ($_SESSION['just_created_message'] ?? 'not set'));

if (isset($_SESSION['just_created_page']) && !empty($_SESSION['just_created_page'])) {
    $redirectPath = $_SESSION['just_created_page'];
    $successMessage = $_SESSION['just_created_message'] ?? 'Page created successfully';
    
    error_log("DEBUG: Session fallback triggered - redirectPath: " . $redirectPath . ", successMessage: " . $successMessage);
    
    // Clear the session variables
    unset($_SESSION['just_created_page']);
    unset($_SESSION['just_created_message']);
    
    // Instead of redirecting, set the action and path directly
    $action = 'edit_content';
    $_GET['path'] = $redirectPath;
    $success = $successMessage;
    
    error_log("DEBUG: Using session fallback - action set to edit_content, path set to: " . $redirectPath);
    error_log("DEBUG: After setting - action: " . $action . ", _GET[path]: " . ($_GET['path'] ?? 'not set'));
} else {
    error_log("DEBUG: No session fallback needed");
}

// Get action from GET or POST, default to dashboard
// But don't override if already set by session fallback
if (!isset($action)) {
    $action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';
}
error_log("DEBUG: Action after determination: " . $action);
error_log("DEBUG: Full request details - GET: " . print_r($_GET, true) . ", POST: " . print_r($_POST, true));

// Debug logging for action determination
error_log("DEBUG: Action determination - GET action: " . ($_GET['action'] ?? 'none') . ", POST action: " . ($_POST['action'] ?? 'none') . ", Final action: " . $action);
error_log("DEBUG: Request URI: " . $_SERVER['REQUEST_URI']);
error_log("DEBUG: Script name: " . $_SERVER['SCRIPT_NAME']);

// Security logging for admin actions
if (getenv('FCMS_DEBUG') === 'true') {
    error_log("Admin action: " . $action . " by user: " . ($_SESSION['username'] ?? 'anonymous'));
}

// Handle content deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['delete_page', 'delete_content'])) {
    error_log("DEBUG: Content deletion handler triggered!");
    error_log("DEBUG: POST data: " . print_r($_POST, true));
    
    // Clear any existing output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    if (!isLoggedIn()) {
        error_log("DEBUG: User not logged in");
        $_SESSION['error'] = 'You must be logged in to delete pages';
    } elseif (!validate_csrf_token()) {
        error_log("DEBUG: CSRF token validation failed");
        $_SESSION['error'] = 'Invalid security token. Please refresh the page and try again.';
    } elseif (!check_operation_rate_limit('delete_content', $_SESSION['username'], 10, 60)) {
        error_log("DEBUG: Rate limit exceeded");
        $_SESSION['error'] = 'Too many deletion attempts. Please wait before trying again.';
    } else {
        // Handle both old and new parameter names with sanitization
        $fileName = sanitize_input($_POST['file_name'] ?? $_POST['path'] ?? '', 'path');

        // If path is provided without .md extension, add it
        if (!empty($fileName) && !str_ends_with($fileName, '.md')) {
            $fileName .= '.md';
        }

        // Validate filename: allow slashes for subfolders
        if (empty($fileName) || !preg_match('/^[a-zA-Z0-9_\/-]+\.md$/', $fileName)) {
            $_SESSION['error'] = 'Invalid filename';
        } elseif (strpos($fileName, '../') !== false || strpos($fileName, './') === 0) {
            $_SESSION['error'] = 'Invalid file path - path traversal detected';
        } else {
            $filePath = CONTENT_DIR . '/' . $fileName;

            // Ensure we're only deleting files within the content directory
            $realFilePath = realpath($filePath);
            $realContentDir = realpath(CONTENT_DIR);

            if ($realFilePath === false || strpos($realFilePath, $realContentDir) !== 0) {
                $_SESSION['error'] = 'Invalid file path';
            } else if (!file_exists($filePath)) {
                $_SESSION['error'] = 'File not found';
            } else {
                            // Delete the file
            error_log("DEBUG: Attempting to delete file: " . $filePath);
            if (unlink($filePath)) {
                error_log("DEBUG: File deleted successfully");
                $_SESSION['success'] = 'Page deleted successfully';

                // Clear page cache after content deletion
                $cacheDir = dirname(__DIR__) . '/cache';
                if (is_dir($cacheDir)) {
                    foreach (glob($cacheDir . '/*.html') as $cacheFile) {
                        @unlink($cacheFile);
                    }
                }

                // Log security event
                error_log("SECURITY: Content file '{$fileName}' deleted by '{$_SESSION['username']}' from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            } else {
                error_log("DEBUG: Failed to delete file");
                $_SESSION['error'] = 'Failed to delete file';
            }
            }
        }
    }
    
    // Redirect back to the content management page
    $redirectUrl = $_SERVER['HTTP_REFERER'] ?? 'index.php?action=manage_content';
    error_log("DEBUG: Redirecting to: " . $redirectUrl);
    error_log("DEBUG: Session error: " . ($_SESSION['error'] ?? 'none'));
    error_log("DEBUG: Session success: " . ($_SESSION['success'] ?? 'none'));
    
    // Check if headers can still be sent
    if (headers_sent($file, $line)) {
        error_log("DEBUG: Headers already sent in $file:$line");
        // Use JavaScript redirect as fallback
        echo '<script>window.location.href = "' . htmlspecialchars($redirectUrl) . '";</script>';
        echo '<p>Redirecting... <a href="' . htmlspecialchars($redirectUrl) . '">Click here if not redirected automatically</a></p>';
        exit;
    }
    
    header('Location: ' . $redirectUrl);
    exit;
}

// Handle bulk content deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_delete_content') {
    error_log("DEBUG: Bulk content deletion handler triggered!");
    error_log("DEBUG: POST data: " . print_r($_POST, true));
    
    // Clear any existing output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    if (!isLoggedIn()) {
        error_log("DEBUG: User not logged in");
        $_SESSION['error'] = 'You must be logged in to delete pages';
    } elseif (!validate_csrf_token()) {
        error_log("DEBUG: CSRF token validation failed");
        $_SESSION['error'] = 'Invalid security token. Please refresh the page and try again.';
    } elseif (!check_operation_rate_limit('delete_content', $_SESSION['username'], 20, 60)) {
        error_log("DEBUG: Rate limit exceeded");
        $_SESSION['error'] = 'Too many deletion attempts. Please wait before trying again.';
    } else {
        $selectedItems = $_POST['selected_items'] ?? '';
        
        if (empty($selectedItems)) {
            $_SESSION['error'] = 'No items selected for deletion';
        } else {
            $itemPaths = explode(',', $selectedItems);
            $deletedCount = 0;
            $errorCount = 0;
            $errors = [];
            
            foreach ($itemPaths as $itemPath) {
                $itemPath = trim($itemPath);
                if (empty($itemPath)) continue;
                
                // If path is provided without .md extension, add it
                if (!str_ends_with($itemPath, '.md')) {
                    $itemPath .= '.md';
                }
                
                // Validate filename: allow slashes for subfolders
                if (!preg_match('/^[a-zA-Z0-9_\/-]+\.md$/', $itemPath)) {
                    $errors[] = "Invalid filename: $itemPath";
                    $errorCount++;
                    continue;
                }
                
                if (strpos($itemPath, '../') !== false || strpos($itemPath, './') === 0) {
                    $errors[] = "Invalid file path - path traversal detected: $itemPath";
                    $errorCount++;
                    continue;
                }
                
                $filePath = CONTENT_DIR . '/' . $itemPath;
                
                // Ensure we're only deleting files within the content directory
                $realFilePath = realpath($filePath);
                $realContentDir = realpath(CONTENT_DIR);
                
                if ($realFilePath === false || strpos($realFilePath, $realContentDir) !== 0) {
                    $errors[] = "Invalid file path: $itemPath";
                    $errorCount++;
                    continue;
                }
                
                if (!file_exists($filePath)) {
                    $errors[] = "File not found: $itemPath";
                    $errorCount++;
                    continue;
                }
                
                // Delete the file
                error_log("DEBUG: Attempting to delete file: " . $filePath);
                if (unlink($filePath)) {
                    error_log("DEBUG: File deleted successfully");
                    $deletedCount++;
                    
                    // Log security event
                    error_log("SECURITY: Content file '{$itemPath}' deleted by '{$_SESSION['username']}' from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                } else {
                    error_log("DEBUG: Failed to delete file: " . $filePath);
                    $errors[] = "Failed to delete: $itemPath";
                    $errorCount++;
                }
            }
            
            // Clear page cache after content deletion
            $cacheDir = dirname(__DIR__) . '/cache';
            if (is_dir($cacheDir)) {
                foreach (glob($cacheDir . '/*.html') as $cacheFile) {
                    @unlink($cacheFile);
                }
            }
            
            // Set success/error messages
            if ($deletedCount > 0) {
                if ($errorCount > 0) {
                    $_SESSION['success'] = "Successfully deleted $deletedCount item(s). $errorCount item(s) failed to delete.";
                    if (!empty($errors)) {
                        $_SESSION['error'] = "Errors: " . implode('; ', $errors);
                    }
                } else {
                    $_SESSION['success'] = "Successfully deleted $deletedCount item(s).";
                }
            } else {
                $_SESSION['error'] = "No items were deleted. Errors: " . implode('; ', $errors);
            }
        }
    }
    
    // Redirect back to the content management page
    $redirectUrl = $_SERVER['HTTP_REFERER'] ?? 'index.php?action=manage_content';
    error_log("DEBUG: Redirecting to: " . $redirectUrl);
    error_log("DEBUG: Session error: " . ($_SESSION['error'] ?? 'none'));
    error_log("DEBUG: Session success: " . ($_SESSION['success'] ?? 'none'));
    
    // Check if headers can still be sent
    if (headers_sent($file, $line)) {
        error_log("DEBUG: Headers already sent in $file:$line");
        // Use JavaScript redirect as fallback
        echo '<script>window.location.href = "' . htmlspecialchars($redirectUrl) . '";</script>';
        echo '<p>Redirecting... <a href="' . htmlspecialchars($redirectUrl) . '">Click here if not redirected automatically</a></p>';
        exit;
    }
    
    header('Location: ' . $redirectUrl);
    exit;
}

// Handle AJAX requests for menu management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'manage_menus') {
    // Clear any output
    ob_clean();
    header('Content-Type: application/json');

    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'You must be logged in to perform this action']);
        exit;
    }

    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['action'])) {
        echo json_encode(['success' => false, 'error' => 'No action specified']);
        exit;
    }

    $menuFile = CONFIG_DIR . '/menus.json';
    $menus = file_exists($menuFile) ? json_decode(file_get_contents($menuFile), true) : [];

    switch ($data['action']) {
        case 'save_menu':
            if (empty($data['menu_id']) || empty($data['items'])) {
                echo json_encode(['success' => false, 'error' => 'Menu ID and items are required']);
                exit;
            }

            $menuId = $data['menu_id'];
            $menus[$menuId] = [
                'label' => $data['label'] ?? ucwords(str_replace('_', ' ', $menuId)),
                'menu_class' => $data['class'] ?? '',
                'items' => $data['items']
            ];

            if (file_put_contents($menuFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to save menu']);
            }
            exit;

        case 'create_menu':
            if (empty($data['name'])) {
                echo json_encode(['success' => false, 'error' => 'Menu name is required']);
                exit;
            }

            $menuId = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $data['name']));

            if (isset($menus[$menuId])) {
                echo json_encode(['success' => false, 'error' => 'Menu with this name already exists']);
                exit;
            }

            $menus[$menuId] = [
                'label' => $data['name'],
                'menu_class' => $data['class'] ?? '',
                'items' => []
            ];

            if (file_put_contents($menuFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to create menu']);
            }
            exit;

        case 'delete_menu':
            if (empty($data['menu_id'])) {
                echo json_encode(['success' => false, 'error' => 'Menu ID is required']);
                exit;
            }

            $menuId = $data['menu_id'];

            if (isset($menus[$menuId])) {
                unset($menus[$menuId]);
                if (file_put_contents($menuFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to delete menu']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Menu not found']);
            }
            exit;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            exit;
    }
}



// Admin path already loaded at the top of the file

// Validate session for security
if (isset($_SESSION['username']) && getenv('FCMS_DEBUG') === 'true') {
    error_log("Valid admin session for user: " . $_SESSION['username']);
}

// If not logged in, redirect to login (handled by main index.php routing)
if (!isLoggedIn()) {
    error_log("Admin index - Not logged in, redirecting to: /" . $adminPath . "/login");
    fcms_flush_output(); // Flush output buffer before setting headers
    header('Location: /' . $adminPath . '/login');
    exit;
}



// Load plugins and run init hook
fcms_load_plugins();
fcms_do_hook('init');

// Debug logging for admin sections
$admin_sections = fcms_get_admin_sections();
error_log("Admin index.php - Available admin sections: " . print_r(array_keys($admin_sections), true));

$pageTitle = ucfirst($action);
$themeManager = new ThemeManager();

// Check CMS mode access restrictions AFTER $cmsModeManager is created
if (isLoggedIn()) {
    // Check if user is trying to access a restricted page
    $restrictedActions = [
        'manage_plugins' => 'canManagePlugins',
        'store' => 'canAccessStore',
        'files' => 'canManageFiles'
    ];

    if (isset($restrictedActions[$action])) {
        $permissionMethod = $restrictedActions[$action];
        if (!$cmsModeManager->$permissionMethod()) {
            // Redirect to dashboard with error message
            fcms_flush_output(); // Flush output buffer before setting headers
            header('Location: /' . $adminPath . '?action=dashboard&error=access_denied');
            exit;
        }
    }
}

$usersFile = CONFIG_DIR . '/users.json';
$themes = $themeManager->getThemes();
$menusFile = CONFIG_DIR . '/menus.json';
$pluginsFile = PLUGIN_CONFIG;
$activePlugins = file_exists($pluginsFile) ? json_decode(file_get_contents($pluginsFile), true) : [];

// Load site name from config
$configFile = CONFIG_DIR . '/config.json';
$siteName = 'FearlessCMS'; // Default
$siteDescription = ''; // Default empty tagline
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if (isset($config['site_name'])) {
        $siteName = $config['site_name'];
    }
    if (isset($config['site_description'])) {
        $siteDescription = $config['site_description'];
    }
    // Load custom code
    // Custom CSS and JS functionality removed
}

// Load cache configuration and statistics
$cacheConfig = $cacheManager->getConfig();
$cacheStats = $cacheManager->getStats();
$cacheStatus = $cacheManager->getCacheStatus();
$cacheSize = $cacheManager->getCacheSize();
$cacheLastCleared = $cacheManager->getLastCleared();

// Prepare cache template variables
$cache_enabled_checked = ($cacheConfig['enabled'] ?? false) ? 'checked' : '';
$cache_pages_checked = ($cacheConfig['cache_pages'] ?? false) ? 'checked' : '';
$cache_assets_checked = ($cacheConfig['cache_assets'] ?? false) ? 'checked' : '';
$cache_queries_checked = ($cacheConfig['cache_queries'] ?? false) ? 'checked' : '';
$cache_compression_checked = ($cacheConfig['cache_compression'] ?? false) ? 'checked' : '';

$cache_duration = $cacheConfig['cache_duration'] ?? 3600;
$cache_duration_unit = $cacheConfig['cache_duration_unit'] ?? 'seconds';
$cache_storage = $cacheConfig['cache_storage'] ?? 'file';
$cache_max_size = $cacheConfig['cache_max_size'] ?? '100MB';

$cache_duration_unit_seconds_selected = ($cache_duration_unit === 'seconds') ? 'selected' : '';
$cache_duration_unit_minutes_selected = ($cache_duration_unit === 'minutes') ? 'selected' : '';
$cache_duration_unit_hours_selected = ($cache_duration_unit === 'hours') ? 'selected' : '';
$cache_duration_unit_days_selected = ($cache_duration_unit === 'days') ? 'selected' : '';

$cache_storage_file_selected = ($cache_storage === 'file') ? 'selected' : '';
$cache_storage_memory_selected = ($cache_storage === 'memory') ? 'selected' : '';

// Load menu options for menu management
$menu_options = '';
if (file_exists($menusFile)) {
    $menus = json_decode(file_get_contents($menusFile), true);
    foreach ($menus as $menuId => $menu) {
        $menu_options .= sprintf(
            '<option value="%s">%s</option>',
            htmlspecialchars($menuId),
            htmlspecialchars($menu['label'] ?? ucwords(str_replace('_', ' ', $menuId)))
        );
    }
}

// Calculate total pages
$totalPages = 0;
$contentDir = CONTENT_DIR;
if (is_dir($contentDir)) {
    $files = glob($contentDir . '/*.md');
    $totalPages = count($files);
}

// Load recent content for dashboard
$recentContent = [];
if (is_dir($contentDir)) {
    error_log("DEBUG: CONTENT_DIR is: " . CONTENT_DIR); // Log CONTENT_DIR
    // Recursively get all .md files
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($contentDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    $files = new RegexIterator($files, '/\\.md$/');

    $fileArray = iterator_to_array($files);

    error_log("DEBUG: Files found by iterator before sorting and filtering:");
    foreach ($fileArray as $f) {
        error_log("DEBUG:   - " . $f->getPathname());
    }

    usort($fileArray, function($a, $b) {
        return $b->getMTime() - $a->getMTime();
    });

    // Get 5 most recent files, excluding preview directory
    $count = 0;
    foreach ($fileArray as $file) {
        error_log("DEBUG: Processing file: " . $file->getPathname());
        // Skip files in _preview directory
        if (strpos($file->getPathname(), '/content/_preview/') !== false) {
            error_log("DEBUG: Skipping file in _preview directory: " . $file->getPathname());
            continue;
        }

        $content = file_get_contents($file->getPathname());
        $title = '';
        if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $content, $matches)) {
            $metadata = json_decode($matches[1], true);
            if ($metadata && isset($metadata['title'])) {
                $title = $metadata['title'];
            }
        }
        if (!$title) {
            $title = ucwords(str_replace(['-', '_'], ' ', $file->getBasename('.md')));
        }

        // Get relative path from content directory
        $relativePath = str_replace($contentDir . '/', '', $file->getPathname());
        $path = substr($relativePath, 0, -3); // Remove .md extension

        $recentContent[] = [
            'title' => $title,
            'path' => $path,
            'modified' => $file->getMTime()
        ];
        error_log("DEBUG: Added to recentContent: Title='" . $title . "', Path='" . $path . "'");

        $count++;
        if ($count >= 5) break;
    }
    error_log("DEBUG: Final recentContent array count: " . count($recentContent));
}

// Load content list for content management
$contentList = [];
if (is_dir($contentDir)) {
    // Recursively get all .md files
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($contentDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    $files = new RegexIterator($files, '/\\.md$/');

    foreach ($files as $file) {
        // Skip files in _preview directory
        if (strpos($file->getPathname(), '/content/_preview/') !== false) {
            continue;
        }

        $content = file_get_contents($file->getPathname());
        $title = '';
        if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $content, $matches)) {
            $metadata = json_decode($matches[1], true);
            if ($metadata && isset($metadata['title'])) {
                $title = $metadata['title'];
            }
        }
        if (!$title) {
            $title = ucwords(str_replace(['-', '_'], ' ', $file->getBasename('.md')));
        }

        // Get relative path from content directory
        $relativePath = str_replace($contentDir . '/', '', $file->getPathname());
        $path = substr($relativePath, 0, -3); // Remove .md extension

        $contentList[] = [
            'title' => $title,
            'path' => $path,
            'modified' => date('Y-m-d H:i:s', $file->getMTime())
        ];
    }
}

$username = $_SESSION['username'] ?? '';
$content = '';
$error = '';
$success = '';

// Handle access denied error
if (isset($_GET['error']) && $_GET['error'] === 'access_denied') {
    $error = 'Access denied. This feature is not available in the current CMS mode.';
}

$plugin_nav_items = '';

// Load content data for edit_content action
if (
    $action === 'edit_content' && (isset($_GET['path']) || isset($_GET['amp;path']))
) {
    // Handle both encoded and non-encoded path parameters
    $path = sanitize_input($_GET['path'] ?? $_GET['amp;path'] ?? '', 'path');

    // Basic security: block path traversal attempts
    if (strpos($path, '../') !== false || strpos($path, './') === 0) {
        error_log("Path traversal attempt blocked: " . $_GET['path']);
        die('Access denied: Invalid path');
    }

    // Build safe content path
    $contentFile = CONTENT_DIR . '/' . $path;
    if (!str_ends_with($contentFile, '.md')) {
        $contentFile .= '.md';
    }
    
    // Debug: Show exact path resolution
    error_log("DEBUG: Path resolution - original path: " . $path);
    error_log("DEBUG: Path resolution - CONTENT_DIR: " . CONTENT_DIR);
    error_log("DEBUG: Path resolution - final contentFile: " . $contentFile);

    // Ensure file is within content directory
    $realContentFile = realpath($contentFile);
    $realContentDir = realpath(CONTENT_DIR);
    if ($realContentFile && $realContentDir && strpos($realContentFile, $realContentDir) !== 0) {
        error_log("Invalid path access attempt: " . $_GET['path']);
        die('Access denied: Invalid path');
    }
    
    // Debug: Log the exact file path being used
    error_log("DEBUG: Content file path: " . $contentFile);
    error_log("DEBUG: Real content file path: " . ($realContentFile ?: 'NOT FOUND'));
    error_log("DEBUG: Content directory: " . $realContentDir);
    

    
    // $contentFile is already set above
    if (file_exists($contentFile)) {
        $fileModTime = filemtime($contentFile);
        $fileHash = md5_file($contentFile);
        error_log("DEBUG: File modification time: " . date('Y-m-d H:i:s', $fileModTime));
        error_log("DEBUG: File hash: " . $fileHash);
        
        // Force fresh content loading by clearing any potential file cache
        clearstatcache(true, $contentFile);
        
        // Check if we're coming from a save operation (handle both encoded and non-encoded)
        $isFromSave = (isset($_GET['saved']) && $_GET['saved'] === '1') || 
                     (isset($_GET['amp;saved']) && $_GET['amp;saved'] === '1');
        
        if ($isFromSave) {
            error_log("DEBUG: Save operation detected - path: " . $path . ", file: " . $contentFile);
            error_log("DEBUG: GET parameters: " . print_r($_GET, true));
            error_log("DEBUG: File exists check: " . (file_exists($contentFile) ? 'YES' : 'NO'));
            error_log("DEBUG: File readable check: " . (is_readable($contentFile) ? 'YES' : 'NO'));
            
            // Show the actual file contents to debug
            if (file_exists($contentFile)) {
                $fileContents = file_get_contents($contentFile);
                error_log("DEBUG: File contents length: " . strlen($fileContents));
                error_log("DEBUG: File contents preview: " . substr($fileContents, 0, 200));
            } else {
                error_log("DEBUG: File does not exist: " . $contentFile);
            }
        }
        
        // Debug: Log all GET parameters to see what's happening
        error_log("DEBUG: All GET parameters: " . print_r($_GET, true));
        error_log("DEBUG: Path being used: " . $path);
        
        // Initialize contentData variable
        $contentData = '';
        
        // Admin area - simple file reading without complex locking
        if (!is_readable($contentFile)) {
            error_log("ERROR: File is not readable: " . $contentFile);
            $error = 'File is not readable';
        } else {
            // If coming from save operation, force reload content
            if ($isFromSave) {
                error_log("DEBUG: Force reloading content after save operation");
                // Clear any cached file info
                clearstatcache(true, $contentFile);
                // Small delay to ensure file system is updated
                usleep(100000); // 0.1 second
                
                // Force reload content from disk
                $contentData = file_get_contents($contentFile);
                error_log("DEBUG: Content RELOADED from file after save - length: " . strlen($contentData));
                
                // Verify the content is actually fresh
                if (strlen($contentData) > 0) {
                    error_log("DEBUG: Fresh content verified - length: " . strlen($contentData));
                } else {
                    error_log("ERROR: Content is empty after reload - this indicates a problem");
                    // Try to load content again as fallback
                    $contentData = file_get_contents($contentFile);
                    error_log("DEBUG: Fallback content load - length: " . strlen($contentData));
                    
                    // If still empty, try alternative path
                    if (strlen($contentData) === 0) {
                        error_log("DEBUG: Trying alternative path resolution");
                        $altPath = CONTENT_DIR . '/' . $path . '.md';
                        if (file_exists($altPath)) {
                            $contentData = file_get_contents($altPath);
                            error_log("DEBUG: Alternative path content load - length: " . strlen($contentData));
                        }
                    }
                }
            } else {
                // Normal content loading
                $contentData = file_get_contents($contentFile);
                error_log("DEBUG: Content loaded from file: " . $contentFile . " - Content length: " . strlen($contentData));
            }
            
            error_log("DEBUG: Content preview: " . substr($contentData, 0, 200));
        }
        
        // Admin area - always load fresh content from disk
        error_log("DEBUG: Admin area - content loaded fresh from disk");
        error_log("DEBUG: Final contentData length: " . strlen($contentData));
        
        $title = '';
        $editorMode = 'markdown'; // Default to markdown
        $currentTemplate = 'page-with-sidebar'; // Default to page template
        if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $contentData, $matches)) {
            $metadata = json_decode($matches[1], true);
            if ($metadata) {
                if (isset($metadata['title'])) {
                    $title = $metadata['title'];
                }
                if (isset($metadata['editor_mode'])) {
                    $editorMode = $metadata['editor_mode'];
                }
                if (isset($metadata['template'])) {
                    $currentTemplate = $metadata['template'];
                }
            }
        }
        error_log("DEBUG: editorMode is " . $editorMode);
        if (!$title) {
            $title = ucwords(str_replace(['-', '_'], ' ', $path));
        }
        // Make $editorMode global for the template
        $GLOBALS['editorMode'] = $editorMode;
        
        // Debug: Log the final variables that will be used by the template
        error_log("DEBUG: Final variables for template - contentData length: " . strlen($contentData));
        error_log("DEBUG: Final variables for template - title: " . $title);
        error_log("DEBUG: Final variables for template - editorMode: " . $editorMode);
        error_log("DEBUG: Final variables for template - path: " . $path);
    } else {
        $error = 'Content file not found';
        $contentData = '';
        $title = '';
        $editorMode = 'markdown';
        $GLOBALS['editorMode'] = $editorMode;
    }

    // Get available templates for edit content
    $templates = [];
    $templateDir = PROJECT_ROOT . '/themes/' . ($themeManager->getActiveTheme() ?? 'punk_rock') . '/templates';
    if (is_dir($templateDir)) {
        foreach (glob($templateDir . '/*.html') as $template) {
            $templateName = basename($template, '.html');
            if ($templateName !== '404' && !str_ends_with($template, '.html.mod')) {
                $templates[] = $templateName;
            }
        }
    }
} else {
    $contentData = '';
    $title = '';
    $editorMode = 'markdown';
    $GLOBALS['editorMode'] = $editorMode;
}

// Handle POST requests for admin sections
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $postAction = $_POST['action'];

    // Handle logout action (special case - doesn't need CSRF for security)
    if ($postAction === 'logout') {
        if (function_exists('session_destroy')) {
            session_destroy();
        }
        fcms_flush_output(); // Flush output buffer before setting headers
        header('Location: /' . $adminPath . '/login');
        exit;
    }

    // Validate CSRF token for all other POST actions EXCEPT delete operations and updates
    if (!in_array($postAction, ['delete_content', 'delete_page', 'updates']) && !validate_csrf_token()) {
        error_log("CSRF token validation failed for action: " . $postAction);
        $error = 'Invalid security token. Please refresh the page and try again.';
        $action = 'dashboard'; // Redirect to dashboard on CSRF failure
    } else {
        // Debug logging for updates action
        if ($postAction === 'updates') {
            error_log("Updates action detected - bypassing main admin CSRF validation");
            error_log("POST data for updates: " . print_r($_POST, true));
        }
        
        // Check if this is a file manager action
    if (in_array($postAction, ['upload_file', 'delete_file', 'bulk_delete_files', 'rename_file'])) {
        $action = 'files'; // Set the action to 'files' to use the file manager's render callback
        
        // Handle file operations directly here to avoid including filemanager.php
        if (!isLoggedIn()) {
            $error = 'You must be logged in to perform this action';
        } else {
            $uploadsDir = dirname(__DIR__) . '/uploads';
            $allowedExts = ['jpg','jpeg','png','gif','webp','pdf','zip','svg','txt','md'];
            $maxFileSize = 10 * 1024 * 1024; // 10MB
            
            // Ensure uploads directory exists
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            
            switch ($postAction) {
                case 'upload_file':
                    if (!empty($_FILES['files']['name'][0])) {
                        $uploadedFiles = $_FILES['files'];
                        $successCount = 0;
                        $errorCount = 0;
                        $errors = [];
                        
                        for ($i = 0; $i < count($uploadedFiles['name']); $i++) {
                            $file = [
                                'name' => $uploadedFiles['name'][$i],
                                'type' => $uploadedFiles['type'][$i],
                                'tmp_name' => $uploadedFiles['tmp_name'][$i],
                                'error' => $uploadedFiles['error'][$i],
                                'size' => $uploadedFiles['size'][$i]
                            ];
                            
                            $originalName = $file['name'];
                            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                            $tmpName = $file['tmp_name'];
                            
                            if ($file['error'] !== UPLOAD_ERR_OK) {
                                $errors[] = $originalName . ': Upload error occurred.';
                                $errorCount++;
                                continue;
                            }
                            
                            if (!in_array($ext, $allowedExts)) {
                                $errors[] = $originalName . ': File type not allowed.';
                                $errorCount++;
                                continue;
                            }
                            
                            if ($file['size'] > $maxFileSize) {
                                $errors[] = $originalName . ': File is too large.';
                                $errorCount++;
                                continue;
                            }
                            
                            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                            $safeName = preg_replace('/_{2,}/', '_', $safeName);
                            
                            if (strpos($safeName, '.') === 0) {
                                $safeName = 'file' . $safeName;
                            }
                            
                            $pathInfo = pathinfo($safeName);
                            $finalName = $pathInfo['filename'] . '_' . time() . '_' . $i . '.' . $pathInfo['extension'];
                            $target = $uploadsDir . '/' . $finalName;
                            
                            if (move_uploaded_file($tmpName, $target)) {
                                $successCount++;
                            } else {
                                $errors[] = $originalName . ': Failed to save file.';
                                $errorCount++;
                            }
                        }
                        
                        if ($successCount > 0) {
                            if (!empty($errors)) {
                                $error = "Errors: " . implode('; ', $errors);
                            } else {
                                $success = "Successfully uploaded $successCount file(s).";
                            }
                        } else {
                            $error = "No files were uploaded. Errors: " . implode('; ', $errors);
                        }
                    }
                    break;
                    
                case 'delete_file':
                    $filename = $_POST['filename'] ?? '';
                    if (!empty($filename)) {
                        $filePath = $uploadsDir . '/' . $filename;
                        if (file_exists($filePath) && strpos(realpath($filePath), realpath($uploadsDir)) === 0) {
                            if (unlink($filePath)) {
                                $success = 'File deleted successfully.';
                            } else {
                                $error = 'Failed to delete file.';
                            }
                        } else {
                            $error = 'Invalid file.';
                        }
                    } else {
                        $error = 'Filename is required.';
                    }
                    break;
                    
                case 'bulk_delete_files':
                    $filenames = $_POST['filenames'] ?? [];
                    if (!empty($filenames)) {
                        $deletedCount = 0;
                        $errors = [];
                        
                        foreach ($filenames as $filename) {
                            $filePath = $uploadsDir . '/' . $filename;
                            if (file_exists($filePath) && strpos(realpath($filePath), realpath($uploadsDir)) === 0) {
                                if (unlink($filePath)) {
                                    $deletedCount++;
                                } else {
                                    $errors[] = $filename . ': Failed to delete.';
                                }
                            } else {
                                $errors[] = $filename . ': Invalid file.';
                            }
                        }
                        
                        if ($deletedCount > 0) {
                            if (!empty($errors)) {
                                $error = "Errors: " . implode('; ', $errors);
                            } else {
                                $success = "Successfully deleted $deletedCount file(s).";
                            }
                        } else {
                            $error = "No files were deleted. Errors: " . implode('; ', $errors);
                        }
                    } else {
                        $error = 'No files selected for deletion.';
                    }
                    break;
                    
                case 'rename_file':
                    $oldFilename = $_POST['old_filename'] ?? '';
                    $newFilename = $_POST['new_filename'] ?? '';
                    if (!empty($oldFilename) && !empty($newFilename)) {
                        $newFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $newFilename);
                        $newFilename = preg_replace('/_{2,}/', '_', $newFilename);
                        
                        if (strpos($newFilename, '.') === 0) {
                            $newFilename = 'file' . $newFilename;
                        }
                        
                        $oldPath = $uploadsDir . '/' . $oldFilename;
                        $newPath = $uploadsDir . '/' . $newFilename;
                        
                        if (file_exists($oldPath) && strpos(realpath($oldPath), realpath($uploadsDir)) === 0) {
                            if (file_exists($newPath)) {
                                $error = 'A file with that name already exists.';
                            } else {
                                if (rename($oldPath, $newPath)) {
                                    $success = 'File renamed successfully.';
                                } else {
                                    $error = 'Failed to rename file.';
                                }
                            }
                        } else {
                            $error = 'Invalid file.';
                        }
                    } else {
                        $error = 'Both old and new filenames are required.';
                    }
                    break;
            }
        }
    }
    // Check if this is a blog action
    else if (in_array($postAction, ['save_post', 'delete_post'])) {
        $action = 'blog';
    }
    // Check if this is a widget action
    else if (in_array($postAction, ['save_widget', 'delete_widget', 'add_sidebar', 'delete_sidebar', 'reorder_widgets', 'save_sidebar'])) {
        error_log("Admin index.php - Widget action detected: " . $postAction);
        error_log("Admin index.php - POST action: " . $postAction);
        error_log("Admin index.php - All POST data: " . print_r($_POST, true));
        
        // For AJAX requests, handle them directly with the widgets handler
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            error_log("Admin index.php - Widget AJAX request detected, routing to widgets handler");
            error_log("Admin index.php - POST data: " . print_r($_POST, true));
            error_log("Admin index.php - HTTP_X_REQUESTED_WITH: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'not set'));
            
            // Clear any output buffer before including the handler
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            require_once __DIR__ . '/widgets-handler.php';
            exit;
        }
        
        $action = 'manage_widgets'; // Set the action to 'manage_widgets' to use the widgets handler
    }
    // Check if this is a plugin action
    else if (in_array($postAction, ['activate_plugin', 'deactivate_plugin', 'delete_plugin'])) {
        error_log("Admin index.php - Plugin action detected: " . $postAction);
        $action = 'manage_plugins'; // Set the action to 'manage_plugins' to use the plugin handler
    }
    // Check if this is a user management action
    else if (in_array($postAction, ['add_user', 'edit_user', 'delete_user'])) {
        error_log("Admin index.php - User action detected: " . $postAction);
        error_log("DEBUG: User management handler - POST action: " . $postAction . ", Current action: " . $action);
        error_log("DEBUG: User management handler - Processing " . $postAction . " action");

        // Handle user management actions directly
        if (!isLoggedIn()) {
            $error = 'You must be logged in to perform this action';
        } else {
            switch ($postAction) {
                case 'add_user':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_users')) {
                        $error = 'You do not have permission to manage users';
                        break;
                    }
                    if (empty($_POST['new_username']) || empty($_POST['new_user_password'])) {
                        $error = 'Username and password are required';
                        break;
                    }
                    $username = $_POST['new_username'] ?? '';
                    $password = $_POST['new_user_password'] ?? '';
                    $role = $_POST['role'] ?? 'author';

                    $users = json_decode(file_get_contents($usersFile), true);
                    if (array_search($username, array_column($users, 'username')) !== false) {
                        $error = 'Username already exists';
                    } else {
                        $users[] = [
                            'id' => uniqid(),
                            'username' => $username,
                            'password' => password_hash($password, PASSWORD_DEFAULT),
                            'role' => $role
                        ];
                        if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT))) {
                            $success = 'User added successfully';
                        } else {
                            $error = 'Failed to add user';
                        }
                    }
                    break;

                case 'edit_user':
                    error_log("DEBUG: Edit user action - Session username: " . ($_SESSION['username'] ?? 'none'));

                    // First, try to find the current user in the database
                    $users = json_decode(file_get_contents($usersFile), true);
                    $currentUser = null;
                    foreach ($users as $user) {
                        if ($user['username'] === $_SESSION['username']) {
                            $currentUser = $user;
                            break;
                        }
                    }

                    // If session username not found, try to find admin user
                    if (!$currentUser) {
                        foreach ($users as $user) {
                            if ($user['role'] === 'admin') {
                                $currentUser = $user;
                                $_SESSION['username'] = $user['username']; // Fix session
                                error_log("DEBUG: Fixed session username to: " . $user['username']);
                                break;
                            }
                        }
                    }

                    if (!$currentUser) {
                        $error = 'User not found in database';
                        $action = 'manage_users';
                        break;
                    }

                    error_log("DEBUG: Edit user action - Permission check result: " . (fcms_check_permission($_SESSION['username'], 'manage_users') ? 'true' : 'false'));

                    if (!fcms_check_permission($_SESSION['username'], 'manage_users')) {
                        $error = 'You do not have permission to manage users';
                        $action = 'manage_users'; // Set the action to 'manage_users' to show the users page
                        break;
                    }
                    if (empty($_POST['username'])) {
                        $error = 'Username is required';
                        break;
                    }
                    $username = $_POST['username'] ?? '';
                    $newUsername = $_POST['new_username'] ?? '';
                    $newPassword = $_POST['new_password'] ?? '';
                    $newRole = $_POST['user_role'] ?? 'author';

                    $users = json_decode(file_get_contents($usersFile), true);
                    $userIndex = array_search($username, array_column($users, 'username'));

                    if ($userIndex === false) {
                        $error = 'User not found';
                        break;
                    } else {
                        // Don't allow editing the last admin user
                        $adminCount = count(array_filter($users, fn($u) => $u['role'] === 'administrator'));
                        if ($users[$userIndex]['role'] === 'administrator' && $adminCount <= 1 && $newRole !== 'administrator') {
                            $error = 'Cannot modify the last admin user';
                        } else {
                            if (!empty($newUsername)) {
                                $users[$userIndex]['username'] = $newUsername;

                                // If user is editing their own username, update the session
                                if ($_SESSION['username'] === $username) {
                                    $_SESSION['username'] = $newUsername;
                                    error_log("DEBUG: Updated session username from '$username' to '$newUsername'");
                                }
                            }
                            if (!empty($newPassword)) {
                                $users[$userIndex]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                            }
                            $users[$userIndex]['role'] = $newRole;

                            // Handle permissions
                            if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
                                $users[$userIndex]['permissions'] = $_POST['permissions'];
                            } else {
                                $users[$userIndex]['permissions'] = [];
                            }

                            if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT))) {
                                $success = 'User updated successfully';
                            } else {
                                $error = 'Failed to update user';
                            }
                        }
                    }
                    break;

                case 'delete_user':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_users')) {
                        $error = 'You do not have permission to manage users';
                        break;
                    }
                    if (empty($_POST['username'])) {
                        $error = 'Username is required';
                        break;
                    }
                    $username = $_POST['username'] ?? '';

                    $users = json_decode(file_get_contents($usersFile), true);
                    $userIndex = array_search($username, array_column($users, 'username'));

                    if ($userIndex === false) {
                        $error = 'User not found';
                        break;
                    } else {
                        // Don't allow deleting the last admin user
                        $adminCount = count(array_filter($users, fn($u) => $u['role'] === 'administrator'));
                        if ($users[$userIndex]['role'] === 'administrator' && $adminCount <= 1) {
                            $error = 'Cannot delete the last admin user';
                        } else {
                            array_splice($users, $userIndex, 1);
                            if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT))) {
                                $success = 'User deleted successfully';
                            } else {
                                $error = 'Failed to delete user';
                            }
                        }
                    }
                    break;
            }
        }

        $action = 'manage_users'; // Set the action to 'manage_users' to show the users page
    }
    // Add other section-specific actions here as needed
}

// Handle POST for site name and tagline update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_site_name') {
    $newSiteName = trim($_POST['site_name'] ?? '');
    $newTagline = trim($_POST['site_description'] ?? '');
    $configFile = CONFIG_DIR . '/config.json';
    $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
    if ($newSiteName !== '') {
        $config['site_name'] = $newSiteName;
    }
    $config['site_description'] = $newTagline;
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    // Optionally, reload config for this request
    $siteName = $config['site_name'];
    $siteDescription = $config['site_description'];
    $success = 'Site name and tagline updated.';
}

// Handle POST for cache settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_cache_settings') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to update cache settings';
    } elseif (!validate_csrf_token()) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $cacheConfig = [
            'enabled' => isset($_POST['cache_enabled']),
            'cache_duration' => (int)($_POST['cache_duration'] ?? 3600),
            'cache_duration_unit' => $_POST['cache_duration_unit'] ?? 'seconds',
            'cache_pages' => isset($_POST['cache_pages']),
            'cache_assets' => isset($_POST['cache_assets']),
            'cache_queries' => isset($_POST['cache_queries']),
            'cache_compression' => isset($_POST['cache_compression']),
            'cache_storage' => $_POST['cache_storage'] ?? 'file',
            'cache_max_size' => $_POST['cache_max_size'] ?? '100MB'
        ];
        
        $cacheManager->updateConfig($cacheConfig);
        $success = 'Cache settings updated successfully.';
    }
}

// Handle POST for clearing cache
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_cache') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to clear cache';
    } elseif (!validate_csrf_token()) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $cleared = $cacheManager->clearCache();
        $success = "Cache cleared successfully. {$cleared} files removed.";
        
        // Refresh cache statistics after clearing
        $cacheStats = $cacheManager->getStats();
        $cacheStatus = $cacheManager->getCacheStatus();
        $cacheSize = $cacheManager->getCacheSize();
        $cacheLastCleared = $cacheManager->getLastCleared();
    }
}

// Handle POST for clearing cache stats
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_cache_stats') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to clear cache statistics';
    } elseif (!validate_csrf_token()) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $cacheManager->clearCacheStats();
        $success = 'Cache statistics cleared successfully.';
        
        // Refresh cache statistics after clearing stats
        $cacheStats = $cacheManager->getStats();
        $cacheStatus = $cacheManager->getCacheStatus();
        $cacheSize = $cacheManager->getCacheSize();
        $cacheLastCleared = $cacheManager->getLastCleared();
    }
}

// Handle POST requests for saving content
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_content') {
    error_log("DEBUG: save_content action triggered");
    error_log("DEBUG: POST data: " . print_r($_POST, true));
    error_log("DEBUG: Content length: " . (isset($_POST['content']) ? strlen($_POST['content']) : 'NOT SET'));
    error_log("DEBUG: Text content length: " . (isset($_POST['text_content']) ? strlen($_POST['text_content']) : 'NOT SET'));
    error_log("DEBUG: Editor content length: " . (isset($_POST['editor_content']) ? strlen($_POST['editor_content']) : 'NOT SET'));
    error_log("DEBUG: Path: " . (isset($_POST['path']) ? $_POST['path'] : 'NOT SET'));
    error_log("DEBUG: Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("DEBUG: Action: " . $_POST['action']);

    if (!isLoggedIn()) {
        $error = 'You must be logged in to edit files';
    } elseif (!validate_csrf_token()) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $oldFileName = $_POST['path'] ?? '';
        $newSlug = $_POST['new_slug'] ?? '';
        $fileName = $newSlug; // Use the new slug as the filename
        
        error_log("DEBUG: oldFileName from POST: " . $oldFileName);
        error_log("DEBUG: newSlug from POST: " . $newSlug);
        
        // Check for content from either text mode or editor mode
        $content = $_POST['text_content'] ?? $_POST['editor_content'] ?? $_POST['content'] ?? '';
        $pageTitle = $_POST['title'] ?? '';
        $parentPage = $_POST['parent'] ?? '';
        $template = $_POST['template'] ?? 'page-with-sidebar';
        $editorMode = $_POST['editor_mode'] ?? 'easy';
        


        // Validate filename: allow slashes for subfolders
        if (empty($fileName) || !preg_match('/^[a-zA-Z0-9_\\-\/]+$/', $fileName)) {
            $error = 'Invalid filename';
        } else {
            $oldFilePath = CONTENT_DIR . '/' . $oldFileName . '.md';
            $newFilePath = CONTENT_DIR . '/' . $fileName . '.md';
            
            // Check if slug has changed and handle file renaming
            if ($oldFileName !== $fileName && file_exists($oldFilePath)) {
                // Check if new filename already exists
                if (file_exists($newFilePath)) {
                    $error = 'A page with that URL slug already exists. Please choose a different slug.';
                    error_log("DEBUG: New filename already exists: " . $newFilePath);
                } else {
                    // Create directory for new path if needed
                    $dir = dirname($newFilePath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    
                    // Rename the file
                    if (rename($oldFilePath, $newFilePath)) {
                        error_log("DEBUG: File renamed from " . $oldFilePath . " to " . $newFilePath);
                    } else {
                        $error = 'Failed to rename file. Please try again.';
                        error_log("DEBUG: Failed to rename file from " . $oldFilePath . " to " . $newFilePath);
                    }
                }
            } else {
                // No slug change, use original file path
                $newFilePath = $oldFilePath;
            }
            
            $filePath = $newFilePath;
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            // Check if content already has JSON frontmatter
            $hasFrontmatter = preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $content, $matches);
            if ($hasFrontmatter) {
                $metadata = json_decode($matches[1], true) ?: [];
                $metadata['title'] = $pageTitle;
                $metadata['editor_mode'] = $editorMode;
                $metadata['template'] = $template;
                if (!empty($parentPage)) {
                    $metadata['parent'] = $parentPage;
                } elseif (isset($metadata['parent'])) {
                    unset($metadata['parent']);
                }
                $newFrontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
                $content = preg_replace('/^<!--\s*json\s*.*?\s*-->/s', $newFrontmatter, $content);
            } else {
                $metadata = [
                    'title' => $pageTitle,
                    'editor_mode' => $editorMode,
                    'template' => $template
                ];
                if (!empty($parentPage)) {
                    $metadata['parent'] = $parentPage;
                }
                $newFrontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
                $content = $newFrontmatter . "\n\n" . $content;
            }
            error_log("DEBUG: Attempting to save file: " . $filePath);
            error_log("DEBUG: Content length: " . strlen($content));

            if (file_put_contents($filePath, $content) !== false) {
                error_log("DEBUG: File saved successfully");
                
                // Verify the file was actually written
                $writtenContent = file_get_contents($filePath);
                if ($writtenContent === $content) {
                    error_log("DEBUG: File content verified - written content matches");
                } else {
                    error_log("ERROR: File content mismatch - written: " . strlen($writtenContent) . ", expected: " . strlen($content));
                }
                
                // Admin area should never be cached - ensure fresh content
                error_log("DEBUG: Admin area - no caching applied");
                
                // Only clear public cache if it exists (not admin cache)
                if (isset($cacheManager) && method_exists($cacheManager, 'clearCache')) {
                    // Only clear public-facing cache, not admin cache
                    $cleared = $cacheManager->clearCache();
                    error_log("DEBUG: Public cache cleared after content save. {$cleared} files removed.");
                }
                
                // Small delay to ensure file system has updated
                usleep(50000); // 0.05 second delay
                error_log("DEBUG: File system delay completed");;
                
                // Admin area - ensure content is fresh without complex versioning
                error_log("DEBUG: Content saved successfully - admin area always loads fresh");
                
                // Remove .md extension for the redirect path
                $redirectPath = str_replace('.md', '', $fileName);
                error_log("DEBUG: About to redirect to: ?action=edit_content&path=" . urlencode($redirectPath));
                error_log("DEBUG: Save path: " . $filePath);
                error_log("DEBUG: Redirect path: " . $redirectPath);
                error_log("DEBUG: New fileName: " . $fileName);
                
                // Always use redirect for admin area to ensure fresh content
                $timestamp = time();
                $redirectUrl = '?action=edit_content&path=' . urlencode($redirectPath) . '&saved=1&_t=' . $timestamp;
                error_log("DEBUG: Redirecting to: " . $redirectUrl . " (timestamp: " . $timestamp . ")");
                
                if (!headers_sent()) {
                    header('Location: ' . $redirectUrl);
                    exit;
                } else {
                    // If headers already sent, use JavaScript redirect with proper encoding
                    echo '<script>window.location.href = "' . addslashes($redirectUrl) . '";</script>';
                    echo '<p>Redirecting... <a href="' . htmlspecialchars($redirectUrl) . '">Click here if not redirected automatically</a></p>';
                    exit;
                }
            } else {
                error_log("DEBUG: Failed to save file");
                $error = 'Failed to save file';
            }
        }
    }
}

// Handle theme options form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_theme_options') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to edit theme options';
    } else {
        $themeOptionsFile = CONFIG_DIR . '/theme_options.json';
        $themeOptions = file_exists($themeOptionsFile) ? json_decode(file_get_contents($themeOptionsFile), true) : [];

        // Update theme options
        $themeOptions['author_name'] = $_POST['author_name'] ?? '';
        $themeOptions['author_avatar'] = $_POST['author_avatar'] ?? '';
        $themeOptions['avatar_size'] = $_POST['avatar_size'] ?? 'size-m';
        $themeOptions['avatar_first'] = isset($_POST['avatar_first']);
        $themeOptions['user'] = $_POST['user'] ?? 'user';
        $themeOptions['hostname'] = $_POST['hostname'] ?? 'localhost';
        $themeOptions['footer_html'] = $_POST['footer_html'] ?? '';
        $themeOptions['color_scheme'] = $_POST['color_scheme'] ?? 'blue';

        // Handle social links
        $socialLinks = [];
        if (isset($_POST['social_name']) && is_array($_POST['social_name'])) {
            for ($i = 0; $i < count($_POST['social_name']); $i++) {
                if (!empty($_POST['social_name'][$i]) && !empty($_POST['social_url'][$i])) {
                    $socialLinks[] = [
                        'name' => $_POST['social_name'][$i],
                        'url' => $_POST['social_url'][$i],
                        'icon' => $_POST['social_icon'][$i] ?? '',
                        'target' => $_POST['social_target'][$i] ?? '',
                        'aria' => $_POST['social_aria'][$i] ?? '',
                        'rel' => $_POST['social_rel'][$i] ?? ''
                    ];
                }
            }
        }
        $themeOptions['social_links'] = $socialLinks;

        // Save options
        if (file_put_contents($themeOptionsFile, json_encode($themeOptions, JSON_PRETTY_PRINT))) {
            $success = 'Theme options updated successfully!';
        } else {
            $error = 'Failed to update theme options';
        }
    }
    } // Close the CSRF validation else block
}

// Theme options are now handled entirely by theme-handler.php

// Map actions to their template files
error_log("DEBUG: About to map actions to templates. Current action: " . $action);
$template_map = [
    'dashboard' => 'dashboard.php',
    'manage_content' => 'content-management.php',
    'manage_plugins' => 'plugins.php',
    'manage_themes' => 'themes.php',
    'manage_menus' => 'menus.php',
    'manage_cache_settings' => 'cache-settings.php',
    'edit_content' => 'edit_content_quill.php',
    'new_content' => 'new_content.php',
    'create_page' => 'new_content.php', // Redirect create_page to new_content template
    // 'files' => 'file_manager.php', // Handled by admin section system
    'manage_users' => 'users.php',
    'manage_roles' => 'role-management.html',
    'manage_widgets' => 'widgets.php',
    'updates' => 'updater.php'
];

// Get the correct template file name
$templateFile = ADMIN_TEMPLATE_DIR . '/' . ($template_map[$action] ?? $action . '.php');

// File manager is now handled by the admin section system via fcms_render_file_manager()
// if ($action === 'files') {
//     $uploadsDir = dirname(__DIR__) . '/uploads';
//     $webUploadsDir = '/uploads';
//     $allowedExts = ['jpg','jpeg','png','gif','webp','pdf','zip','svg','txt','md'];
//     $maxFileSize = 10 * 1024 * 1024; // 10MB
//     
//     // Ensure uploads directory exists
//     if (!is_dir($uploadsDir)) {
//         mkdir($uploadsDir, 0755, true);
//     }
//     
//     $error = '';
//     $success = '';
    
//     // Handle file upload
//     if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_file') {
//         if (!empty($_FILES['files']['name'][0])) {
//             $uploadedFiles = $_FILES['files'];
//             $successCount = 0;
//             $errorCount = 0;
//             $errors = [];
//             
//             // Process each uploaded file
//             for ($i = 0; $i < count($uploadedFiles['name']); $i++) {
//                 $file = [
//                     'name' => $uploadedFiles['name'][$i],
//                     'type' => $uploadedFiles['type'][$i],
//                     'tmp_name' => $uploadedFiles['tmp_name'][$i],
//                     'error' => $uploadedFiles['error'][$i],
//                     'size' => $uploadedFiles['size'][$i]
//                 ];
//                 
//                 $originalName = $file['name'];
//                 $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
//                 $tmpName = $file['tmp_name'];
//                 
//                 // Check for upload errors
//                 if ($file['error'] !== UPLOAD_ERR_OK) {
//                     $errors[] = $originalName . ': Upload error occurred.';
//                     $errorCount++;
//                     continue;
//                 }
//                 
//                 // Validate file extension
//                 if (!in_array($ext, $allowedExts)) {
//                     $errors[] = $originalName . ': File type not allowed. Allowed types: ' . implode(', ', $allowedExts);
//                     $errorCount++;
//                     continue;
//                 }
//                 
//                 // Validate file size
//                 if ($file['size'] > $maxFileSize) {
//                     $errors[] = $originalName . ': File is too large. Maximum size: ' . round($maxFileSize/1024/1024) . 'MB';
//                     $errorCount++;
//                     continue;
//                 }
//                 
//                 // Sanitize filename
//                 $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
//                 $safeName = preg_replace('/_{2,}/', '_', $safeName);
//                 
//                 if (strpos($safeName, '.') === 0) {
//                     $safeName = 'file' . $safeName;
//                 }
//                 
//                 // Add timestamp to prevent conflicts
//                 $pathInfo = pathinfo($safeName);
//                 $finalName = $pathInfo['filename'] . '_' . time() . '_' . $i . '.' . $pathInfo['extension'];
//                 
//                 $target = $uploadsDir . '/' . $finalName;
//                 
//                 if (move_uploaded_file($tmpName, $target)) {
//                     $successCount++;
//                 } else {
//                     $errors[] = $originalName . ': Failed to save file.';
//                     $errorCount++;
//                 }
//             }
//             
//             if ($successCount > 0) {
//                 $success = "Successfully uploaded $successCount file(s).";
//             }
//             if ($errorCount > 0) {
//                 $error = implode('<br>', $errors);
//             }
//         }
//     }
    
//     // Handle file deletion
//     if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_file') {
//         $filename = $_POST['filename'] ?? '';
//         $filepath = realpath($uploadsDir . '/' . $filename);
//         if ($filename && $filepath && strpos($filepath, realpath($uploadsDir)) === 0 && is_file($filepath)) {
//             if (unlink($filepath)) {
//                 $success = 'File deleted successfully.';
//             } else {
//                 $error = 'Failed to delete file.';
//             }
//         } else {
//             $error = 'Invalid file.';
//         }
//     }
    
//     // Handle file rename
//     if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rename_file') {
//         $oldFilename = $_POST['old_filename'] ?? '';
//         $newFilename = $_POST['new_filename'] ?? '';
//         
//         if (empty($oldFilename) || empty($newFilename)) {
//             $error = 'Both old and new filenames are required.';
//         } else {
//             // Sanitize new filename
//             $newFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $newFilename);
//             $newFilename = preg_replace('/_{2,}/', '_', $newFilename);
//             
//             if (strpos($newFilename, '.') === 0) {
//                 $newFilename = 'file' . $newFilename;
//             }
//             
//             $oldPath = $uploadsDir . '/' . $oldFilename;
//             $newPath = $uploadsDir . '/' . $newFilename;
//             
//             // Check if old file exists and is within uploads directory
//             if (file_exists($oldPath) && strpos(realpath($oldPath), realpath($uploadsDir)) === 0) {
//                 // Check if new filename already exists
//                 if (file_exists($newPath)) {
//                     $error = 'A file with that name already exists.';
//                 } else {
//                     if (rename($oldPath, $newPath)) {
//                         $success = 'File renamed successfully.';
//                         
//                         // Update theme options if this was the hero banner
//                         if ($oldFilename === 'herobanner_1755845067.png') {
//                             $themeOptionsFile = dirname(__DIR__) . '/config/theme_options.json';
//                             if (file_exists($themeOptionsFile)) {
//                                 $themeOptions = json_decode(file_get_contents($themeOptionsFile), true) ?: [];
//                                 $themeOptions['herobanner'] = 'uploads/' . $newFilename;
//                                 file_put_contents($themeOptionsFile, json_encode($themeOptions, JSON_PRETTY_PRINT));
//                             }
//                         }
//                     } else {
//                         $error = 'Failed to rename file.';
//                     }
//                 }
//             } else {
//                 $error = 'Invalid file.';
//             }
//         }
//     }
    
//     // List files
//     $files = [];
//     if (is_dir($uploadsDir)) {
//         foreach (scandir($uploadsDir) as $f) {
//             if ($f === '.' || $f === '..') continue;
//             $full = $uploadsDir . '/' . $f;
//             if (is_file($full)) {
//                 $files[] = [
//                     'name' => $f,
//                     'size' => filesize($full),
//                     'type' => mime_content_type($full),
//                     'url'  => $webUploadsDir . '/' . rawurlencode($f)
//                 ];
//             }
//         }
//     }
// }
// }

// Try .php first, then .html if .php doesn't exist
if (!file_exists($templateFile) && str_ends_with($templateFile, '.php')) {
    $htmlTemplateFile = str_replace('.php', '.html', $templateFile);
    if (file_exists($htmlTemplateFile)) {
        $templateFile = $htmlTemplateFile;
    }
}

// Check if this is a registered admin section
$admin_sections = fcms_get_admin_sections();
error_log("DEBUG: Admin section loading - action: " . $action);
error_log("DEBUG: Admin section loading - available sections: " . print_r(array_keys($admin_sections), true));
error_log("DEBUG: Admin section loading - looking for section: " . $action);
$section_found = false;

// First check direct sections
if (isset($admin_sections[$action])) {
    error_log("Admin index.php - Loading admin section: " . $action);
    if (isset($admin_sections[$action]['render_callback']) && is_callable($admin_sections[$action]['render_callback'])) {
        error_log("Admin index.php - Calling render callback for section: " . $action);
        ob_start();
        $section_content = call_user_func($admin_sections[$action]['render_callback']);
        error_log("Admin index.php - Render callback returned: " . (is_string($section_content) ? 'string of length ' . strlen($section_content) : gettype($section_content)));
        $content = ob_get_clean();
        error_log("Admin index.php - Content buffer length: " . strlen($content));
        $section_found = true;
    } else {
        error_log("Admin index.php - Invalid render callback for section: " . $action);
    }
} else {
    // Then check child sections
    foreach ($admin_sections as $parent_id => $parent) {
        if (isset($parent['children']) && isset($parent['children'][$action])) {
            error_log("Admin index.php - Loading child section: " . $action);
            if (isset($parent['children'][$action]['render_callback']) && is_callable($parent['children'][$action]['render_callback'])) {
                ob_start();
                $section_content = call_user_func($parent['children'][$action]['render_callback']);
                $content = ob_get_clean();
                $section_found = true;
                break;
            } else {
                error_log("Admin index.php - Invalid render callback for child section: " . $action);
            }
        }
    }
}



// If no admin section was found, try to load the template file
if (!$section_found) {
    if (!file_exists($templateFile)) {
        error_log("Admin index.php - Invalid action: " . $action . " (Template file not found: " . $templateFile . ")");
        $content = '<div class="alert alert-danger">Invalid action specified.</div>';
        $templateFile = null; // Unset template file if not found
    } else {
        // Process template variables for HTML templates
        if (str_ends_with($templateFile, '.html')) {
            $templateContent = file_get_contents($templateFile);
            $templateContent = processAdminTemplate($templateContent);
            $content = $templateContent;
        }
    }
}

// Include the base template
$plugins_menu_label = $cmsModeManager->canManagePlugins() ? 'Plugins' : 'Additional Features';

// Template will be included directly without output buffer interference

include ADMIN_TEMPLATE_DIR . '/base.php';

/**
 * Process admin template variables
 */
function processAdminTemplate($content) {
    global $siteName, $siteDescription, $totalPages, $activePlugins;
    global $cache_enabled_checked, $cache_pages_checked, $cache_assets_checked, $cache_queries_checked, $cache_compression_checked;
    global $cache_duration, $cache_duration_unit, $cache_storage, $cache_max_size;
    global $cache_duration_unit_seconds_selected, $cache_duration_unit_minutes_selected, $cache_duration_unit_hours_selected, $cache_duration_unit_days_selected;
    global $cache_storage_file_selected, $cache_storage_memory_selected;
    global $cacheStatus, $cacheSize, $cacheLastCleared;
    
    // Replace template variables
    $replacements = [
        '{{site_name}}' => $siteName ?? 'FearlessCMS',
        '{{site_description}}' => $siteDescription ?? '',
        '{{total_pages}}' => $totalPages ?? 0,
        '{{active_plugins}}' => count($activePlugins ?? []),
        '{{cache_enabled_checked}}' => $cache_enabled_checked ?? '',
        '{{cache_pages_checked}}' => $cache_pages_checked ?? '',
        '{{cache_assets_checked}}' => $cache_assets_checked ?? '',
        '{{cache_queries_checked}}' => $cache_queries_checked ?? '',
        '{{cache_compression_checked}}' => $cache_compression_checked ?? '',
        '{{cache_duration}}' => $cache_duration ?? 3600,
        '{{cache_duration_unit_seconds_selected}}' => $cache_duration_unit_seconds_selected ?? '',
        '{{cache_duration_unit_minutes_selected}}' => $cache_duration_unit_minutes_selected ?? '',
        '{{cache_duration_unit_hours_selected}}' => $cache_duration_unit_hours_selected ?? '',
        '{{cache_duration_unit_days_selected}}' => $cache_duration_unit_days_selected ?? '',
        '{{cache_storage_file_selected}}' => $cache_storage_file_selected ?? '',
        '{{cache_storage_memory_selected}}' => $cache_storage_memory_selected ?? '',
        '{{cache_max_size}}' => $cache_max_size ?? '100MB',
        '{{cache_status}}' => $cacheStatus ?? 'Unknown',
        '{{cache_size}}' => $cacheSize ?? '0 B',
        '{{cache_last_cleared}}' => $cacheLastCleared ?? 'Never'
    ];
    
    return str_replace(array_keys($replacements), array_values($replacements), $content);
}
?>
