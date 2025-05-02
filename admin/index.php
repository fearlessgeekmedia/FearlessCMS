<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('PROJECT_ROOT', dirname(__DIR__));
define('ADMIN_CONFIG_DIR', __DIR__ . '/config');
define('ADMIN_TEMPLATE_DIR', __DIR__ . '/templates');
define('CONTENT_DIR', __DIR__ . '/../content');
define('CONFIG_DIR', dirname(__DIR__) . '/config');

session_start();

require_once dirname(__DIR__) . '/includes/ThemeManager.php';
require_once dirname(__DIR__) . '/includes/plugins.php';

// Initialize ThemeManager
$themeManager = new ThemeManager();

if (!file_exists(ADMIN_CONFIG_DIR)) {
    mkdir(ADMIN_CONFIG_DIR, 0755, true);
}

$usersFile = ADMIN_CONFIG_DIR . '/users.json';
if (!file_exists($usersFile)) {
    $defaultAdmin = [
        'username' => 'admin',
        'password' => password_hash('changeme123', PASSWORD_DEFAULT)
    ];
    file_put_contents($usersFile, json_encode([$defaultAdmin]));
}

function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Function to detect theme sidebars
function get_theme_sidebars() {
    global $themeManager;
    $sidebars = [];
    $activeTheme = $themeManager->getActiveTheme();
    $templatesDir = PROJECT_ROOT . '/themes/' . $activeTheme . '/templates';
    
    if (!is_dir($templatesDir)) {
        return $sidebars;
    }
    
    foreach (glob($templatesDir . '/*.html') as $templateFile) {
        $content = file_get_contents($templateFile);
        
        if (preg_match_all('/\{\{sidebar=([\w-]+)\}\}/', $content, $matches)) {
            foreach ($matches[1] as $sidebarId) {
                if (!isset($sidebars[$sidebarId])) {
                    $sidebars[$sidebarId] = [
                        'name' => ucwords(str_replace(['-', '_'], ' ', $sidebarId)),
                        'widgets' => []
                    ];
                }
            }
        }
    }
    
    return $sidebars;
}

require("login-handler.php");
require("logout-handler.php");
require("pchange-handler.php");
require("newuser-handler.php");
require("edituser-handler.php");
require("deluser-handler.php");
require("newpage-handler.php");
require("filesave-handler.php");
require("filedel-handler.php");
require("widgets-handler.php");

// Handle site name update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_site_name') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to update the site name';
    } else {
        $newSiteName = trim($_POST['site_name'] ?? '');
        if ($newSiteName === '') {
            $error = 'Site name cannot be empty';
        } else {
            $configFile = CONFIG_DIR . '/config.json';
            $config = [];
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
            }
            $config['site_name'] = $newSiteName;
            file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
            $success = 'Site name updated successfully';
        }
    }
}

// Load config for site name
$configFile = CONFIG_DIR . '/config.json';
$config = [];
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
}
$siteName = $config['site_name'] ?? 'FearlessCMS';


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

// Handle theme activation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'activate_theme') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to manage themes';
    } else {
        $themeName = $_POST['theme'] ?? '';
        try {
            $themeManager->setActiveTheme($themeName);
            $success = "Theme '$themeName' activated successfully";
            // Redirect to refresh the page
            header('Location: ?action=manage_themes&success=' . urlencode($success));
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Handle plugin activation/deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_plugin') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to manage plugins';
    } else {
        $pluginName = $_POST['plugin_name'] ?? '';
        $active = $_POST['active'] === 'true';
        
        $pluginsFile = PLUGIN_CONFIG;
        $activePlugins = file_exists($pluginsFile) ? json_decode(file_get_contents($pluginsFile), true) : [];
        if (!is_array($activePlugins)) $activePlugins = [];
        
        if ($active) {
            if (!in_array($pluginName, $activePlugins)) {
                $activePlugins[] = $pluginName;
            }
        } else {
            $activePlugins = array_filter($activePlugins, function($p) use ($pluginName) {
                return $p !== $pluginName;
            });
        }
        
        file_put_contents($pluginsFile, json_encode(array_values($activePlugins)));
        $success = $active ? "Plugin '$pluginName' activated" : "Plugin '$pluginName' deactivated";
    }
}

