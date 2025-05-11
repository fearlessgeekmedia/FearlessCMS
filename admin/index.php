<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get action from GET or POST, default to dashboard
$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';
$pageTitle = ucfirst($action);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug session
error_log("Current session at start: " . print_r($_SESSION, true));

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once PROJECT_ROOT . '/includes/ThemeManager.php';
require_once PROJECT_ROOT . '/includes/plugins.php';
require_once __DIR__ . '/widget-handler.php';
require_once __DIR__ . '/theme-handler.php';

// Debug session after includes
error_log("Session after includes: " . print_r($_SESSION, true));

$themeManager = new ThemeManager();
$usersFile = ADMIN_CONFIG_DIR . '/users.json';
$themes = $themeManager->getThemes();
$menusFile = CONFIG_DIR . '/menus.json';
$pluginsFile = PLUGIN_CONFIG;
$activePlugins = file_exists($pluginsFile) ? json_decode(file_get_contents($pluginsFile), true) : [];

// Load site name from config
$configFile = CONFIG_DIR . '/config.json';
$siteName = 'FearlessCMS'; // Default
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if (isset($config['site_name'])) {
        $siteName = $config['site_name'];
    }
    // Load custom code
    $custom_css = $config['custom_css'] ?? '';
    $custom_js = $config['custom_js'] ?? '';
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
    $files = glob($contentDir . '/*.md');
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    $files = array_slice($files, 0, 5); // Get 5 most recent files
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $title = '';
        if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $content, $matches)) {
            $metadata = json_decode($matches[1], true);
            if ($metadata && isset($metadata['title'])) {
                $title = $metadata['title'];
            }
        }
        if (!$title) {
            $title = ucwords(str_replace(['-', '_'], ' ', basename($file, '.md')));
        }
        $recentContent[] = [
            'title' => $title,
            'path' => basename($file, '.md'),
            'modified' => filemtime($file)
        ];
    }
}

$username = $_SESSION['username'] ?? '';
$content = '';
$error = '';
$success = '';
$plugin_nav_items = '';

