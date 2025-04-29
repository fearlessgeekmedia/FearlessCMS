<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define PROJECT_ROOT constant
define('PROJECT_ROOT', dirname(__DIR__));

session_start();

define('ADMIN_CONFIG_DIR', __DIR__ . '/config');
define('ADMIN_TEMPLATE_DIR', __DIR__ . '/templates');
define('CONTENT_DIR', __DIR__ . '/../content');

// Include ThemeManager class
require_once dirname(__DIR__) . '/includes/ThemeManager.php';

// Create config directory if it doesn't exist
if (!file_exists(ADMIN_CONFIG_DIR)) {
    mkdir(ADMIN_CONFIG_DIR, 0755, true);
}

// Initialize users file if it doesn't exist
$usersFile = ADMIN_CONFIG_DIR . '/users.json';
if (!file_exists($usersFile)) {
    $defaultAdmin = [
        'username' => 'admin',
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

// Handle new page creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_page') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to create pages';
    } else {
        $newPageFilename = $_POST['new_page_filename'] ?? '';
        $newPageContent = $_POST['new_page_content'] ?? '';
        $pageTitle = $_POST['page_title'] ?? '';
        
        if (!preg_match('/^[a-zA-Z0-9_-]+\.md$/', $newPageFilename)) {
            $error = 'Invalid filename. Use only letters, numbers, dashes, underscores, and end with .md';
        } else {
            $filePath = CONTENT_DIR . '/' . $newPageFilename;
            if (file_exists($filePath)) {
                $error = 'A page with that filename already exists.';
            } else {
                // Add JSON frontmatter with title if provided
                if (!empty($pageTitle)) {
                    $metadata = ['title' => $pageTitle];
                    $frontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
                    $newPageContent = $frontmatter . "\n\n" . $newPageContent;
                }
                
                if (file_put_contents($filePath, $newPageContent) !== false) {
                    // Redirect to editor for the new page
                    header('Location: ?edit=' . urlencode($newPageFilename));
                    exit;
                } else {
                    $error = 'Failed to create new page.';
                }
            }
        }
    }
}

// Handle file saving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_file') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to edit files';
    } else {
        $fileName = $_POST['file_name'] ?? '';
        $content = $_POST['content'] ?? '';
        $pageTitle = $_POST['page_title'] ?? '';
        
        // Validate filename
        if (empty($fileName) || !preg_match('/^[a-zA-Z0-9_-]+\.md$/', $fileName)) {
            $error = 'Invalid filename';
        } else {
            $filePath = CONTENT_DIR . '/' . $fileName;
            
            // Ensure we're only editing files within the content directory
            $realFilePath = realpath($filePath);
            $realContentDir = realpath(CONTENT_DIR);
            
            if ($realFilePath === false || strpos($realFilePath, $realContentDir) !== 0) {
                $error = 'Invalid file path';
            } else {
                // Check if content already has JSON frontmatter
                $hasFrontmatter = preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $content, $matches);
                
                if ($hasFrontmatter) {
                    // Update existing frontmatter
                    $metadata = json_decode($matches[1], true) ?: [];
                    $metadata['title'] = $pageTitle;
                    $newFrontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
                    $content = preg_replace('/^<!--\s*json\s*(.*?)\s*-->/s', $newFrontmatter, $content);
                } else if (!empty($pageTitle)) {
                    // Add new frontmatter
                    $metadata = ['title' => $pageTitle];
                    $newFrontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
                    $content = $newFrontmatter . "\n\n" . $content;
                }
                
                // Save the file
                if (file_put_contents($filePath, $content) !== false) {
                    $success = 'File saved successfully';
                } else {
                    $error = 'Failed to save file';
                }
            }
        }
    }
}

// Handle page deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_page') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to delete pages';
    } else {
        $fileName = $_POST['file_name'] ?? '';
        
        // Validate filename
        if (empty($fileName) || !preg_match('/^[a-zA-Z0-9_-]+\.md$/', $fileName)) {
            $error = 'Invalid filename';
        } else {
            $filePath = CONTENT_DIR . '/' . $fileName;
            
            // Ensure we're only deleting files within the content directory
            $realFilePath = realpath($filePath);
            $realContentDir = realpath(CONTENT_DIR);
            
            if ($realFilePath === false || strpos($realFilePath, $realContentDir) !== 0) {
                $error = 'Invalid file path';
            } else if (!file_exists($filePath)) {
                $error = 'File not found';
            } else {
                // Delete the file
                if (unlink($filePath)) {
                    $success = 'Page deleted successfully';
                } else {
                    $error = 'Failed to delete file';
                }
            }
        }
    }
}

output_template:

