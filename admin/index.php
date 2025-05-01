<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('PROJECT_ROOT', dirname(__DIR__));
define('ADMIN_CONFIG_DIR', __DIR__ . '/config');
define('ADMIN_TEMPLATE_DIR', __DIR__ . '/templates');
define('CONTENT_DIR', __DIR__ . '/../content');

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
    }
    else if ($isMenuManagement) {
        $template = preg_replace('/\{\{if_menu_management\}\}(.*?)\{\{\/if_menu_management\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_user_management\}\}.*?\{\{\/if_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_theme_management\}\}.*?\{\{\/if_theme_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_management\}\}.*?\{\{\/if_plugin_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_widget_management\}\}.*?\{\{\/if_widget_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_user_management\}\}.*?\{\{\/if_not_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}.*?\{\{\/if_not_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_section\}\}.*?\{\{\/if_plugin_section\}\}/s', '', $template);
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

echo $template;
