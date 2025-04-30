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

require("login-handler.php");
require("logout-handler.php");
require("pchange-handler.php");
require("newuser-handler.php");
require("edituser-handler.php");
require("deluser-handler.php");
require("newpage-handler.php");
require("filesave-handler.php");
require("filedel-handler.php");

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
    $isContentEditor = isset($_GET['edit']) && !empty($_GET['edit']);
    $isMenuManagement = isset($_GET['action']) && $_GET['action'] === 'manage_menus';
    
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
                $template = preg_replace('/\{\{if_plugin_management\}\}.*?\{\{\/if_plugin_management\}\}/s', '', $template);
                $template = preg_replace('/\{\{if_menu_management\}\}.*?\{\{\/if_menu_management\}\}/s', '', $template);
                $template = preg_replace('/\{\{if_plugin_section\}\}.*?\{\{\/if_plugin_section\}\}/s', '', $template);

                $template = str_replace('{{file_name}}', htmlspecialchars($fileName), $template);
                $template = str_replace('{{page_title}}', htmlspecialchars($pageTitle), $template);
                $template = str_replace('{{file_content}}', json_encode($fileContent), $template);
            }
        }
    } else if ($isUserManagement) {
        $template = preg_replace('/\{\{if_user_management\}\}(.*?)\{\{\/if_user_management\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_not_user_management\}\}.*?\{\{\/if_not_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_theme_management\}\}.*?\{\{\/if_theme_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_management\}\}.*?\{\{\/if_plugin_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}.*?\{\{\/if_not_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_menu_management\}\}.*?\{\{\/if_menu_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_section\}\}.*?\{\{\/if_plugin_section\}\}/s', '', $template);

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
        $template = preg_replace('/\{\{if_plugin_management\}\}.*?\{\{\/if_plugin_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_menu_management\}\}.*?\{\{\/if_menu_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_user_management\}\}.*?\{\{\/if_not_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}.*?\{\{\/if_not_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_section\}\}.*?\{\{\/if_plugin_section\}\}/s', '', $template);
        
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
    } else if ($isPluginManagement) {
        $template = preg_replace('/\{\{if_plugin_management\}\}(.*?)\{\{\/if_plugin_management\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_user_management\}\}.*?\{\{\/if_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_theme_management\}\}.*?\{\{\/if_theme_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_menu_management\}\}.*?\{\{\/if_menu_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_user_management\}\}.*?\{\{\/if_not_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}.*?\{\{\/if_not_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_section\}\}.*?\{\{\/if_plugin_section\}\}/s', '', $template);
        
        // Get all plugins
        $pluginsDir = PLUGIN_DIR;
        $plugins = [];
        
        if (is_dir($pluginsDir)) {
            foreach (glob($pluginsDir . '/*', GLOB_ONLYDIR) as $pluginFolder) {
                $pluginName = basename($pluginFolder);
                $mainFile = $pluginFolder . '/' . $pluginName . '.php';
                
                if (file_exists($mainFile)) {
                    $pluginInfo = [
                        'id' => $pluginName,
                        'name' => $pluginName,
                        'description' => 'A plugin for FearlessCMS',
                        'version' => '1.0',
                        'author' => 'Unknown'
                    ];
                    
                    // Try to extract plugin metadata from the file
                    $fileContent = file_get_contents($mainFile);
                    if (preg_match('/Plugin Name:\s*(.+)$/m', $fileContent, $matches)) {
                        $pluginInfo['name'] = trim($matches[1]);
                    }
                    if (preg_match('/Description:\s*(.+)$/m', $fileContent, $matches)) {
                        $pluginInfo['description'] = trim($matches[1]);
                    }
                    if (preg_match('/Version:\s*(.+)$/m', $fileContent, $matches)) {
                        $pluginInfo['version'] = trim($matches[1]);
                    }
                    if (preg_match('/Author:\s*(.+)$/m', $fileContent, $matches)) {
                        $pluginInfo['author'] = trim($matches[1]);
                    }
                    
                    // Check if plugin is active
                    $activePlugins = file_exists(PLUGIN_CONFIG) ? json_decode(file_get_contents(PLUGIN_CONFIG), true) : [];
                    $pluginInfo['active'] = in_array($pluginName, $activePlugins);
                    
                    $plugins[] = $pluginInfo;
                }
            }
        }
        
        // Build plugin list HTML
        $pluginList = '';
        foreach ($plugins as $plugin) {
            $activeClass = $plugin['active'] ? 'ring-2 ring-green-500' : '';
            $activeLabel = $plugin['active'] ? 
                '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Active</span>' : '';
            $toggleButton = $plugin['active'] ? 
                '<form method="POST" action="">
                    <input type="hidden" name="action" value="toggle_plugin" />
                    <input type="hidden" name="plugin_name" value="'.$plugin['id'].'" />
                    <input type="hidden" name="active" value="false" />
                    <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Deactivate</button>
                </form>' : 
                '<form method="POST" action="">
                    <input type="hidden" name="action" value="toggle_plugin" />
                    <input type="hidden" name="plugin_name" value="'.$plugin['id'].'" />
                    <input type="hidden" name="active" value="true" />
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Activate</button>
                </form>';
            
            $pluginList .= '
                <div class="border rounded-lg p-4 '.$activeClass.'">
                    <h3 class="text-lg font-medium mb-2">'.htmlspecialchars($plugin['name']).'</h3>
                    <p class="text-sm text-gray-600 mb-4">'.htmlspecialchars($plugin['description']).'</p>
                    <div class="text-sm text-gray-500 mb-4">
                        <p>Version: '.htmlspecialchars($plugin['version']).'</p>
                        <p>Author: '.htmlspecialchars($plugin['author']).'</p>
                    </div>
                    '.$activeLabel.'
                    '.$toggleButton.'
                </div>';
        }
        
        // Replace the {{#plugins}} ... {{/plugins}} block with the generated HTML
        $template = preg_replace('/\{\{#plugins\}\}.*?\{\{\/plugins\}\}/s', $pluginList, $template);
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
            $menuItems = [];
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (!empty($item['label']) || !empty($item['url'])) {
                        $menuItems[] = [
                            'label' => $item['label'] ?? '',
                            'url' => $item['url'] ?? '',
                            'item_class' => $item['item_class'] ?? '',
                            'target' => $item['target'] ?? ''
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
        foreach ($themeMenus as $name => $_) {
            if (!isset($menus[$name])) {
                $menuOptions .= "<option value=\"" . htmlspecialchars($name) . "\">" . htmlspecialchars($name) . " (suggested by theme)</option>";
            }
        }

        // Build menu items HTML and use unique keys
        $menuItemsHtml = '';
        foreach ($mainMenu['items'] as $idx => $item) {
            $menuItemsHtml .= '<div class="flex space-x-2 mb-2 items-end">';
            $menuItemsHtml .= '<div><label class="block text-xs mb-1">Label</label><input type="text" name="items['.$idx.'][label]" value="'.htmlspecialchars($item['label']).'" placeholder="Label" class="border rounded px-2 py-1"></div>';
            $menuItemsHtml .= '<div><label class="block text-xs mb-1">URL</label><input type="text" name="items['.$idx.'][url]" value="'.htmlspecialchars($item['url']).'" placeholder="URL" class="border rounded px-2 py-1"></div>';
            $menuItemsHtml .= '<div><label class="block text-xs mb-1">Item Class</label><input type="text" name="items['.$idx.'][item_class]" value="'.htmlspecialchars($item['item_class'] ?? '').'" placeholder="Item Class" class="border rounded px-2 py-1"></div>';
            $menuItemsHtml .= '<div><label class="block text-xs mb-1">Target</label><input type="text" name="items['.$idx.'][target]" value="'.htmlspecialchars($item['target'] ?? '').'" placeholder="_blank" class="border rounded px-2 py-1" style="width:80px"></div>';
            $menuItemsHtml .= '<button type="button" onclick="this.parentNode.remove()" class="bg-red-500 text-white px-2 py-1 rounded h-8 mb-1">×</button>';
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
            newItem.className = 'flex space-x-2 mb-2 items-end';
            newItem.innerHTML = `
                <div>
                    <label class="block text-xs mb-1">Label</label>
                    <input type="text" name="items[\${idx}][label]" placeholder="Label" class="border rounded px-2 py-1">
                </div>
                <div>
                    <label class="block text-xs mb-1">URL</label>
                    <input type="text" name="items[\${idx}][url]" placeholder="URL" class="border rounded px-2 py-1">
                </div>
                <div>
                    <label class="block text-xs mb-1">Item Class</label>
                    <input type="text" name="items[\${idx}][item_class]" placeholder="Item Class" class="border rounded px-2 py-1">
                </div>
                <div>
                    <label class="block text-xs mb-1">Target</label>
                    <input type="text" name="items[\${idx}][target]" placeholder="_blank" class="border rounded px-2 py-1" style="width:80px">
                </div>
                <button type="button" onclick="this.parentNode.remove()" class="bg-red-500 text-white px-2 py-1 rounded h-8 mb-1">×</button>
            `;
            container.appendChild(newItem);
        }
        </script>
        HTML;

        $template = preg_replace('/\{\{if_menu_management\}\}(.*?)\{\{\/if_menu_management\}\}/s', $menuManagementHtml, $template);
        $template = preg_replace('/\{\{if_user_management\}\}.*?\{\{\/if_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_theme_management\}\}.*?\{\{\/if_theme_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_management\}\}.*?\{\{\/if_plugin_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_user_management\}\}.*?\{\{\/if_not_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}.*?\{\{\/if_not_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_section\}\}.*?\{\{\/if_plugin_section\}\}/s', '', $template);
    } else if ($isPluginSection) {
        $template = preg_replace('/\{\{if_plugin_section\}\}(.*?)\{\{\/if_plugin_section\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_user_management\}\}.*?\{\{\/if_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_theme_management\}\}.*?\{\{\/if_theme_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_management\}\}.*?\{\{\/if_plugin_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_menu_management\}\}.*?\{\{\/if_menu_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_user_management\}\}.*?\{\{\/if_not_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}.*?\{\{\/if_not_content_editor\}\}/s', '', $template);
        
        $template = str_replace('{{plugin_section_content}}', $pluginSectionContent, $template);
    } else {
        // Show content management section, hide other sections
        $template = preg_replace('/\{\{if_not_user_management\}\}(.*?)\{\{\/if_not_user_management\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_not_content_editor\}\}(.*?)\{\{\/if_not_content_editor\}\}/s', '$1', $template);
        $template = preg_replace('/\{\{if_user_management\}\}.*?\{\{\/if_user_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_theme_management\}\}.*?\{\{\/if_theme_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_management\}\}.*?\{\{\/if_plugin_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_content_editor\}\}.*?\{\{\/if_content_editor\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_menu_management\}\}.*?\{\{\/if_menu_management\}\}/s', '', $template);
        $template = preg_replace('/\{\{if_plugin_section\}\}.*?\{\{\/if_plugin_section\}\}/s', '', $template);

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
