<?php
// Simple test to see if this file is being executed
file_put_contents(__DIR__ . '/test.log', date('Y-m-d H:i:s') . " - Admin index executed\n", FILE_APPEND);

// VERY OBVIOUS DEBUG
error_log("ADMIN INDEX.PHP IS BEING EXECUTED - " . date('Y-m-d H:i:s'));

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering at the very beginning
ob_start();

// Set session save path BEFORE starting session (same as main index.php)
$sessionDir = dirname(__DIR__) . '/sessions';
if (!is_dir($sessionDir)) {
    mkdir($sessionDir, 0755, true);
}
ini_set('session.save_path', $sessionDir);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug logging to specific file
file_put_contents(__DIR__ . '/../debug.log', date('Y-m-d H:i:s') . " - Admin index started\n", FILE_APPEND);

// Debug session at very start
error_log("Admin index.php - Initial session state: " . print_r($_SESSION, true));

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/ThemeManager.php';
require_once dirname(__DIR__) . '/includes/plugins.php';
require_once dirname(__DIR__) . '/includes/CMSModeManager.php';

// Handle plugin actions BEFORE other handlers are included
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['activate_plugin', 'deactivate_plugin', 'delete_plugin'])) {
    file_put_contents(__DIR__ . '/test.log', date('Y-m-d H:i:s') . " - Plugin action detected before handlers: " . $_POST['action'] . "\n", FILE_APPEND);
    require_once __DIR__ . '/plugin-handler.php';
    exit;
}

// Handle image uploads for ToastUI editor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_image') {
    require_once __DIR__ . '/toastui-upload-handler.php';
    exit;
}

require_once __DIR__ . '/widget-handler.php';
require_once __DIR__ . '/theme-handler.php';
require_once __DIR__ . '/store-handler.php';
require_once __DIR__ . '/newpage-handler.php';
require_once __DIR__ . '/filedel-handler.php';
require_once __DIR__ . '/widgets-handler.php';
require_once __DIR__ . '/user-handler.php';
require_once __DIR__ . '/newuser-handler.php';
require_once __DIR__ . '/edituser-handler.php';
require_once __DIR__ . '/deluser-handler.php';

// Get action from GET or POST, default to dashboard
$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';
error_log("DEBUG: Action is " . $action);

// Debug logging for action determination
error_log("DEBUG: Action determination - GET action: " . ($_GET['action'] ?? 'none') . ", POST action: " . ($_POST['action'] ?? 'none') . ", Final action: " . $action);
error_log("DEBUG: Request URI: " . $_SERVER['REQUEST_URI']);
error_log("DEBUG: Script name: " . $_SERVER['SCRIPT_NAME']);

// Debug logging
file_put_contents(__DIR__ . '/../debug.log', date('Y-m-d H:i:s') . " - Action: " . $action . ", Method: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/../debug.log', date('Y-m-d H:i:s') . " - POST: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/../debug.log', date('Y-m-d H:i:s') . " - Session: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

error_log("DEBUG: Admin index - Action: " . $action);
error_log("DEBUG: Admin index - Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("DEBUG: Admin index - POST data: " . print_r($_POST, true));
error_log("DEBUG: Admin index - GET data: " . print_r($_GET, true));

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

// Handle AJAX requests for loading menu data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'load_menu') {
    // Clear any output
    ob_clean();
    header('Content-Type: application/json');
    
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'You must be logged in to perform this action']);
        exit;
    }
    
    if (!isset($_GET['menu_id'])) {
        echo json_encode(['success' => false, 'error' => 'Menu ID is required']);
        exit;
    }
    
    $menuId = $_GET['menu_id'];
    $menuFile = CONFIG_DIR . '/menus.json';
    
    if (!file_exists($menuFile)) {
        echo json_encode(['success' => false, 'error' => 'Menu file not found']);
        exit;
    }
    
    $menus = json_decode(file_get_contents($menuFile), true);
    if (!isset($menus[$menuId])) {
        echo json_encode(['success' => false, 'error' => 'Menu not found']);
        exit;
    }
    
    echo json_encode($menus[$menuId]);
    exit;
}

