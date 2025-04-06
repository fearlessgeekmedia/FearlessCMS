<?php
// Enable full error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// admin/index.php
session_start();

// Configuration
define('ADMIN_CONFIG_DIR', __DIR__ . '/config');
define('ADMIN_TEMPLATE_DIR', __DIR__ . '/templates');

// Create config directory if it doesn't exist
if (!file_exists(ADMIN_CONFIG_DIR)) {
    mkdir(ADMIN_CONFIG_DIR, 0755, true);
}

// Initialize users file if it doesn't exist
$usersFile = ADMIN_CONFIG_DIR . '/users.json';
if (!file_exists($usersFile)) {
    $defaultAdmin = [
        'username' => 'admin',
        // Default password: "changeme123"
        'password' => password_hash('changeme123', PASSWORD_DEFAULT)
    ];
    file_put_contents($usersFile, json_encode([$defaultAdmin]));
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $users = json_decode(file_get_contents($usersFile), true);
    $user = array_filter($users, fn($u) => $u['username'] === $username);
    
    if ($user && password_verify($password, reset($user)['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['username'] = $username;
        // Fixed: Use absolute path for admin redirect
        header('Location: /admin/index.php');
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    // Fixed: Use absolute path for admin redirect
    header('Location: /admin/index.php');
    exit;
}

// Prevent main site routing from affecting admin
unset($_GET['page']);

// Display appropriate template based on login status
if (!isLoggedIn()) {
    $template = file_get_contents(ADMIN_TEMPLATE_DIR . '/login.html');
    $template = str_replace('{{error}}', $error ?? '', $template);
} else {
    $template = file_get_contents(ADMIN_TEMPLATE_DIR . '/dashboard.html');
    
<<<<<<< HEAD
    // Get list of content files
    $contentFiles = glob('../content/*.md');
    $contentList = '';
    foreach ($contentFiles as $file) {
        $filename = basename($file);
        $contentList .= "<li class='py-2 px-4 hover:bg-gray-100'>
            <a href='?edit=" . urlencode($filename) . "'>$filename</a>
        </li>";
    }
    
    $template = str_replace('{{content_list}}', $contentList, $template);
=======
    // Check if we're in user management mode
    $isUserManagement = isset($_GET['action']) && $_GET['action'] === 'manage_users';
    
    // Check if we're in theme management mode
    $isThemeManagement = isset($_GET['action']) && $_GET['action'] === 'manage_themes';

    // Process conditional sections
    if ($isUserManagement) {
        // Show user management section, hide content management
        $template = preg_replace('/{{if_user_management}}(.*?){{\/if_user_management}}/s', '$1', $template);
        $template = preg_replace('/{{if_not_user_management}}.*?{{\/if_not_user_management}}/s', '', $template);

        // Get list of users for display
        $users = json_decode(file_get_contents($usersFile), true);
        $userList = '';
        foreach ($users as $user) {
            $username = htmlspecialchars($user['username']);
            $userList .= "<tr class='hover:bg-gray-100'>
                <td class='py-2 px-4 border-b'>$username</td>
                <td class='py-2 px-4 border-b'>
                    <button onclick='editUser(\"$username\")' class='bg-blue-500 text-white px-3 py-1 rounded mr-2 hover:bg-blue-600'>Edit</button>
                    " . ($username !== $_SESSION['username'] ? "
                    <button onclick='deleteUser(\"$username\")' class='bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600'>Delete</button>
                    " : "") . "
                </td>
            </tr>";
        }
        $template = str_replace('{{user_list}}', $userList, $template);
    } else if ($isThemeManagement) {
        // Show theme management section
        $template = preg_replace('/{{if_theme_management}}(.*?){{\/if_theme_management}}/s', '$1', $template);
        $template = preg_replace('/{{if_not_user_management}}.*?{{\/if_not_user_management}}/s', '', $template);
        
        // Sample theme data (replace with your actual data)
        $themesHTML = '';
        $themes = [
            [
                'id' => 'default',
                'name' => 'Default Theme',
                'description' => 'The default theme for FearlessCMS',
                'version' => '1.0',
                'author' => 'FearlessCMS Team',
                'active' => true
            ],
            [
                'id' => 'dark',
                'name' => 'Dark Theme',
                'description' => 'A dark theme for FearlessCMS',
                'version' => '1.0',
                'author' => 'FearlessCMS Team',
                'active' => false
            ]
        ];
        
        // Build HTML for themes directly
        foreach ($themes as $theme) {
            $activeClass = $theme['active'] ? 'ring-2 ring-green-500' : '';
            $activeLabel = $theme['active'] ? '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Active Theme</span>' : '';
            $activateButton = !$theme['active'] ? '
                <form method="POST" action="">
                    <input type="hidden" name="action" value="activate_theme" />
                    <input type="hidden" name="theme" value="'.$theme['id'].'" />
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Activate Theme</button>
                </form>' : '';
            
            $themesHTML .= '
                <div class="border rounded-lg p-4 '.$activeClass.'">
                    <h3 class="text-lg font-medium mb-2">'.$theme['name'].'</h3>
                    <p class="text-sm text-gray-600 mb-4">'.$theme['description'].'</p>
                    <div class="text-sm text-gray-500 mb-4">
                        <p>Version: '.$theme['version'].'</p>
                        <p>Author: '.$theme['author'].'</p>
                    </div>
                    '.$activeLabel.'
                    '.$activateButton.'
                </div>';
        }
        
        // Create the theme grid container
        $themesContainer = '
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            '.$themesHTML.'
        </div>';
        
        // Replace the theme tags with our HTML
        $template = str_replace('{{#themes}}.*?{{/themes}}', $themesContainer, $template);
    } else {
        // Show content management section, hide user management
        $template = preg_replace('/{{if_user_management}}.*?{{\/if_user_management}}/s', '', $template);
        $template = preg_replace('/{{if_theme_management}}.*?{{\/if_theme_management}}/s', '', $template);
        $template = preg_replace('/{{if_not_user_management}}(.*?){{\/if_not_user_management}}/s', '$1', $template);

        // Get list of content files
        $contentFiles = glob('../content/*.md');
        $contentList = '';
        foreach ($contentFiles as $file) {
            $filename = basename($file);
            $contentList .= "<li class='py-2 px-4 hover:bg-gray-100'>
                <a href='?edit=" . urlencode($filename) . "'>$filename</a>
            </li>";
        }

        $template = str_replace('{{content_list}}', $contentList, $template);
    }

    // Handle error and success messages
    if (isset($error)) {
        $template = str_replace('{{#error}}', '', $template);
        $template = str_replace('{{/error}}', '', $template);
        $template = str_replace('{{error}}', "<div class='max-w-7xl mx-auto mt-4 p-4 bg-red-100 text-red-700 rounded'>{$error}</div>", $template);
    } else {
        $template = preg_replace('/{{#error}}.*?{{\/error}}/s', '', $template);
        $template = str_replace('{{error}}', '', $template);
    }

    if (isset($success)) {
        $template = str_replace('{{#success}}', '', $template);
        $template = str_replace('{{/success}}', '', $template);
        $template = str_replace('{{success}}', "<div class='max-w-7xl mx-auto mt-4 p-4 bg-green-100 text-green-700 rounded'>{$success}</div>", $template);
    } else {
        $template = preg_replace('/{{#success}}.*?{{\/success}}/s', '', $template);
        $template = str_replace('{{success}}', '', $template);
    }

    // Replace common template variables
>>>>>>> 48539fb (Fixed issues with the dashboard. Still more to fix.)
    $template = str_replace('{{username}}', htmlspecialchars($_SESSION['username']), $template);
    
    // Remove any template conditional blocks that aren't implemented yet
    $pattern = '/\{\{if_([a-z_]+)\}\}.*?\{\{\/if_\1\}\}/s';
    $template = preg_replace($pattern, '', $template);
    
    // Remove any remaining template variables
    $template = preg_replace('/\{\{[^}]+\}\}/', '', $template);
}

echo $template;