// Handle menu management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_menu') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to manage menus';
    } else {
        $menuData = $_POST['menu_data'] ?? '';
        $menuFile = ADMIN_CONFIG_DIR . '/menus.json';
        
        if (!empty($menuData)) {
            $menuData = json_decode($menuData, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                file_put_contents($menuFile, json_encode($menuData, JSON_PRETTY_PRINT));
                $success = 'Menu saved successfully';
            } else {
                $error = 'Invalid menu data format';
            }
        } else {
            $error = 'No menu data provided';
        }
    }
}

require("version.php");

output_template:

unset($_GET['page']);

if (!isLoggedIn()) {
    $template = file_get_contents(ADMIN_TEMPLATE_DIR . '/login.html');
    $template = str_replace('{{error}}', $error ?? '', $template);
} else {
    $template = file_get_contents(ADMIN_TEMPLATE_DIR . '/dashboard.html');

    $isUserManagement = isset($_GET['action']) && $_GET['action'] === 'manage_users';
    $isThemeManagement = isset($_GET['action']) && $_GET['action'] === 'manage_themes';
    $isPluginManagement = isset($_GET['action']) && $_GET['action'] === 'manage_plugins';
    $isContentEditor = isset($_GET['edit']) && !empty($_GET['edit']) && !isset($_GET['action']);
    $isMenuManagement = isset($_GET['action']) && $_GET['action'] === 'manage_menus';
    $isWidgetManagement = isset($_GET['action']) && $_GET['action'] === 'manage_widgets';

    // Check for plugin admin sections
    $isPluginSection = false;
    $pluginSectionContent = '';
    if (isset($_GET['action'])) {
        $sections = fcms_get_admin_sections();
        foreach ($sections as $id => $section) {
            if ($_GET['action'] === $id) {
                $isPluginSection = true;
                $pluginSectionContent = call_user_func($section['render_callback']);
                break;
            }
        }
    }

    if ($isPluginSection) {
        $template = preg_replace('/\{\{if_plugin_section\}\}(.*?)\{\{\/if_plugin_section\}\}/s', '$1', $template);
        $template = str_replace('{{plugin_section_content}}', $pluginSectionContent, $template);
        $template = preg_replace('/\{\{if_user_management\}\}.*?\{\{\/if_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_theme_management\}\}.*?\{\{\/if_theme_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_management\}\}.*?\{\{\/if_plugin_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_menu_management\}\}.*?\{\{\/if_menu_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_widget_management\}\}.*?\{\{\/if_widget_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_user_management\}\}.*?\{\{\/if_not_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}.*?\{\{\/if_not_content_editor\}\}/s', '', $template);
    }
    else if ($isWidgetManagement) {
        $template = preg_replace('/\{\{if_widget_management\}\}(.*?)\{\{\/if_widget_management\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_user_management\}\}.*?\{\{\/if_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_theme_management\}\}.*?\{\{\/if_theme_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_management\}\}.*?\{\{\/if_plugin_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_menu_management\}\}.*?\{\{\/if_menu_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_user_management\}\}.*?\{\{\/if_not_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}.*?\{\{\/if_not_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_section\}\}.*?\{\{\/if_plugin_section\}\}/s', '', $template);
    
        $widgetsFile = ADMIN_CONFIG_DIR . '/widgets.json';
        
        // Get theme sidebars
        $themeSidebars = get_theme_sidebars();
        error_log('Theme sidebars detected: ' . print_r($themeSidebars, true));
        
        // Get existing widget configuration
        $widgets = file_exists($widgetsFile) ? json_decode(file_get_contents($widgetsFile), true) : [];
        error_log('Existing widgets configuration: ' . print_r($widgets, true));
        
        // Merge theme sidebars with existing configuration
        foreach ($themeSidebars as $id => $sidebar) {
            if (!isset($widgets[$id])) {
                // Add new sidebar from theme
                $widgets[$id] = $sidebar;
            } else {
                // Keep existing widgets but update sidebar name
                $existingWidgets = $widgets[$id]['widgets'] ?? [];
                $widgets[$id] = $sidebar;
                $widgets[$id]['widgets'] = $existingWidgets;
            }
        }
        
        // Save updated configuration
        file_put_contents($widgetsFile, json_encode($widgets, JSON_PRETTY_PRINT));
        
        // Get current sidebar
        $currentSidebar = $_GET['sidebar'] ?? array_key_first($widgets) ?? '';
        
        // Build sidebar selection HTML
        $sidebarSelectionHtml = '
        <form method="GET" class="mb-4">
            <input type="hidden" name="action" value="manage_widgets">
            <div class="flex items-center">
                <label for="sidebar" class="mr-2">Select Sidebar:</label>
                <select name="sidebar" id="sidebar" onchange="this.form.submit()" class="border rounded px-2 py-1 flex-grow">';
        
        if (!empty($widgets)) {
            foreach ($widgets as $id => $sidebar) {
                $selected = ($id === $currentSidebar) ? ' selected' : '';
                $sidebarSelectionHtml .= sprintf(
                    '<option value="%s"%s>%s</option>',
                    htmlspecialchars($id),
                    $selected,
                    htmlspecialchars($sidebar['name'])
                );
            }
        } else {
            $sidebarSelectionHtml .= '<option value="">No sidebars detected in theme</option>';
        }
        
        $sidebarSelectionHtml .= '</select>';
        
        if ($currentSidebar) {
            $sidebarSelectionHtml .= sprintf(
                '<button type="button" onclick="deleteSidebar(\'%s\')" class="bg-red-500 text-white px-2 py-1 rounded text-sm ml-2">Delete Sidebar</button>',
                htmlspecialchars($currentSidebar)
            );
        }
        
        $sidebarSelectionHtml .= '</div></form>';
        
        // Update the template replacement
        $template = str_replace('{{sidebar_selection}}', $sidebarSelectionHtml, $template);
        // Remove the old replacement if it exists
        $template = str_replace('{{sidebar_options}}', '', $template);
        
        // Build widget list
        $widgetList = '';
        if ($currentSidebar && isset($widgets[$currentSidebar])) {
            foreach ($widgets[$currentSidebar]['widgets'] as $widget) {
                $widgetList .= '<div class="widget-item border rounded p-4 mb-4" data-widget-id="' . htmlspecialchars($widget['id']) . '">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="font-medium widget-handle cursor-move">↕ ' . htmlspecialchars($widget['title']) . '</h3>
                        <div class="space-x-2">
                            <button type="button" onclick="editWidget(\'' . htmlspecialchars($widget['id']) . '\')" 
                                    class="bg-blue-500 text-white px-2 py-1 rounded text-sm">Edit</button>
                            <button type="button" onclick="deleteWidget(\'' . htmlspecialchars($widget['id']) . '\', \'' . htmlspecialchars($currentSidebar) . '\')" 
                                    class="bg-red-500 text-white px-2 py-1 rounded text-sm">Delete</button>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600">Type: ' . htmlspecialchars($widget['type']) . '</div>
                </div>';
            }
        }
        
        // Replace template placeholders
        $template = str_replace('{{sidebar_options}}', $sidebarSelectionHtml, $template);
        $template = str_replace('{{widget_list}}', $widgetList, $template);
        $template = str_replace('{{current_sidebar}}', htmlspecialchars($currentSidebar), $template);
    }
    else if ($isContentEditor) {
        $template = preg_replace('/\{\{if_content_editor\}\}(.*?)\{\{\/if_content_editor\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_user_management\}\}.*?\{\{\/if_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_theme_management\}\}.*?\{\{\/if_theme_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_management\}\}.*?\{\{\/if_plugin_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_menu_management\}\}.*?\{\{\/if_menu_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_widget_management\}\}.*?\{\{\/if_widget_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_user_management\}\}.*?\{\{\/if_not_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}.*?\{\{\/if_not_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_section\}\}.*?\{\{\/if_plugin_section\}\}/s', '', $template);

        $fileName = $_GET['edit'];
        $filePath = CONTENT_DIR . '/' . $fileName;
        
        if (!file_exists($filePath)) {
            header('Location: /admin/');
            exit;
        }

        $fileContent = file_get_contents($filePath);
        $pageTitle = '';

        // Extract title from JSON frontmatter if it exists
        if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $fileContent, $matches)) {
            $metadata = json_decode($matches[1], true);
            if ($metadata && isset($metadata['title'])) {
                $pageTitle = $metadata['title'];
            }
        }

        $template = str_replace('{{file_name}}', htmlspecialchars($fileName), $template);
        $template = str_replace('{{file_content}}', json_encode($fileContent), $template);
        $template = str_replace('{{page_title}}', htmlspecialchars($pageTitle), $template);
    }
    else if ($isUserManagement) {
        $template = preg_replace('/\{\{if_user_management\}\}(.*?)\{\{\/if_user_management\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_theme_management\}\}.*?\{\{\/if_theme_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_management\}\}.*?\{\{\/if_plugin_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_menu_management\}\}.*?\{\{\/if_menu_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_widget_management\}\}.*?\{\{\/if_widget_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_user_management\}\}.*?\{\{\/if_not_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}.*?\{\{\/if_not_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_section\}\}.*?\{\{\/if_plugin_section\}\}/s', '', $template);

        $users = json_decode(file_get_contents($usersFile), true);
        $userList = '';
        foreach ($users as $user) {
            $userList .= "<tr>
                <td class='py-2 px-4'>" . htmlspecialchars($user['username']) . "</td>
                <td class='py-2 px-4'>
                    <button onclick='editUser(\"" . htmlspecialchars($user['username']) . "\")' 
                            class='bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 mr-2'>
                        Edit
                    </button>
                    <button onclick='deleteUser(\"" . htmlspecialchars($user['username']) . "\")' 
                            class='bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600'>
                        Delete
                    </button>
                </td>
            </tr>";
        }
        $template = str_replace('{{user_list}}', $userList, $template);
    }
    else if ($isThemeManagement) {
        $template = preg_replace('/\{\{if_theme_management\}\}(.*?)\{\{\/if_theme_management\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_user_management\}\}.*?\{\{\/if_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_management\}\}.*?\{\{\/if_plugin_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_menu_management\}\}.*?\{\{\/if_menu_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_widget_management\}\}.*?\{\{\/if_widget_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_user_management\}\}.*?\{\{\/if_not_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}.*?\{\{\/if_not_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_section\}\}.*?\{\{\/if_plugin_section\}\}/s', '', $template);

        $themes = $themeManager->getThemes();
        $themesHtml = '';
        
        foreach ($themes as $theme) {
            $themesHtml .= '<div class="border rounded-lg p-4 ' . ($theme['active'] ? 'ring-2 ring-green-500' : '') . '">
                <h3 class="text-lg font-medium mb-2">' . htmlspecialchars($theme['name']) . '</h3>
                <p class="text-sm text-gray-600 mb-4">' . htmlspecialchars($theme['description']) . '</p>
                <div class="text-sm text-gray-500 mb-4">
                    <p>Version: ' . htmlspecialchars($theme['version']) . '</p>
                    <p>Author: ' . htmlspecialchars($theme['author']) . '</p>
                </div>';
                
            if ($theme['active']) {
                $themesHtml .= '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Active Theme</span>';
            } else {
                $themesHtml .= '<form method="POST" action="">
                    <input type="hidden" name="action" value="activate_theme" />
                    <input type="hidden" name="theme" value="' . htmlspecialchars($theme['id']) . '" />
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Activate Theme</button>
                </form>';
            }
            
            $themesHtml .= '</div>';
        }

        // Replace the Mustache-style template with our generated HTML
        $template = preg_replace('/\{\{#themes\}\}.*?\{\{\/themes\}\}/s', $themesHtml, $template);
    }
    else if ($isPluginManagement) {
        $template = preg_replace('/\{\{if_plugin_management\}\}(.*?)\{\{\/if_plugin_management\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_user_management\}\}.*?\{\{\/if_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_theme_management\}\}.*?\{\{\/if_theme_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_menu_management\}\}.*?\{\{\/if_menu_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_widget_management\}\}.*?\{\{\/if_widget_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_user_management\}\}.*?\{\{\/if_not_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}.*?\{\{\/if_not_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_section\}\}.*?\{\{\/if_plugin_section\}\}/s', '', $template);
        
        // Get plugin data
        $pluginsDir = PROJECT_ROOT . '/plugins';
        $activePlugins = file_exists(PLUGIN_CONFIG) ? json_decode(file_get_contents(PLUGIN_CONFIG), true) : [];
        if (!is_array($activePlugins)) $activePlugins = [];
        
        $pluginsHtml = '';
        foreach (glob($pluginsDir . '/*', GLOB_ONLYDIR) as $pluginDir) {
            $pluginId = basename($pluginDir);
            $pluginFile = $pluginDir . '/' . $pluginId . '.php';
            
            if (file_exists($pluginFile)) {
                $pluginData = [
                    'id' => $pluginId,
                    'name' => ucfirst($pluginId),
                    'description' => 'A plugin for FearlessCMS',
                    'version' => '1.0',
                    'author' => 'Unknown',
                    'active' => in_array($pluginId, $activePlugins)
                ];
                
                // Try to extract plugin metadata from file
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
                
                $pluginsHtml .= '<div class="border rounded-lg p-4 ' . ($pluginData['active'] ? 'ring-2 ring-green-500' : '') . '">
                    <h3 class="text-lg font-medium mb-2">' . htmlspecialchars($pluginData['name']) . '</h3>
                    <p class="text-sm text-gray-600 mb-4">' . htmlspecialchars($pluginData['description']) . '</p>
                    <div class="text-sm text-gray-500 mb-4">
                        <p>Version: ' . htmlspecialchars($pluginData['version']) . '</p>
                        <p>Author: ' . htmlspecialchars($pluginData['author']) . '</p>
                    </div>';
                    
                if ($pluginData['active']) {
                    $pluginsHtml .= '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Active</span>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="toggle_plugin" />
                        <input type="hidden" name="plugin_name" value="' . htmlspecialchars($pluginData['id']) . '" />
                        <input type="hidden" name="active" value="false" />
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 mt-2">Deactivate</button>
                    </form>';
                } else {
                    $pluginsHtml .= '<form method="POST" action="">
                        <input type="hidden" name="action" value="toggle_plugin" />
                        <input type="hidden" name="plugin_name" value="' . htmlspecialchars($pluginData['id']) . '" />
                        <input type="hidden" name="active" value="true" />
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Activate</button>
                    </form>';
                }
                
                $pluginsHtml .= '</div>';
            }
        }
        
        // Replace the Mustache-style template with our generated HTML
        $template = preg_replace('/\{\{#plugins\}\}.*?\{\{\/plugins\}\}/s', $pluginsHtml, $template);
    }