// Load admin path from config
$configFile = CONFIG_DIR . '/config.json';
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$adminPath = $config['admin_path'] ?? 'admin';

// Debug session information
error_log("Admin index - Session ID: " . session_id());
error_log("Admin index - Session data: " . print_r($_SESSION, true));
error_log("Admin index - Cookies: " . print_r($_COOKIE, true));

// If not logged in, redirect to login (handled by main index.php routing)
if (!isLoggedIn()) {
    error_log("Admin index - Not logged in, redirecting to: /" . $adminPath . "/login");
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
$cmsModeManager = new CMSModeManager();

// Make CMS mode manager globally available for plugins
$GLOBALS['cmsModeManager'] = $cmsModeManager;

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
    // Recursively get all .md files
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($contentDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    $files = new RegexIterator($files, '/\.md$/');
    
    // Convert to array and sort by modification time
    $fileArray = iterator_to_array($files);
    usort($fileArray, function($a, $b) {
        return $b->getMTime() - $a->getMTime();
    });
    
    // Get 5 most recent files, excluding preview directory
    $count = 0;
    foreach ($fileArray as $file) {
        // Skip files in preview directory or with preview in the path
        if (strpos($file->getPathname(), '/preview/') !== false || strpos($file->getPathname(), 'preview') !== false) {
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
        
        $count++;
        if ($count >= 5) break;
    }
}

// Load content list for content management
$contentList = [];
if (is_dir($contentDir)) {
    // Recursively get all .md files
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($contentDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    $files = new RegexIterator($files, '/\.md$/');
    
    foreach ($files as $file) {
        // Skip files in preview directory or with preview in the path
        if (strpos($file->getPathname(), '/preview/') !== false || strpos($file->getPathname(), 'preview') !== false) {
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
    $action === 'edit_content' && isset($_GET['path'])
) {
    $path = $_GET['path'];
    // Mitigation: Only allow safe characters in path
    if (!preg_match('/^[a-zA-Z0-9_\-\/]+$/', $path)) {
        die('Invalid path');
    }
    $contentFile = CONTENT_DIR . '/' . $path . '.md';
    $resolved = realpath($contentFile);
    if (!$resolved || strpos($resolved, realpath(CONTENT_DIR)) !== 0) {
        die('Access denied');
    }
    if (file_exists($resolved)) {
        $contentData = file_get_contents($resolved);
        $title = '';
        $editorMode = 'markdown'; // Default to markdown
        if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $contentData, $matches)) {
            $metadata = json_decode($matches[1], true);
            if ($metadata) {
                if (isset($metadata['title'])) {
                    $title = $metadata['title'];
                }
                if (isset($metadata['editor_mode'])) {
                    $editorMode = $metadata['editor_mode'];
                }
            }
        }
        error_log("DEBUG: editorMode is " . $editorMode);
        if (!$title) {
            $title = ucwords(str_replace(['-', '_'], ' ', $path));
        }
        // Make $editorMode global for the template
        $GLOBALS['editorMode'] = $editorMode;
    } else {
        $error = 'Content file not found';
        $contentData = '';
        $title = '';
        $editorMode = 'markdown';
        $GLOBALS['editorMode'] = $editorMode;
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
    
    // Handle logout action
    if ($postAction === 'logout') {
        session_destroy();
        header('Location: /' . $adminPath . '/login');
        exit;
    }
    
    // Check if this is a file manager action
    if (in_array($postAction, ['upload_file', 'delete_file'])) {
        $action = 'files'; // Set the action to 'files' to use the file manager's render callback
    }
    // Check if this is a blog action
    else if (in_array($postAction, ['save_post', 'delete_post'])) {
        $action = 'blog';
    }
    // Check if this is a widget action
    else if (in_array($postAction, ['save_widget', 'delete_widget', 'add_sidebar', 'delete_sidebar', 'reorder_widgets', 'save_sidebar'])) {
        error_log("Admin index.php - Widget action detected: " . $postAction);
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

// Handle POST requests for saving content
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_content') {
    error_log("DEBUG: save_content action triggered");
    error_log("DEBUG: POST data: " . print_r($_POST, true));
    error_log("DEBUG: Content length: " . (isset($_POST['content']) ? strlen($_POST['content']) : 'NOT SET'));
    error_log("DEBUG: Text content length: " . (isset($_POST['text_content']) ? strlen($_POST['text_content']) : 'NOT SET'));
    error_log("DEBUG: Editor content length: " . (isset($_POST['editor_content']) ? strlen($_POST['editor_content']) : 'NOT SET'));
    error_log("DEBUG: Path: " . (isset($_POST['path']) ? $_POST['path'] : 'NOT SET'));
    
    if (!isLoggedIn()) {
        $error = 'You must be logged in to edit files';
    } else {
        $fileName = $_POST['path'] ?? '';
        // Check for content from either text mode or editor mode
        $content = $_POST['text_content'] ?? $_POST['editor_content'] ?? $_POST['content'] ?? '';
        $pageTitle = $_POST['title'] ?? '';
        $parentPage = $_POST['parent'] ?? '';
        $template = $_POST['template'] ?? 'page';
        $editorMode = $_POST['editor_mode'] ?? 'easy';

        // Validate filename: allow slashes for subfolders
        if (empty($fileName) || !preg_match('/^[a-zA-Z0-9_\/-]+$/', $fileName)) {
            $error = 'Invalid filename';
        } else {
            $filePath = CONTENT_DIR . '/' . $fileName . '.md';
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
                $success = 'File saved successfully';
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
            $error = 'Failed to save theme options';
        }
    }
}

// Map actions to their template files
$template_map = [
    'dashboard' => 'dashboard.php',
    'manage_content' => 'content-management.php',
    'manage_plugins' => 'plugins.php',
    'manage_themes' => 'themes.php',
    'manage_menus' => 'menus.php',
    'manage_settings' => 'site-settings.html',
    'edit_content' => 'edit_content.php',
    'new_content' => 'new_content.php',
    'files' => 'file_manager.php',
    'manage_users' => 'users.php',
    'manage_roles' => 'role-management.html',
    'manage_widgets' => 'widgets.php'
];

// Get the correct template file name
$templateFile = ADMIN_TEMPLATE_DIR . '/' . ($template_map[$action] ?? $action . '.php');

// Try .php first, then .html if .php doesn't exist
if (!file_exists($templateFile) && str_ends_with($templateFile, '.php')) {
    $htmlTemplateFile = str_replace('.php', '.html', $templateFile);
    if (file_exists($htmlTemplateFile)) {
        $templateFile = $htmlTemplateFile;
    }
}

// Check if this is a registered admin section
$admin_sections = fcms_get_admin_sections();
$section_found = false;

// First check direct sections
if (isset($admin_sections[$action])) {
    error_log("Admin index.php - Loading admin section: " . $action);
    if (isset($admin_sections[$action]['render_callback']) && is_callable($admin_sections[$action]['render_callback'])) {
        ob_start();
        $section_content = call_user_func($admin_sections[$action]['render_callback']);
        if (is_string($section_content)) {
            echo $section_content;
        }
        $content = ob_get_clean();
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
                if (is_string($section_content)) {
                    echo $section_content;
                }
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
    if (file_exists($templateFile)) {
        error_log("Admin index.php - Loading template: " . $templateFile);
        ob_start();
        include $templateFile;
        $content = ob_get_clean();
        $section_found = true;
    } else {
        error_log("Admin index.php - Invalid action: " . $action . " (Template file not found: " . $templateFile . ")");
        $content = '<div class="alert alert-danger">Invalid action specified.</div>';
    }
}

// Include the base template
$plugins_menu_label = $cmsModeManager->canManagePlugins() ? 'Plugins' : 'Additional Features';
include ADMIN_TEMPLATE_DIR . '/base.php';
?>