if (!isLoggedIn()) {
    // Process login attempt
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        error_log("Login attempt for user: " . $username);
        
        if (empty($username) || empty($password)) {
            $error = 'Username and password are required';
        } else {
            if (login($username, $password)) {
                error_log("Login successful for user: " . $username);
                header('Location: ?action=dashboard');
                exit;
            } else {
                error_log("Login failed for user: " . $username);
                $error = 'Invalid username or password';
            }
        }
    }
    
    // Show login page
    ob_start();
    include ADMIN_TEMPLATE_DIR . '/login.php';
    $content = ob_get_clean();
    $pageTitle = 'Login';
} else {
    // Process POST actions first
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle logout
        if (isset($_POST['action']) && $_POST['action'] === 'logout') {
            logout();
            header('Location: ?action=login');
            exit;
        }
        // Handle JSON POST requests
        else if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';
            
            switch ($action) {
                case 'save_menu':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_menus')) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Permission denied']);
                        exit;
                    }

                    if (!isset($input['menu_id']) || !isset($input['items'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Menu ID and items are required']);
                        exit;
                    }

                    $menuId = $input['menu_id'];
                    $menuData = [
                        'label' => $input['label'] ?? ucfirst($menuId),
                        'menu_class' => $input['class'] ?? '',
                        'items' => $input['items']
                    ];

                    $menus = file_exists($menusFile) ? json_decode(file_get_contents($menusFile), true) : [];
                    $menus[$menuId] = $menuData;

                    if (file_put_contents($menusFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true]);
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Failed to save menu']);
                    }
                    exit;
                    break;

                case 'create_menu':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_menus')) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Permission denied']);
                        exit;
                    }

                    if (empty($input['name'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Menu name is required']);
                        exit;
                    }

                    $menuId = strtolower(preg_replace('/[^a-z0-9]+/', '-', $input['name']));
                    $menuData = [
                        'label' => $input['name'],
                        'menu_class' => $input['class'] ?? '',
                        'items' => []
                    ];

                    $menus = file_exists($menusFile) ? json_decode(file_get_contents($menusFile), true) : [];
                    $menus[$menuId] = $menuData;

                    if (file_put_contents($menusFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true]);
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Failed to create menu']);
                    }
                    exit;
                    break;

                case 'delete_menu':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_menus')) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Permission denied']);
                        exit;
                    }

                    if (empty($input['menu_id'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Menu ID is required']);
                        exit;
                    }

                    $menuId = $input['menu_id'];
                    $menus = file_exists($menusFile) ? json_decode(file_get_contents($menusFile), true) : [];

                    if (isset($menus[$menuId])) {
                        unset($menus[$menuId]);
                        if (file_put_contents($menusFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => true]);
                        } else {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'error' => 'Failed to delete menu']);
                        }
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Menu not found']);
                    }
                    exit;
                    break;
            }
        }
        // Handle regular POST requests
        else if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_user':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_settings')) {
                        header('Location: ?action=users&error=permission_denied');
                        exit;
                    }
                    
                    $newUsername = $_POST['new_username'] ?? '';
                    $newPassword = $_POST['new_user_password'] ?? '';
                    $permissions = $_POST['permissions'] ?? ['manage_content'];
                    $role = $_POST['role'] ?? 'author'; // Default to 'author' if not set
                    
                    if (empty($newUsername) || empty($newPassword)) {
                        header('Location: ?action=users&error=missing_fields');
                        exit;
                    }
                    
                    $users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];
                    
                    // Check if username already exists
                    foreach ($users as $user) {
                        if ($user['username'] === $newUsername) {
                            header('Location: ?action=users&error=username_exists');
                            exit;
                        }
                    }
                    
                    // Add new user
                    $users[] = [
                        'username' => $newUsername,
                        'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                        'permissions' => $permissions,
                        'role' => $role
                    ];
                    
                    if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT))) {
                        header('Location: ?action=users&success=user_added');
                    } else {
                        header('Location: ?action=users&error=save_failed');
                    }
                    exit;
                    break;
                    
                case 'edit_user':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_settings')) {
                        header('Location: ?action=users&error=permission_denied');
                        exit;
                    }
                    
                    $username = $_POST['username'] ?? '';
                    $newPassword = $_POST['new_password'] ?? '';
                    $permissions = isset($_POST['permissions']) ? (array)$_POST['permissions'] : [];
                    $role = $_POST['role'] ?? 'author'; // Default to 'author' if not set
                    
                    error_log("Edit user action - Username: " . $username);
                    error_log("Edit user action - Permissions received: " . print_r($permissions, true));
                    
                    if (empty($username)) {
                        header('Location: ?action=users&error=missing_fields');
                        exit;
                    }
                    
                    $users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];
                    if (!is_array($users)) {
                        $users = [];
                    }
                    error_log("Edit user action - Current users data: " . print_r($users, true));
                    
                    $userFound = false;
                    
                    foreach ($users as &$user) {
                        if ($user['username'] === $username) {
                            $userFound = true;
                            if (!empty($newPassword)) {
                                $user['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                            }
                            $user['permissions'] = $permissions;
                            $user['role'] = $role;
                            error_log("Edit user action - Updated user data: " . print_r($user, true));
                            break;
                        }
                    }
                    
                    if (!$userFound) {
                        header('Location: ?action=users&error=user_not_found');
                        exit;
                    }
                    
                    error_log("Edit user action - Final users data to save: " . print_r($users, true));
                    
                    if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT))) {
                        header('Location: ?action=users&success=user_updated');
                    } else {
                        header('Location: ?action=users&error=save_failed');
                    }
                    exit;
                    break;
                    
                case 'delete_user':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_settings')) {
                        header('Location: ?action=users&error=permission_denied');
                        exit;
                    }
                    
                    $username = $_POST['username'] ?? '';
                    
                    if (empty($username)) {
                        header('Location: ?action=users&error=missing_fields');
                        exit;
                    }
                    
                    // Don't allow deleting the current user
                    if ($username === $_SESSION['username']) {
                        header('Location: ?action=users&error=cannot_delete_self');
                        exit;
                    }
                    
                    $users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];
                    $newUsers = array_filter($users, function($user) use ($username) {
                        return $user['username'] !== $username;
                    });
                    
                    if (file_put_contents($usersFile, json_encode(array_values($newUsers), JSON_PRETTY_PRINT))) {
                        header('Location: ?action=users&success=user_deleted');
                    } else {
                        header('Location: ?action=users&error=save_failed');
                    }
                    exit;
                    break;

                case 'activate_theme':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_themes')) {
                        header('Location: ?action=themes&error=permission_denied');
                        exit;
                    }
                    
                    $theme = $_POST['theme'] ?? '';
                    if ($themeManager->activateTheme($theme)) {
                        header('Location: ?action=themes&success=theme_activated');
                    } else {
                        header('Location: ?action=themes&error=activation_failed');
                    }
                    exit;
                    break;

                case 'save_theme_options':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_themes')) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Permission denied']);
                        exit;
                    }

                    $themeOptionsFile = CONFIG_DIR . '/theme_options.json';
                    $themeOptions = file_exists($themeOptionsFile) ? json_decode(file_get_contents($themeOptionsFile), true) : [];

                    // Handle logo upload
                    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                        $logoFile = $_FILES['logo'];
                        $logoExt = strtolower(pathinfo($logoFile['name'], PATHINFO_EXTENSION));
                        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
                        
                        if (!in_array($logoExt, $allowedExts)) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'error' => 'Invalid logo file type']);
                            exit;
                        }

                        $logoPath = 'uploads/theme/logo.' . $logoExt;
                        if (!is_dir(dirname($logoPath))) {
                            mkdir(dirname($logoPath), 0755, true);
                        }
                        
                        if (move_uploaded_file($logoFile['tmp_name'], $logoPath)) {
                            $themeOptions['logo'] = $logoPath;
                        }
                    }

                    // Handle hero banner upload
                    if (isset($_FILES['herobanner']) && $_FILES['herobanner']['error'] === UPLOAD_ERR_OK) {
                        $bannerFile = $_FILES['herobanner'];
                        $bannerExt = strtolower(pathinfo($bannerFile['name'], PATHINFO_EXTENSION));
                        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
                        
                        if (!in_array($bannerExt, $allowedExts)) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'error' => 'Invalid hero banner file type']);
                            exit;
                        }

                        $bannerPath = 'uploads/theme/herobanner.' . $bannerExt;
                        if (!is_dir(dirname($bannerPath))) {
                            mkdir(dirname($bannerPath), 0755, true);
                        }
                        
                        if (move_uploaded_file($bannerFile['tmp_name'], $bannerPath)) {
                            $themeOptions['herobanner'] = $bannerPath;
                        }
                    }

                    if (file_put_contents($themeOptionsFile, json_encode($themeOptions, JSON_PRETTY_PRINT))) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true]);
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Failed to save theme options']);
                    }
                    exit;
                    break;

                case 'update_site_name':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_settings')) {
                        $error = 'You do not have permission to manage settings';
                    } else if (empty($_POST['site_name'])) {
                        $error = 'Site name is required';
                    } else {
                        $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
                        $config['site_name'] = trim($_POST['site_name']);
                        if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT))) {
                            $siteName = $config['site_name'];
                            $success = 'Site name updated successfully';
                        } else {
                            $error = 'Failed to update site name';
                        }
                    }
                    break;

                case 'save_content':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_content')) {
                        $error = 'You do not have permission to edit content';
                    } else if (empty($_POST['path']) || empty($_POST['content'])) {
                        $error = 'Path and content are required';
                    } else {
                        $path = $_POST['path'];
                        $title = $_POST['title'] ?? '';
                        $content = $_POST['content'];
                        $template = $_POST['template'] ?? 'page';
                        $parent = $_POST['parent'] ?? '';
                        $contentFile = CONTENT_DIR . '/' . $path . '.md';
                        
                        // Create metadata
                        $metadata = [
                            'title' => $title,
                            'template' => $template,
                            'last_modified' => date('Y-m-d H:i:s'),
                            'author' => $_SESSION['username']
                        ];
                        
                        // Add parent if specified
                        if (!empty($parent)) {
                            $metadata['parent'] = $parent;
                        }
                        
                        // Combine metadata and content
                        $fullContent = "<!-- json " . json_encode($metadata) . " -->\n\n" . $content;
                        
                        if (file_put_contents($contentFile, $fullContent)) {
                            $success = 'Content saved successfully';
                            // Redirect back to editor
                            header('Location: ?action=edit_content&path=' . urlencode($path) . '&editor=toast');
                            exit;
                        } else {
                            $error = 'Failed to save content';
                        }
                    }
                    break;

                case 'save_custom_code':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_settings')) {
                        $error = 'You do not have permission to manage settings';
                    } else {
                        $customCss = $_POST['custom_css'] ?? '';
                        $customJs = $_POST['custom_js'] ?? '';
                        
                        $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
                        $config['custom_css'] = $customCss;
                        $config['custom_js'] = $customJs;
                        
                        if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT))) {
                            $success = 'Custom code saved successfully';
                            $custom_css = $customCss;
                            $custom_js = $customJs;
                        } else {
                            $error = 'Failed to save custom code';
                        }
                    }
                    break;

                case 'delete_content':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_content')) {
                        $error = 'You do not have permission to delete content';
                    } else if (empty($_POST['path'])) {
                        $error = 'Path is required';
                    } else {
                        $path = $_POST['path'];
                        $contentFile = CONTENT_DIR . '/' . $path . '.md';
                        
                        if (file_exists($contentFile)) {
                            if (unlink($contentFile)) {
                                $success = 'Page deleted successfully';
                                // Redirect back to dashboard
                                header('Location: ?action=dashboard');
                                exit;
                            } else {
                                $error = 'Failed to delete page';
                            }
                        } else {
                            $error = 'Page not found';
                        }
                    }
                    break;

                case 'upload_image':
                    if (!fcms_check_permission($_SESSION['username'], 'manage_content')) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Permission denied']);
                        exit;
                    }

                    if (empty($_FILES['file'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'No file uploaded']);
                        exit;
                    }

                    $file = $_FILES['file'];
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $maxSize = 5 * 1024 * 1024; // 5MB

                    if (!in_array($file['type'], $allowedTypes)) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Invalid file type']);
                        exit;
                    }

                    if ($file['size'] > $maxSize) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'File too large']);
                        exit;
                    }

                    // Create uploads directory if it doesn't exist
                    $uploadDir = PROJECT_ROOT . '/uploads';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    // Generate unique filename
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = uniqid() . '.' . $extension;
                    $targetPath = $uploadDir . '/' . $filename;

                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'url' => '/uploads/' . $filename
                        ]);
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Failed to save file']);
                    }
                    exit;
                    break;

                case 'preview_content':
                    // Ensure we're sending JSON response
                    header('Content-Type: application/json');
                    
                    try {
                        // Get content and metadata from form
                        if (!isset($_POST['content'])) {
                            throw new Exception('Content is required');
                        }
                        
                        $content = trim($_POST['content']);
                        error_log("Received content for preview: " . substr($content, 0, 100) . "...");
                        
                        $metadata = [
                            'title' => $_POST['title'] ?? 'Preview',
                            'slug' => $_POST['slug'] ?? '',
                            'parent' => $_POST['parent'] ?? '',
                            'template' => $_POST['template'] ?? 'page',
                            'last_modified' => date('Y-m-d H:i:s'),
                            'author' => 'Admin'
                        ];
                        
                        error_log("Preview metadata: " . json_encode($metadata));
                        
                        // Create a temporary preview file in the content directory
                        $previewDir = CONTENT_DIR . '/_preview';
                        if (!is_dir($previewDir)) {
                            if (!mkdir($previewDir, 0755, true)) {
                                throw new Exception('Failed to create preview directory');
                            }
                        }
                        
                        // Clean up old preview files (older than 1 hour)
                        $oldFiles = glob($previewDir . '/*.md');
                        foreach ($oldFiles as $file) {
                            if (filemtime($file) < time() - 3600) {
                                unlink($file);
                            }
                        }
                        
                        // Create new preview file
                        $previewId = uniqid('preview_');
                        $previewFile = $previewDir . '/' . $previewId . '.md';
                        
                        // Format the content with metadata
                        $fullContent = "<!-- json " . json_encode($metadata, JSON_PRETTY_PRINT) . " -->\n\n" . $content;
                        
                        // Debug information
                        error_log("Creating preview file: " . $previewFile);
                        error_log("Full preview content: " . $fullContent);
                        
                        if (file_put_contents($previewFile, $fullContent)) {
                            error_log("Preview file created successfully");
                            echo json_encode([
                                'success' => true,
                                'previewUrl' => '/_preview/' . $previewId
                            ]);
                        } else {
                            throw new Exception('Failed to create preview file');
                        }
                    } catch (Exception $e) {
                        error_log("Preview error: " . $e->getMessage());
                        echo json_encode([
                            'success' => false,
                            'error' => $e->getMessage()
                        ]);
                    }
                    exit;
                    break;

                case 'toggle_plugin':
                    error_log("Toggle plugin action started");
                    error_log("Current session: " . print_r($_SESSION, true));
                    
                    // Prevent any output before JSON response
                    ob_clean();
                    
                    // Check if user is logged in first
                    if (!isLoggedIn()) {
                        error_log("User not logged in");
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Not logged in']);
                        exit;
                    }

                    // Check for manage_plugins permission
                    error_log("Checking manage_plugins permission for user: " . $_SESSION['username']);
                    if (!fcms_check_permission($_SESSION['username'], 'manage_plugins')) {
                        error_log("Permission denied for user: " . $_SESSION['username']);
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Permission denied']);
                        exit;
                    }

                    $pluginId = $_POST['plugin_id'] ?? '';
                    if (empty($pluginId)) {
                        error_log("No plugin ID provided");
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Plugin ID is required']);
                        exit;
                    }

                    error_log("Toggling plugin: " . $pluginId);
                    
                    // Load current active plugins
                    $activePlugins = file_exists($pluginsFile) ? json_decode(file_get_contents($pluginsFile), true) : [];
                    if (!is_array($activePlugins)) {
                        $activePlugins = [];
                    }
                    
                    // Toggle plugin status
                    if (in_array($pluginId, $activePlugins)) {
                        error_log("Deactivating plugin: " . $pluginId);
                        $activePlugins = array_diff($activePlugins, [$pluginId]);
                    } else {
                        error_log("Activating plugin: " . $pluginId);
                        $activePlugins[] = $pluginId;
                    }

                    // Save updated plugin list
                    if (file_put_contents($pluginsFile, json_encode($activePlugins, JSON_PRETTY_PRINT))) {
                        error_log("Plugin list updated successfully");
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true]);
                    } else {
                        error_log("Failed to update plugin list");
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Failed to update plugin status']);
                    }
                    exit;
                    break;
            }
        }
    }

    // Check if user is logged in
    if (!isLoggedIn() && $action !== 'login') {
        // Show login page
        ob_start();
        include ADMIN_TEMPLATE_DIR . '/login.php';
        $content = ob_get_clean();
        $pageTitle = 'Login';
    } else {
    switch ($action) {
        case 'dashboard':
            ob_start();
            include ADMIN_TEMPLATE_DIR . '/dashboard.php';
            $content = ob_get_clean();
            break;

            case 'users':
        case 'manage_users':
                // Load users data
            $users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];
                if (!is_array($users)) {
                    $users = [];
                }
                
            ob_start();
            include ADMIN_TEMPLATE_DIR . '/users.php';
            $content = ob_get_clean();
            break;

        case 'manage_themes':
            $themes = $themeManager->getThemes();
            $pageTitle = 'Themes';
            ob_start();
            include ADMIN_TEMPLATE_DIR . '/themes.php';
            $content = ob_get_clean();
            break;

        case 'manage_menus':
            $menus = file_exists($menusFile) ? json_decode(file_get_contents($menusFile), true) : [];
            $menu_options = '';
            foreach ($menus as $id => $menu) {
                $menu_options .= '<option value="' . htmlspecialchars($id) . '">' . htmlspecialchars($menu['label'] ?? $id) . '</option>';
            }
            ob_start();
            include ADMIN_TEMPLATE_DIR . '/menus.php';
            $content = ob_get_clean();
            break;

        case 'load_menu':
            if (!fcms_check_permission($_SESSION['username'], 'manage_menus')) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Permission denied']);
                exit;
            }
            
            $menuId = $_GET['menu_id'] ?? '';
            if (empty($menuId)) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Menu ID is required']);
                exit;
            }
            
            $menus = file_exists($menusFile) ? json_decode(file_get_contents($menusFile), true) : [];
            if (!isset($menus[$menuId])) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Menu not found']);
                exit;
            }
            
            header('Content-Type: application/json');
            echo json_encode($menus[$menuId]);
            exit;
            break;

        case 'manage_plugins':
            $plugins = [];
            $pluginDir = PROJECT_ROOT . '/plugins';
            if (is_dir($pluginDir)) {
                $pluginFolders = glob($pluginDir . '/*', GLOB_ONLYDIR);
                foreach ($pluginFolders as $folder) {
                    $pluginId = basename($folder);
                    $pluginFile = $folder . '/' . $pluginId . '.php';
                    $pluginData = [
                        'id' => $pluginId,
                        'name' => ucfirst($pluginId),
                        'description' => 'A plugin for FearlessCMS',
                        'version' => '1.0',
                        'author' => 'Unknown',
                        'active' => in_array($pluginId, $activePlugins)
                    ];
                    if (file_exists($pluginFile)) {
                        $pluginContent = file_get_contents($pluginFile);
                        if (preg_match('/Plugin Name: (.*?)$/m', $pluginContent, $matches)) {
                            $pluginData['name'] = trim($matches[1]);
                        }
                        if (preg_match('/Description: (.*?)$/m', $pluginContent, $matches)) {
                            $pluginData['description'] = trim($matches[1]);
                        }
                        if (preg_match('/Version: (.*?)$/m', $pluginContent, $matches)) {
                            $pluginData['version'] = trim($matches[1]);
                        }
                        if (preg_match('/Author: (.*?)$/m', $pluginContent, $matches)) {
                            $pluginData['author'] = trim($matches[1]);
                        }
                    }
                    $plugins[] = $pluginData;
                }
            }
                
            ob_start();
            include ADMIN_TEMPLATE_DIR . '/plugins.php';
            $content = ob_get_clean();
            break;
            
            case 'manage_widgets':
                // Set up variables for widgets
                $widgetManager = fcms_render_widget_manager();
                $sidebar_selection = $widgetManager['sidebar_selection'];
                $widget_list = $widgetManager['widget_list'];
                $current_sidebar = $widgetManager['current_sidebar'];
            
                ob_start();
                include ADMIN_TEMPLATE_DIR . '/widgets.php';
                $content = ob_get_clean();
                $pageTitle = 'Widgets';
                break;

            case 'manage_content':
                if (!fcms_check_permission($_SESSION['username'], 'manage_content')) {
                    $error = 'You do not have permission to manage content';
                    ob_start();
                    include ADMIN_TEMPLATE_DIR . '/dashboard.php';
                    $content = ob_get_clean();
                } else {
                    // Get all content files
                    $contentFiles = glob(CONTENT_DIR . '/*.md');
                    $contentList = [];
                    
                    foreach ($contentFiles as $file) {
                        $fileContent = file_get_contents($file);
                        $title = '';
                        $lastModified = filemtime($file);
                        
                        if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $fileContent, $matches)) {
                            $metadata = json_decode($matches[1], true);
                            if ($metadata && isset($metadata['title'])) {
                                $title = $metadata['title'];
                            }
                        }
                        if (!$title) {
                            $title = ucwords(str_replace(['-', '_'], ' ', basename($file, '.md')));
                        }
                        
                        $contentList[] = [
                            'title' => $title,
                            'path' => basename($file, '.md'),
                            'modified' => date('Y-m-d H:i:s', $lastModified)
                        ];
                    }
                    
                    // Sort by last modified date, newest first
                    usort($contentList, function($a, $b) {
                        return strtotime($b['modified']) - strtotime($a['modified']);
                    });
                    
                    ob_start();
                    include ADMIN_TEMPLATE_DIR . '/content-management.html';
                    $content = ob_get_clean();
                    $pageTitle = 'Content Management';
                }
                break;

            case 'new_content':
                if (!fcms_check_permission($_SESSION['username'], 'manage_content')) {
                    $error = 'You do not have permission to create content';
                    ob_start();
                    include ADMIN_TEMPLATE_DIR . '/dashboard.php';
                    $content = ob_get_clean();
                } else {
                        // Get available templates
                        $templates = [];
                        $templateDir = PROJECT_ROOT . '/themes/' . $themeManager->getActiveTheme() . '/templates';
                        if (is_dir($templateDir)) {
                            foreach (glob($templateDir . '/*.html') as $template) {
                                $templateName = basename($template, '.html');
                                if ($templateName !== '404') { // Exclude 404 template
                                    $templates[] = $templateName;
                                }
                            }
                        }

                    ob_start();
                    include ADMIN_TEMPLATE_DIR . '/new_content.php';
                    $content = ob_get_clean();
                    $pageTitle = 'New Page';
                }
                break;

            case 'edit_content':
                if (!fcms_check_permission($_SESSION['username'], 'manage_content')) {
                    $error = 'You do not have permission to edit content';
                    ob_start();
                    include ADMIN_TEMPLATE_DIR . '/dashboard.php';
                    $content = ob_get_clean();
                } else {
                    $path = $_GET['path'] ?? '';
                    $editor = $_GET['editor'] ?? 'default';
                    $contentFile = CONTENT_DIR . '/' . $path . '.md';
                    
                    if (!file_exists($contentFile)) {
                        $error = 'Content file not found';
                        ob_start();
                        include ADMIN_TEMPLATE_DIR . '/dashboard.php';
                        $content = ob_get_clean();
                    } else {
                        $contentData = file_get_contents($contentFile);
                        $title = '';
                        if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $contentData, $matches)) {
                            $metadata = json_decode($matches[1], true);
                            if ($metadata && isset($metadata['title'])) {
                                $title = $metadata['title'];
                            }
                        }
                        if (!$title) {
                            $title = ucwords(str_replace(['-', '_'], ' ', $path));
                        }

                            // Get available templates
                            $templates = [];
                            $templateDir = PROJECT_ROOT . '/themes/' . $themeManager->getActiveTheme() . '/templates';
                            if (is_dir($templateDir)) {
                                foreach (glob($templateDir . '/*.html') as $template) {
                                    $templateName = basename($template, '.html');
                                    if ($templateName !== '404') { // Exclude 404 template
                                        $templates[] = $templateName;
                                    }
                                }
                            }
                        
                        ob_start();
                        if ($editor === 'toast') {
                            include ADMIN_TEMPLATE_DIR . '/edit_content_toast.php';
                        } else {
                            include ADMIN_TEMPLATE_DIR . '/edit_content.php';
                        }
                        $content = ob_get_clean();
                        $pageTitle = 'Edit: ' . $title;
                    }
                }
                break;

            case 'files':
                // Set up variables for the file manager
                $uploadsDir = PROJECT_ROOT . '/uploads';
                $webUploadsDir = '/uploads';
                $allowedExts = ['jpg','jpeg','png','gif','webp','pdf','zip','svg','txt','md'];
                $maxFileSize = 10 * 1024 * 1024; // 10MB
            
                // Initialize error/success
                $error = '';
                $success = '';
            
                // Handle file upload
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_file') {
                    if (!empty($_FILES['file']['name'])) {
                        $file = $_FILES['file'];
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        if (!in_array($ext, $allowedExts)) {
                            $error = 'File type not allowed.';
                        } elseif ($file['size'] > $maxFileSize) {
                            $error = 'File is too large.';
                        } else {
                            if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
                            $target = $uploadsDir . '/' . basename($file['name']);
                            if (move_uploaded_file($file['tmp_name'], $target)) {
                                $success = 'File uploaded successfully.';
                            } else {
                                $error = 'Failed to upload file.';
                            }
                        }
                    } else {
                        $error = 'No file selected.';
                    }
                }
            
                // Handle file deletion
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_file') {
                    $filename = $_POST['filename'] ?? '';
                    $filepath = realpath($uploadsDir . '/' . $filename);
                    if ($filename && $filepath && strpos($filepath, realpath($uploadsDir)) === 0 && is_file($filepath)) {
                        if (unlink($filepath)) {
                            $success = 'File deleted.';
                        } else {
                            $error = 'Failed to delete file.';
                        }
                    } else {
                        $error = 'Invalid file.';
                    }
                }
            
                // List files
                $files = [];
                if (is_dir($uploadsDir)) {
                    foreach (scandir($uploadsDir) as $f) {
                        if ($f === '.' || $f === '..') continue;
                        $full = $uploadsDir . '/' . $f;
                        if (is_file($full)) {
                            $files[] = [
                                'name' => $f,
                                'size' => filesize($full),
                                'type' => mime_content_type($full),
                                'url'  => $webUploadsDir . '/' . rawurlencode($f),
                                'ext'  => strtolower(pathinfo($f, PATHINFO_EXTENSION))
                            ];
                        }
                    }
                }
            
                // Buffer and include the file manager template
                ob_start();
                include ADMIN_TEMPLATE_DIR . '/file_manager.php';
                $content = ob_get_clean();
                $pageTitle = 'Files';
                break;

                case 'login':
            ob_start();
                    include ADMIN_TEMPLATE_DIR . '/login.php';
            $content = ob_get_clean();
                    $pageTitle = 'Login';
                    break;
                
                case 'logout':
                    logout();
                    header('Location: ?action=login');
                    exit;
                    break;

                default:
                    $admin_sections = fcms_get_admin_sections();
                    if (isset($admin_sections[$action])) {
                        $section = $admin_sections[$action];
                        $pageTitle = $section['label'];
                        $content = $section['render_callback']();
                    }
                    break;
        }
    }
}

// Load base template
ob_start();
include ADMIN_TEMPLATE_DIR . '/base.php';
echo ob_get_clean();
?>
