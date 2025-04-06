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
        header('Location: /admin/index.php');
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: /admin/index.php');
    exit;
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to change password';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match';
        } else {
            $users = json_decode(file_get_contents($usersFile), true);
            $userIndex = array_search($_SESSION['username'], array_column($users, 'username'));

            if ($userIndex !== false && password_verify($currentPassword, $users[$userIndex]['password'])) {
                $users[$userIndex]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                file_put_contents($usersFile, json_encode($users));
                $success = 'Password changed successfully';
            } else {
                $error = 'Current password is incorrect';
            }
        }
    }
}

// Handle adding new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to add users';
    } else {
        $newUsername = $_POST['new_username'] ?? '';
        $newUserPassword = $_POST['new_user_password'] ?? '';

        if (empty($newUsername) || empty($newUserPassword)) {
            $error = 'Username and password are required';
        } else {
            $users = json_decode(file_get_contents($usersFile), true);

            // Check if username already exists
            if (array_search($newUsername, array_column($users, 'username')) !== false) {
                $error = 'Username already exists';
            } else {
                $users[] = [
                    'username' => $newUsername,
                    'password' => password_hash($newUserPassword, PASSWORD_DEFAULT)
                ];
                file_put_contents($usersFile, json_encode($users));
                $success = 'User added successfully';
            }
        }
    }
}

// Handle editing user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to edit users';
    } else {
        $username = $_POST['username'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        $users = json_decode(file_get_contents($usersFile), true);
        $userIndex = array_search($username, array_column($users, 'username'));

        if ($userIndex === false) {
            $error = 'User not found';
        } else {
            // Don't allow editing the last admin user
            $adminCount = count(array_filter($users, fn($u) => $u['username'] === 'admin'));
            if ($users[$userIndex]['username'] === 'admin' && $adminCount <= 1 && $_POST['new_username'] !== 'admin') {
                $error = 'Cannot modify the last admin user';
            } else {
                // Update username if provided and different
                if (isset($_POST['new_username']) && !empty($_POST['new_username']) && $_POST['new_username'] !== $username) {
                    // Check if new username already exists
                    if (array_search($_POST['new_username'], array_column($users, 'username')) !== false) {
                        $error = 'Username already exists';
                        goto output_template; // Skip the rest of the update
                    }
                    $users[$userIndex]['username'] = $_POST['new_username'];
                }

                // Update password if provided
                if (!empty($newPassword)) {
                    $users[$userIndex]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                }

                file_put_contents($usersFile, json_encode($users));
                $success = 'User updated successfully';

                // Update session if current user updated their own username
                if ($_SESSION['username'] === $username && isset($_POST['new_username'])) {
                    $_SESSION['username'] = $_POST['new_username'];
                }
            }
        }
    }
}

// Handle deleting user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to delete users';
    } else {
        $username = $_POST['username'] ?? '';

        $users = json_decode(file_get_contents($usersFile), true);
        $userIndex = array_search($username, array_column($users, 'username'));

        if ($userIndex === false) {
            $error = 'User not found';
        } else {
            // Don't allow deleting the last admin user
            $adminCount = count(array_filter($users, fn($u) => $u['username'] === 'admin'));
            if ($users[$userIndex]['username'] === 'admin' && $adminCount <= 1) {
                $error = 'Cannot delete the last admin user';
            } else if ($username === $_SESSION['username']) {
                $error = 'Cannot delete your own account';
            } else {
                array_splice($users, $userIndex, 1);
                file_put_contents($usersFile, json_encode($users));
                $success = 'User deleted successfully';
            }
        }
    }
}

output_template:

// Prevent main site routing from affecting admin
unset($_GET['page']);

// Display appropriate template based on login status
if (!isLoggedIn()) {
    $template = file_get_contents(ADMIN_TEMPLATE_DIR . '/login.html');
    $template = str_replace('{{error}}', $error ?? '', $template);
} else {
    $template = file_get_contents(ADMIN_TEMPLATE_DIR . '/dashboard.html');
    
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
    $template = str_replace('{{username}}', htmlspecialchars($_SESSION['username']), $template);
}

echo $template;