else if ($isMenuManagement) {
    // First, make sure we're keeping the menu management section and removing others
    $template = preg_replace('/\{\{if_menu_management\}\}.*?\{\{\/if_menu_management\}\}/s', 
        '{{if_menu_management}}<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8"><div class="bg-white shadow rounded-lg p-6">{{menu_editor}}</div></div>{{/if_menu_management}}', 
        $template);
    
    $template = preg_replace('/\{\{if_user_management\}\}.*?\{\{\/if_user_management\}\}/s', '', $template);
    $template = preg_replace('/\{\{if_theme_management\}\}.*?\{\{\/if_theme_management\}\}/s', '', $template);
    $template = preg_replace('/\{\{if_plugin_management\}\}.*?\{\{\/if_plugin_management\}\}/s', '', $template);
    $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
    $template = preg_replace('/\{\{if_widget_management\}\}.*?\{\{\/if_widget_management\}\}/s', '', $template);
    $template = preg_replace('/\{\{if_not_user_management\}\}.*?\{\{\/if_not_user_management\}\}/s', '', $template);
    $template = preg_replace('/\{\{if_not_content_editor\}\}.*?\{\{\/if_not_content_editor\}\}/s', '', $template);
    $template = preg_replace('/\{\{if_plugin_section\}\}.*?\{\{\/if_plugin_section\}\}/s', '', $template);
    
    // Now process the if_menu_management section
    $template = preg_replace('/\{\{if_menu_management\}\}(.*?)\{\{\/if_menu_management\}\}/s', '$1', $template);
    
    // Load existing menus
    $menuFile = ADMIN_CONFIG_DIR . '/menus.json';
    $menus = file_exists($menuFile) ? json_decode(file_get_contents($menuFile), true) : ['main' => ['menu_class' => 'main-nav', 'items' => []]];
    
    // Get current menu
    $currentMenu = $_GET['menu'] ?? 'main';
    
    // Build menu selection HTML
    $menuSelectionHtml = '
    <form method="GET" class="mb-4">
        <input type="hidden" name="action" value="manage_menus">
        <div class="flex items-center">
            <label for="menu" class="mr-2">Select Menu:</label>
            <select name="menu" id="menu" onchange="this.form.submit()" class="border rounded px-2 py-1 flex-grow">';
    
    foreach ($menus as $id => $menu) {
        $selected = ($id === $currentMenu) ? ' selected' : '';
        $menuSelectionHtml .= sprintf(
            '<option value="%s"%s>%s</option>',
            htmlspecialchars($id),
            $selected,
            htmlspecialchars(ucfirst($id) . ' Menu')
        );
    }
    
    $menuSelectionHtml .= '</select>';
    $menuSelectionHtml .= '</div></form>';
    
    // Build menu items HTML
    $menuItemsHtml = '';
    if (isset($menus[$currentMenu]['items'])) {
        foreach ($menus[$currentMenu]['items'] as $index => $item) {
            $menuItemsHtml .= '
            <div class="menu-item border rounded p-4 mb-4" data-item-index="' . $index . '">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-medium menu-handle cursor-move">↕ ' . htmlspecialchars($item['label']) . '</h3>
                    <div class="space-x-2">
                        <button type="button" onclick="editMenuItem(' . $index . ')" 
                                class="bg-blue-500 text-white px-2 py-1 rounded text-sm">Edit</button>
                        <button type="button" onclick="deleteMenuItem(' . $index . ')" 
                                class="bg-red-500 text-white px-2 py-1 rounded text-sm">Delete</button>
                    </div>
                </div>
                <div class="text-sm text-gray-600">URL: ' . htmlspecialchars($item['url']) . '</div>
            </div>';
        }
    }
    
    // Create menu editor HTML
    $menuEditorHtml = '
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6 fira-code">Menu Management</h2>
        ' . $menuSelectionHtml . '
        
        <div class="mb-4">
            <label class="block mb-1">Menu Class:</label>
            <input type="text" id="menu-class" value="' . htmlspecialchars($menus[$currentMenu]['menu_class'] ?? 'main-nav') . '" 
                   class="w-full border rounded px-2 py-1">
        </div>
        
        <div id="menu-items-container" class="mb-4">
            ' . $menuItemsHtml . '
        </div>
        
        <button type="button" onclick="addMenuItem()" class="bg-blue-500 text-white px-3 py-1 rounded mb-4">Add Menu Item</button>
        
        <div class="flex justify-between">
            <button type="button" onclick="saveMenu()" class="bg-green-500 text-white px-4 py-2 rounded">Save Menu</button>
            <button type="button" onclick="addNewMenu()" class="bg-purple-500 text-white px-4 py-2 rounded">Create New Menu</button>
        </div>
    </div>
    
    <!-- Menu Item Edit Modal -->
    <div id="menuItemModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h3 class="text-xl font-bold mb-4">Edit Menu Item</h3>
            <form id="menu-item-form" class="space-y-4">
                <input type="hidden" id="edit-item-index" value="-1">
                <div>
                    <label class="block mb-1">Label:</label>
                    <input type="text" id="item-label" class="w-full px-3 py-2 border border-gray-300 rounded">
                </div>
                <div>
                    <label class="block mb-1">URL:</label>
                    <input type="text" id="item-url" class="w-full px-3 py-2 border border-gray-300 rounded">
                </div>
                <div>
                    <label class="block mb-1">CSS Class:</label>
                    <input type="text" id="item-class" class="w-full px-3 py-2 border border-gray-300 rounded">
                </div>
                <div>
                    <label class="block mb-1">Target:</label>
                    <select id="item-target" class="w-full px-3 py-2 border border-gray-300 rounded">
                        <option value="">Same Window</option>
                        <option value="_blank">New Window</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeMenuItemModal()" class="bg-gray-400 text-white px-4 py-2 rounded">Cancel</button>
                    <button type="button" onclick="saveMenuItem()" class="bg-blue-500 text-white px-4 py-2 rounded">Save</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- New Menu Modal -->
    <div id="newMenuModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h3 class="text-xl font-bold mb-4">Create New Menu</h3>
            <form id="new-menu-form" class="space-y-4">
                <div>
                    <label class="block mb-1">Menu ID:</label>
                    <input type="text" id="new-menu-id" class="w-full px-3 py-2 border border-gray-300 rounded" placeholder="e.g., footer">
                </div>
                <div>
                    <label class="block mb-1">Menu Class:</label>
                    <input type="text" id="new-menu-class" class="w-full px-3 py-2 border border-gray-300 rounded" value="menu-nav">
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeNewMenuModal()" class="bg-gray-400 text-white px-4 py-2 rounded">Cancel</button>
                    <button type="button" onclick="createNewMenu()" class="bg-blue-500 text-white px-4 py-2 rounded">Create</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <script>
        // Current menu data
        let currentMenu = "' . $currentMenu . '";
        let menuData = ' . json_encode($menus) . ';
        
        // Initialize Sortable
        document.addEventListener("DOMContentLoaded", function() {
            const container = document.getElementById("menu-items-container");
            if (container) {
                new Sortable(container, {
                    animation: 150,
                    handle: ".menu-handle",
                    onEnd: function() {
                        // Update the order in the menuData object
                        const items = document.querySelectorAll(".menu-item");
                        const newItems = [];
                        
                        items.forEach(item => {
                            const index = parseInt(item.dataset.itemIndex);
                            newItems.push(menuData[currentMenu].items[index]);
                        });
                        
                        menuData[currentMenu].items = newItems;
                        
                        // Update data-item-index attributes
                        items.forEach((item, i) => {
                            item.dataset.itemIndex = i;
                        });
                    }
                });
            }
        });
        
        function addMenuItem() {
            const newItem = {
                label: "New Item",
                url: "/",
                item_class: "",
                target: ""
            };
            
            if (!menuData[currentMenu].items) {
                menuData[currentMenu].items = [];
            }
            
            menuData[currentMenu].items.push(newItem);
            const index = menuData[currentMenu].items.length - 1;
            
            const itemHtml = `
                <div class="menu-item border rounded p-4 mb-4" data-item-index="${index}">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="font-medium menu-handle cursor-move">↕ ${newItem.label}</h3>
                        <div class="space-x-2">
                            <button type="button" onclick="editMenuItem(${index})" 
                                    class="bg-blue-500 text-white px-2 py-1 rounded text-sm">Edit</button>
                            <button type="button" onclick="deleteMenuItem(${index})" 
                                    class="bg-red-500 text-white px-2 py-1 rounded text-sm">Delete</button>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600">URL: ${newItem.url}</div>
                </div>
            `;
            
            document.getElementById("menu-items-container").insertAdjacentHTML("beforeend", itemHtml);
            editMenuItem(index);
        }
        
        function editMenuItem(index) {
            const item = menuData[currentMenu].items[index];
            document.getElementById("edit-item-index").value = index;
            document.getElementById("item-label").value = item.label;
            document.getElementById("item-url").value = item.url;
            document.getElementById("item-class").value = item.item_class || "";
            document.getElementById("item-target").value = item.target || "";
            
            document.getElementById("menuItemModal").classList.remove("hidden");
        }
        
        function closeMenuItemModal() {
            document.getElementById("menuItemModal").classList.add("hidden");
        }
        
        function saveMenuItem() {
            const index = parseInt(document.getElementById("edit-item-index").value);
            const label = document.getElementById("item-label").value;
            const url = document.getElementById("item-url").value;
            const itemClass = document.getElementById("item-class").value;
            const target = document.getElementById("item-target").value;
            
            if (!label || !url) {
                alert("Label and URL are required");
                return;
            }
            
            menuData[currentMenu].items[index] = {
                label: label,
                url: url,
                item_class: itemClass,
                target: target
            };
            
            // Update the display
            const itemElement = document.querySelector(`.menu-item[data-item-index="${index}"]`);
            itemElement.querySelector("h3").textContent = "↕ " + label;
            itemElement.querySelector(".text-gray-600").textContent = "URL: " + url;
            
            closeMenuItemModal();
        }
        
        function deleteMenuItem(index) {
            if (confirm("Are you sure you want to delete this menu item?")) {
                menuData[currentMenu].items.splice(index, 1);
                
                // Remove from DOM
                document.querySelector(`.menu-item[data-item-index="${index}"]`).remove();
                
                // Update indices
                const items = document.querySelectorAll(".menu-item");
                items.forEach((item, i) => {
                    item.dataset.itemIndex = i;
                    
                    // Update onclick handlers
                    const editBtn = item.querySelector("button:first-of-type");
                    const deleteBtn = item.querySelector("button:last-of-type");
                    
                    editBtn.setAttribute("onclick", `editMenuItem(${i})`);
                    deleteBtn.setAttribute("onclick", `deleteMenuItem(${i})`);
                });
            }
        }
        
        function saveMenu() {
            // Update menu class
            menuData[currentMenu].menu_class = document.getElementById("menu-class").value;
            
            // Send to server
            fetch("", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: `action=save_menu&menu_data=${encodeURIComponent(JSON.stringify(menuData))}`
            })
            .then(response => response.text())
            .then(() => {
                alert("Menu saved successfully");
                location.reload();
            })
            .catch(error => {
                console.error("Error saving menu:", error);
                alert("Error saving menu");
            });
        }
        
        function addNewMenu() {
            document.getElementById("newMenuModal").classList.remove("hidden");
        }
        
        function closeNewMenuModal() {
            document.getElementById("newMenuModal").classList.add("hidden");
        }
        
        function createNewMenu() {
            const menuId = document.getElementById("new-menu-id").value.trim();
            const menuClass = document.getElementById("new-menu-class").value.trim();
            
            if (!menuId) {
                alert("Menu ID is required");
                return;
            }
            
            if (menuData[menuId]) {
                alert("A menu with this ID already exists");
                return;
            }
            
            menuData[menuId] = {
                menu_class: menuClass,
                items: []
            };
            
            // Save and redirect to the new menu
            fetch("", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: `action=save_menu&menu_data=${encodeURIComponent(JSON.stringify(menuData))}`
            })
            .then(response => response.text())
            .then(() => {
                window.location.href = `?action=manage_menus&menu=${encodeURIComponent(menuId)}`;
            })
            .catch(error => {
                console.error("Error creating menu:", error);
                alert("Error creating menu");
            });
        }
    </script>
    ';
    
    // Directly insert the menu editor HTML into the template
    $template = str_replace('{{menu_editor}}', $menuEditorHtml, $template);
}

    else {
        // Show content management section, hide other sections
        $template = preg_replace('/\{\{if_not_user_management\}\}(.*?)\{\{\/if_not_user_management\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}(.*?)\{\{\/if_not_content_editor\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_user_management\}\}.*?\{\{\/if_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_theme_management\}\}.*?\{\{\/if_theme_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_management\}\}.*?\{\{\/if_plugin_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_menu_management\}\}.*?\{\{\/if_menu_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_section\}\}.*?\{\{\/if_plugin_section\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_widget_management\}\}.*?\{\{\/if_widget_management\}\}/s', '', $template);

        // Get list of content files
        $contentFiles = glob(CONTENT_DIR . '/*.md');
        $contentList = '';
        foreach ($contentFiles as $file) {
            $filename = basename($file);
            $fileContent = file_get_contents($file);
            $displayName = $filename;
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

    // Add plugin admin sections to the navigation
    $pluginSections = fcms_get_admin_sections();
    $pluginNavItems = '';
    foreach ($pluginSections as $id => $section) {
        $pluginNavItems .= '<a href="?action=' . htmlspecialchars($id) . '" class="hover:text-green-200">' . htmlspecialchars($section['label']) . '</a>';
    }
    $template = str_replace('{{plugin_nav_items}}', $pluginNavItems, $template);

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
    $template = str_replace('{{app_version}}', htmlspecialchars(APP_VERSION), $template);
}

// ...after loading the dashboard template...
$template = str_replace('{{site_name}}', htmlspecialchars($siteName), $template);

echo $template;
?>
