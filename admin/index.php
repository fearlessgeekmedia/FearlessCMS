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

require_once PROJECT_ROOT . '/includes/ThemeManager.php';
require_once PROJECT_ROOT . '/includes/plugins.php';

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

$configFile = CONFIG_DIR . '/config.json';
$config = [];
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
}
$siteName = $config['site_name'] ?? 'FearlessCMS';

$customCodeFile = CONFIG_DIR . '/custom_code.json';
$customCss = '';
$customJs = '';
if (file_exists($customCodeFile)) {
    $customCode = json_decode(file_get_contents($customCodeFile), true);
    $customCss = $customCode['css'] ?? '';
    $customJs = $customCode['js'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_site_name') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to update the site name';
    } else {
        $newSiteName = trim($_POST['site_name'] ?? '');
        if ($newSiteName === '') {
            $error = 'Site name cannot be empty';
        } else {
            $config['site_name'] = $newSiteName;
            file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
            $siteName = $newSiteName;
            $success = 'Site name updated successfully';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_custom_code') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to update custom code';
    } else {
        $customCss = $_POST['custom_css'] ?? '';
        $customJs = $_POST['custom_js'] ?? '';
        $customCode = [
            'css' => $customCss,
            'js' => $customJs
        ];
        file_put_contents($customCodeFile, json_encode($customCode, JSON_PRETTY_PRINT));
        $success = 'Custom code updated successfully';
    }
}

function get_theme_sidebars() {
    global $themeManager;
    $sidebars = [];
    $activeTheme = $themeManager->getActiveTheme();
    $templatesDir = PROJECT_ROOT . '/themes/' . $activeTheme . '/templates';
    if (!is_dir($templatesDir)) return $sidebars;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'activate_theme') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to manage themes';
    } else {
        $themeName = $_POST['theme'] ?? '';
        try {
            $themeManager->setActiveTheme($themeName);
            $success = "Theme '$themeName' activated successfully";
            header('Location: ?action=manage_themes&success=' . urlencode($success));
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
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

require_once PROJECT_ROOT . '/version.php';

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
    } else if ($isWidgetManagement) {
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
        $themeSidebars = get_theme_sidebars();
        $widgets = file_exists($widgetsFile) ? json_decode(file_get_contents($widgetsFile), true) : [];
        foreach ($themeSidebars as $id => $sidebar) {
            if (!isset($widgets[$id])) {
                $widgets[$id] = $sidebar;
            } else {
                $existingWidgets = $widgets[$id]['widgets'] ?? [];
                $widgets[$id] = $sidebar;
                $widgets[$id]['widgets'] = $existingWidgets;
            }
        }
        file_put_contents($widgetsFile, json_encode($widgets, JSON_PRETTY_PRINT));
        $currentSidebar = $_GET['sidebar'] ?? array_key_first($widgets) ?? '';
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

        $template = str_replace('{{sidebar_selection}}', $sidebarSelectionHtml, $template);
        $template = str_replace('{{widget_list}}', $widgetList, $template);
        $template = str_replace('{{current_sidebar}}', htmlspecialchars($currentSidebar), $template);
    } else if ($isContentEditor) {
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
        $editorMode = 'easy';

        if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $fileContent, $matches)) {
            $metadata = json_decode($matches[1], true);
            if ($metadata && isset($metadata['title'])) {
                $pageTitle = $metadata['title'];
            }
            if ($metadata && isset($metadata['editor_mode'])) {
                $editorMode = $metadata['editor_mode'];
            }
        }

        $editContent = preg_replace('/^<!--\s*json\s*.*?\s*-->\s*/s', '', $fileContent);

        $template = str_replace('{{file_name}}', htmlspecialchars($fileName), $template);
        $template = str_replace('{{file_content}}', json_encode($editContent), $template);
        $template = str_replace('{{page_title}}', htmlspecialchars($pageTitle), $template);
        $template = str_replace('{{editor_mode}}', htmlspecialchars($editorMode), $template);
        $template = str_replace('{{#if_easy_mode}}', $editorMode === 'easy' ? 'selected' : '', $template);
        $template = str_replace('{{#if_advanced_mode}}', $editorMode === 'advanced' ? 'selected' : '', $template);
    } else if ($isUserManagement) {
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
    } else if ($isThemeManagement) {
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
        $template = preg_replace('/\{\{#themes\}\}.*?\{\{\/themes\}\}/s', $themesHtml, $template);
    } else if ($isPluginManagement) {
        $template = preg_replace('/\{\{if_plugin_management\}\}(.*?)\{\{\/if_plugin_management\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_user_management\}\}.*?\{\{\/if_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_theme_management\}\}.*?\{\{\/if_theme_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_menu_management\}\}.*?\{\{\/if_menu_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_widget_management\}\}.*?\{\{\/if_widget_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_user_management\}\}.*?\{\{\/if_not_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}.*?\{\{\/if_not_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_section\}\}.*?\{\{\/if_plugin_section\}\}/s', '', $template);

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
        $template = preg_replace('/\{\{#plugins\}\}.*?\{\{\/plugins\}\}/s', $pluginsHtml, $template);
   } else if ($isMenuManagement) {
        $template = preg_replace('/\{\{if_menu_management\}\}(.*?)\{\{\/if_menu_management\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_user_management\}\}.*?\{\{\/if_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_theme_management\}\}.*?\{\{\/if_theme_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_management\}\}.*?\{\{\/if_plugin_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_widget_management\}\}.*?\{\{\/if_widget_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_user_management\}\}.*?\{\{\/if_not_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}.*?\{\{\/if_not_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_section\}\}.*?\{\{\/if_plugin_section\}\}/s', '', $template);
    
        // --- Menu Management HTML generation ---
        $menuFile = ADMIN_CONFIG_DIR . '/menus.json';
        $menus = file_exists($menuFile) ? json_decode(file_get_contents($menuFile), true) : ['main' => ['menu_class' => 'main-nav', 'items' => []]];
        $currentMenu = $_GET['menu'] ?? array_key_first($menus) ?? 'main';
    
        $menuSelectionHtml = '<form method="GET" class="mb-4">
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
        $menuSelectionHtml .= '</select></div></form>';
    
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
        <!-- (Add your menu item modal and JS here) -->
        ';
    
        $template = str_replace('{{menu_editor}}', $menuEditorHtml, $template);
    } else {
        $template = preg_replace('/\{\{if_not_user_management\}\}(.*?)\{\{\/if_not_user_management\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}(.*?)\{\{\/if_not_content_editor\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_user_management\}\}.*?\{\{\/if_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_theme_management\}\}.*?\{\{\/if_theme_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_management\}\}.*?\{\{\/if_plugin_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_menu_management\}\}.*?\{\{\/if_menu_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_section\}\}.*?\{\{\/if_plugin_section\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_widget_management\}\}.*?\{\{\/if_widget_management\}\}/s', '', $template);

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

    $pluginSections = fcms_get_admin_sections();
    $pluginNavItems = '';
    foreach ($pluginSections as $id => $section) {
        $pluginNavItems .= '<a href="?action=' . htmlspecialchars($id) . '" class="hover:text-green-200">' . htmlspecialchars($section['label']) . '</a>';
    }
    $template = str_replace('{{plugin_nav_items}}', $pluginNavItems, $template);

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
    $template = str_replace('{{site_name}}', htmlspecialchars($siteName), $template);
    $template = str_replace('{{custom_css}}', htmlspecialchars($customCss), $template);
    $template = str_replace('{{custom_js}}', htmlspecialchars($customJs), $template);
    $template = str_replace('{{username}}', htmlspecialchars($_SESSION['username']), $template);
    $template = str_replace('{{app_version}}', htmlspecialchars(APP_VERSION), $template);
}

echo $template;
?>