unset($_GET['page']);

if (!isLoggedIn()) {
    $template = file_get_contents(ADMIN_TEMPLATE_DIR . '/login.html');
    $template = str_replace('{{error}}', $error ?? '', $template);
} else {
    $template = file_get_contents(ADMIN_TEMPLATE_DIR . '/dashboard.html');

    $isUserManagement = isset($_GET['action']) && $_GET['action'] === 'manage_users';
    $isThemeManagement = isset($_GET['action']) && $_GET['action'] === 'manage_themes';
    $isContentEditor = isset($_GET['edit']) && !empty($_GET['edit']);
    $isMenuManagement = isset($_GET['action']) && $_GET['action'] === 'manage_menus';

    if ($isContentEditor) {
        $fileName = $_GET['edit'];
        if (!preg_match('/^[a-zA-Z0-9_-]+\.md$/', $fileName)) {
            $error = 'Invalid filename';
            $isContentEditor = false;
        } else {
            $filePath = CONTENT_DIR . '/' . $fileName;
            if (!file_exists($filePath)) {
                $error = 'File not found';
                $isContentEditor = false;
            } else {
                $fileContent = file_get_contents($filePath);
                $pageTitle = '';
                
                // Extract title from JSON frontmatter if it exists
                if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $fileContent, $matches)) {
                    $metadata = json_decode($matches[1], true);
                    if ($metadata && isset($metadata['title'])) {
                        $pageTitle = $metadata['title'];
                    }
                }

                $template = preg_replace('/\{\{if_content_editor\}\}(.*?)\{\{\/if_content_editor\}\}/s', '$1', $template);
                $template = preg_replace('/\{\{if_not_content_editor\}\}.*?\{\{\/if_not_content_editor\}\}/s', '', $template);
                $template = preg_replace('/\{\{if_user_management\}\}.*?\{\{\/if_user_management\}\}/s', '', $template);
                $template = preg_replace('/\{\{if_not_user_management\}\}.*?\{\{\/if_not_user_management\}\}/s', '', $template);
                $template = preg_replace('/\{\{if_theme_management\}\}.*?\{\{\/if_theme_management\}\}/s', '', $template);
                $template = preg_replace('/\{\{if_menu_management\}\}.*?\{\{\/if_menu_management\}\}/s', '', $template);

                $template = str_replace('{{file_name}}', htmlspecialchars($fileName), $template);
                $template = str_replace('{{page_title}}', htmlspecialchars($pageTitle), $template);
                $template = str_replace('{{file_content}}', json_encode($fileContent), $template);
            }
        }
    } else if ($isUserManagement) {
        $template = preg_replace('/\{\{if_user_management\}\}(.*?)\{\{\/if_user_management\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_not_user_management\}\}.*?\{\{\/if_not_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_theme_management\}\}.*?\{\{\/if_theme_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}.*?\{\{\/if_not_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_menu_management\}\}.*?\{\{\/if_menu_management\}\}/s', '', $template);

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
        $template = preg_replace('/\{\{if_theme_management\}\}(.*?)\{\{\/if_theme_management\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_user_management\}\}.*?\{\{\/if_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_user_management\}\}.*?\{\{\/if_not_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}.*?\{\{\/if_not_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_menu_management\}\}.*?\{\{\/if_menu_management\}\}/s', '', $template);

        // Initialize theme manager
        $themeManager = new ThemeManager();
        
        // Handle theme activation
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'activate_theme') {
            $newTheme = $_POST['theme'] ?? '';
            if (!empty($newTheme)) {
                try {
                    $themeManager->setActiveTheme($newTheme);
                    $success = "Theme '{$newTheme}' has been activated.";
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
            }
        }

        // Get all themes
        $themes = $themeManager->getThemes();
        
        // Build theme list HTML
        $themeList = '';
        foreach ($themes as $theme) {
            $activeClass = $theme['active'] ? 'ring-2 ring-green-500' : '';
            $activeLabel = $theme['active'] ? 
                '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Active Theme</span>' : '';
            $activateButton = !$theme['active'] ? 
                '<form method="POST" action="">
                    <input type="hidden" name="action" value="activate_theme" />
                    <input type="hidden" name="theme" value="'.$theme['id'].'" />
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Activate Theme</button>
                </form>' : '';
            
            $themeList .= '
                <div class="border rounded-lg p-4 '.$activeClass.'">
                    <h3 class="text-lg font-medium mb-2">'.htmlspecialchars($theme['name']).'</h3>
                    <p class="text-sm text-gray-600 mb-4">'.htmlspecialchars($theme['description']).'</p>
                    <div class="text-sm text-gray-500 mb-4">
                        <p>Version: '.htmlspecialchars($theme['version']).'</p>
                        <p>Author: '.htmlspecialchars($theme['author']).'</p>
                    </div>
                    '.$activeLabel.'
                    '.$activateButton.'
                </div>';
        }

        // Replace the {{#themes}} ... {{/themes}} block with the generated HTML
        $template = preg_replace('/\{\{#themes\}\}.*?\{\{\/themes\}\}/s', $themeList, $template);
    } else if ($isMenuManagement) {
        $menusFile = ADMIN_CONFIG_DIR . '/menus.json';
        $menus = file_exists($menusFile) ? json_decode(file_get_contents($menusFile), true) : [];

        // Scan theme for menu names
        $themeMenus = [];
        $themeFiles = glob(__DIR__ . '/../themes/default/templates/*.html');
        foreach ($themeFiles as $file) {
            if (preg_match_all('/\{\{menu=([a-zA-Z0-9_-]+)\}\}/', file_get_contents($file), $matches)) {
                foreach ($matches[1] as $menuName) {
                    $themeMenus[$menuName] = true;
                }
            }
        }

        // Get menu name from GET or POST, default to first menu or 'main'
        $currentMenu = $_GET['menu'] ?? $_POST['menu'] ?? '';
        if ($currentMenu === '' || !isset($menus[$currentMenu])) {
            $currentMenu = array_key_first($menus) ?: 'main';
        }

        // Handle new menu creation
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'create_menu') {
            $newMenu = trim($_POST['new_menu'] ?? '');
            if ($newMenu !== '' && !isset($menus[$newMenu])) {
                $menus[$newMenu] = ['menu_class' => '', 'items' => []];
                file_put_contents($menusFile, json_encode($menus, JSON_PRETTY_PRINT));
                header("Location: ?action=manage_menus&menu=" . urlencode($newMenu));
                exit;
            } else {
                $error = 'Menu name is required and must be unique.';
            }
        }

        // Handle menu save
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'save_menu') {
            $currentMenu = $_POST['menu'];
            
            // Create a clean array of menu items
            $menuItems = [];
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (!empty($item['label']) || !empty($item['url'])) {
                        $menuItems[] = [
                            'label' => $item['label'] ?? '',
                            'url' => $item['url'] ?? '',
                            'item_class' => $item['item_class'] ?? ''
                        ];
                    }
                }
            }
            
            $menus[$currentMenu]['items'] = $menuItems;
            $menus[$currentMenu]['menu_class'] = $_POST['menu_class'] ?? '';
            
            file_put_contents($menusFile, json_encode($menus, JSON_PRETTY_PRINT));
            $success = "Menu saved!";
        }

        $mainMenu = $menus[$currentMenu] ?? ['menu_class' => '', 'items' => []];

        // Build menu selection dropdown
        $menuOptions = '';
        foreach ($menus as $name => $data) {
            $sel = $name === $currentMenu ? 'selected' : '';
            $menuOptions .= "<option value=\"" . htmlspecialchars($name) . "\" $sel>" . htmlspecialchars($name) . "</option>";
        }
        // Add theme-suggested menus not in the file
        foreach ($themeMenus as $name => $_) {
            if (!isset($menus[$name])) {
                $menuOptions .= "<option value=\"" . htmlspecialchars($name) . "\">" . htmlspecialchars($name) . " (suggested by theme)</option>";
            }
        }

        // Build menu items HTML and use unique keys
        $menuItemsHtml = '';
        foreach ($mainMenu['items'] as $idx => $item) {
            $menuItemsHtml .= '<div class="flex space-x-2 mb-2">';
            $menuItemsHtml .= '<input type="text" name="items['.$idx.'][label]" value="'.htmlspecialchars($item['label']).'" placeholder="Label" class="border rounded px-2 py-1">';
            $menuItemsHtml .= '<input type="text" name="items['.$idx.'][url]" value="'.htmlspecialchars($item['url']).'" placeholder="URL" class="border rounded px-2 py-1">';
            $menuItemsHtml .= '<input type="text" name="items['.$idx.'][item_class]" value="'.htmlspecialchars($item['item_class'] ?? '').'" placeholder="Item Class" class="border rounded px-2 py-1">';
            $menuItemsHtml .= '<button type="button" onclick="this.parentNode.remove()" class="bg-red-500 text-white px-2 py-1 rounded">×</button>';
            $menuItemsHtml .= '</div>';
        }

        $menuManagementHtml = <<<HTML
        <form method="GET" class="mb-4">
            <input type="hidden" name="action" value="manage_menus">
            <label for="menu" class="mr-2">Select Menu:</label>
            <select name="menu" id="menu" onchange="this.form.submit()" class="border rounded px-2 py-1">
                $menuOptions
            </select>
        </form>
        <form method="POST" class="mb-4 flex space-x-2">
            <input type="hidden" name="action" value="create_menu">
            <input type="text" name="new_menu" placeholder="New menu name" class="border rounded px-2 py-1">
            <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded">Create Menu</button>
        </form>
        <form method="POST">
            <input type="hidden" name="action" value="save_menu">
            <input type="hidden" name="menu" value="{$currentMenu}">
            <div>
                <label class="block mb-1">Menu Class:</label>
                <input type="text" name="menu_class" value="{$mainMenu['menu_class']}" class="border rounded px-2 py-1 w-full">
            </div>
            <h3 class="mt-4 mb-2 font-semibold">Menu Items</h3>
            <div id="menu-items">
                $menuItemsHtml
            </div>
            <button type="button" onclick="addMenuItem()" class="bg-blue-500 text-white px-3 py-1 rounded">Add Item</button>
            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded ml-2">Save Menu</button>
        </form>
        <script>
        function addMenuItem() {
            const container = document.getElementById('menu-items');
            const idx = Date.now();
            const newItem = document.createElement('div');
            newItem.className = 'flex space-x-2 mb-2';
            newItem.innerHTML = `
                <input type="text" name="items[\${idx}][label]" placeholder="Label" class="border rounded px-2 py-1">
                <input type="text" name="items[\${idx}][url]" placeholder="URL" class="border rounded px-2 py-1">
                <input type="text" name="items[\${idx}][item_class]" placeholder="Item Class" class="border rounded px-2 py-1">
                <button type="button" onclick="this.parentNode.remove()" class="bg-red-500 text-white px-2 py-1 rounded">×</button>
            `;
            container.appendChild(newItem);
        }
        </script>
        HTML;

        // Replace the menu management block with the HTML
        $template = preg_replace('/\{\{if_menu_management\}\}(.*?)\{\{\/if_menu_management\}\}/s', $menuManagementHtml, $template);

        // Remove all other blocks
        $template = preg_replace('/\{\{if_user_management\}\}.*?\{\{\/if_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_theme_management\}\}.*?\{\{\/if_theme_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_user_management\}\}.*?\{\{\/if_not_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}.*?\{\{\/if_not_content_editor\}\}/s', '', $template);
    } else {
        // Show content management section, hide other sections
        $template = preg_replace('/\{\{if_not_user_management\}\}(.*?)\{\{\/if_not_user_management\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}(.*?)\{\{\/if_not_content_editor\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_user_management\}\}.*?\{\{\/if_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_theme_management\}\}.*?\{\{\/if_theme_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_menu_management\}\}.*?\{\{\/if_menu_management\}\}/s', '', $template);

        // Get list of content files
        $contentFiles = glob(CONTENT_DIR . '/*.md');
        $contentList = '';
        foreach ($contentFiles as $file) {
            $filename = basename($file);
            $fileContent = file_get_contents($file);
            $displayName = $filename;
            
            // Try to extract title from JSON frontmatter
            if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $fileContent, $matches)) {
                $metadata = json_decode($matches[1], true);
                if ($metadata && isset($metadata['title'])) {
                    $displayName = $metadata['title'] . ' <span class="text-gray-400 text-xs">(' . $filename . ')</span>';
                }
            }
            
            $contentList .= "<li class='py-2 px-4 hover:bg-gray-100'>
                <div class='flex justify-between items-center'>
                    <span>" . $displayName . "</span>
                    <div class='flex space-x-2'>
                        <a href='?edit=" . urlencode($filename) . "' class='bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600'>
                            Edit
                        </a>
                        <button onclick='deletePage(\"" . htmlspecialchars($filename) . "\")' class='bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600'>
                            Delete
                        </button>
                    </div>
                </div>
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
        $template = preg_replace('/\{\{#error\}\}.*?\{\{\/error\}\}/s', '', $template);
        $template = str_replace('{{error}}', '', $template);
    }

    if (isset($success)) {
        $template = str_replace('{{#success}}', '', $template);
        $template = str_replace('{{/success}}', '', $template);
        $template = str_replace('{{success}}', "<div class='max-w-7xl mx-auto mt-4 p-4 bg-green-100 text-green-700 rounded'>{$success}</div>", $template);
    } else {
        $template = preg_replace('/\{\{#success\}\}.*?\{\{\/success\}\}/s', '', $template);
        $template = str_replace('{{success}}', '', $template);
    }

    $template = str_replace('{{username}}', htmlspecialchars($_SESSION['username']), $template);
}

echo $template;
?>
