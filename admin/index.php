<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('PROJECT_ROOT', dirname(__DIR__));
define('ADMIN_CONFIG_DIR', __DIR__ . '/config');
define('ADMIN_TEMPLATE_DIR', __DIR__ . '/templates');
define('ADMIN_INCLUDES_DIR', __DIR__ . '/includes');
define('CONTENT_DIR', __DIR__ . '/../content');
define('CONFIG_DIR', dirname(__DIR__) . '/config');

session_start();

require_once PROJECT_ROOT . '/includes/ThemeManager.php';
require_once PROJECT_ROOT . '/includes/plugins.php';

$themeManager = new ThemeManager();

// Add this:
$themeDir = PROJECT_ROOT . '/themes/' . $themeManager->getActiveTheme();

$pageTemplate = '';
$homeTemplate = '';
if (file_exists($themeDir . '/templates/page.html')) {
    $pageTemplate = file_get_contents($themeDir . '/templates/page.html');
}
if (file_exists($themeDir . '/templates/home.html')) {
    $homeTemplate = file_get_contents($themeDir . '/templates/home.html');
}

$supportsHerobanner = (strpos($pageTemplate, '{{herobanner}}') !== false) ||
                      (strpos($homeTemplate, '{{herobanner}}') !== false);

$supportsLogo = (strpos($pageTemplate, '{{logo}}') !== false) ||
                (strpos($homeTemplate, '{{logo}}') !== false);
               
// Load current options
$themeOptionsFile = CONFIG_DIR . '/theme_options.json';
$themeOptions = file_exists($themeOptionsFile) ? json_decode(file_get_contents($themeOptionsFile), true) : [];

$herobannerUrl = $themeOptions['herobanner'] ?? '';
$logoUrl = $themeOptions['logo'] ?? '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'upload_herobanner' && $supportsHerobanner && isset($_FILES['herobanner'])) {
        $file = $_FILES['herobanner'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $uploadsDir = PROJECT_ROOT . '/uploads';
                if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
                $filename = 'herobanner_' . time() . '.' . $ext;
                $target = $uploadsDir . '/' . $filename;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $themeOptions['herobanner'] = '/uploads/' . $filename;
                    file_put_contents($themeOptionsFile, json_encode($themeOptions, JSON_PRETTY_PRINT));
                    $success = 'Hero banner uploaded!';
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    $error = 'Failed to move uploaded file.';
                }
            } else {
                $error = 'Invalid file type.';
            }
        } else {
            $error = 'Upload error.';
        }
    }
    if (isset($_POST['action']) && $_POST['action'] === 'upload_logo' && $supportsLogo && isset($_FILES['logo'])) {
        $file = $_FILES['logo'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            if (in_array($ext, $allowed)) {
                $uploadsDir = PROJECT_ROOT . '/uploads';
                if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
                $filename = 'logo_' . time() . '.' . $ext;
                $target = $uploadsDir . '/' . $filename;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $themeOptions['logo'] = '/uploads/' . $filename;
                    file_put_contents($themeOptionsFile, json_encode($themeOptions, JSON_PRETTY_PRINT));
                    $success = 'Logo uploaded!';
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    $error = 'Failed to move uploaded file.';
                }
            } else {
                $error = 'Invalid file type.';
            }
        } else {
            $error = 'Upload error.';
        }
    }
}


$themeOptionsForms = '';

if ($supportsHerobanner) {
    $themeOptionsForms .= '<form method="POST" enctype="multipart/form-data" class="mb-4">
        <label class="block font-medium mb-1">Hero Banner Image:</label>';
    if ($herobannerUrl) {
        $themeOptionsForms .= '<img src="' . htmlspecialchars($herobannerUrl) . '" style="max-width:300px;max-height:120px;display:block;margin-bottom:1em;">';
    }
    $themeOptionsForms .= '<input type="file" name="herobanner" accept="image/*" class="mb-2">
        <button type="submit" name="action" value="upload_herobanner" class="bg-blue-500 text-white px-4 py-2 rounded">Upload</button>
    </form>';
}

if ($supportsLogo) {
    $themeOptionsForms .= '<form method="POST" enctype="multipart/form-data" class="mb-4">
        <label class="block font-medium mb-1">Logo Image:</label>';
    if ($logoUrl) {
        $themeOptionsForms .= '<img src="' . htmlspecialchars($logoUrl) . '" style="max-width:120px;max-height:60px;display:block;margin-bottom:1em;">';
    }
    $themeOptionsForms .= '<input type="file" name="logo" accept="image/*" class="mb-2">
        <button type="submit" name="action" value="upload_logo" class="bg-blue-500 text-white px-4 py-2 rounded">Upload</button>
    </form>';
}

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

// Add this function outside of the if/else blocks
function generate_parent_options($pages, $currentParent, $currentPage) {
    $options = '<option value="">None (Top Level)</option>';
    foreach ($pages as $slug => $page) {
        // Don't allow a page to be its own parent
        if ($slug !== $currentPage) {
            $selected = ($slug === $currentParent) ? ' selected' : '';
            $options .= '<option value="' . htmlspecialchars($slug) . '"' . $selected . '>' . 
                        htmlspecialchars($page['title']) . '</option>';
        }
    }
    return $options;
}

// Add this function near the top of the file
function get_page_hierarchy() {
    $pages = [];
    $hierarchy = [];
    
    // First pass: collect all pages
    foreach (glob(CONTENT_DIR . '/*.md') as $file) {
        $content = file_get_contents($file);
        $slug = basename($file, '.md');
        
        if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $content, $matches)) {
            $metadata = json_decode($matches[1], true) ?: [];
            $pages[$slug] = [
                'title' => $metadata['title'] ?? $slug,
                'parent' => $metadata['parent'] ?? null,
                'file' => $file
            ];
        } else {
            $pages[$slug] = [
                'title' => $slug,
                'parent' => null,
                'file' => $file
            ];
        }
    }
    
    // Second pass: build hierarchy
    foreach ($pages as $slug => $page) {
        if (empty($page['parent'])) {
            $hierarchy[$slug] = ['data' => $page, 'children' => []];
        } else if (isset($pages[$page['parent']])) {
            if (!isset($hierarchy[$page['parent']])) {
                $hierarchy[$page['parent']] = ['data' => $pages[$page['parent']], 'children' => []];
            }
            $hierarchy[$page['parent']]['children'][$slug] = ['data' => $page, 'children' => []];
        } else {
            // Parent doesn't exist, make it top level
            $hierarchy[$slug] = ['data' => $page, 'children' => []];
        }
    }
    
    return ['pages' => $pages, 'hierarchy' => $hierarchy];
}

fcms_register_admin_section('files', [
    'label' => 'Files',
    'menu_order' => 50,
    'render_callback' => 'fcms_render_file_manager'
]);

    function fcms_render_file_manager() {
    $uploadsDir = PROJECT_ROOT . '/uploads';
    $webUploadsDir = '/uploads';
    $allowedExts = ['jpg','jpeg','png','gif','webp','pdf','zip','svg','txt','md'];
    $maxFileSize = 10 * 1024 * 1024; // 10MB

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

    // Handle file rename
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rename_file') {
        $oldName = $_POST['old_name'] ?? '';
        $newName = $_POST['new_name'] ?? '';
        $oldPath = realpath($uploadsDir . '/' . $oldName);
        $newPath = $uploadsDir . '/' . $newName;
        $newExt = strtolower(pathinfo($newName, PATHINFO_EXTENSION));
        if (!$oldName || !$newName) {
            $error = 'Both old and new file names are required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $newName)) {
            $error = 'Invalid new file name.';
        } elseif (!in_array($newExt, $allowedExts)) {
            $error = 'File extension not allowed.';
        } elseif (!is_file($oldPath) || strpos($oldPath, realpath($uploadsDir)) !== 0) {
            $error = 'Original file not found.';
        } elseif (file_exists($newPath)) {
            $error = 'A file with the new name already exists.';
        } else {
            if (rename($oldPath, $newPath)) {
                $success = 'File renamed successfully.';
            } else {
                $error = 'Failed to rename file.';
            }
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

    ob_start();
    ?>
    <h2 class="text-2xl font-bold mb-6 fira-code">File Manager</h2>
    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="mb-6 flex items-center gap-4">
        <input type="hidden" name="action" value="upload_file">
        <input type="file" name="file" required class="border rounded px-2 py-1">
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Upload</button>
        <span class="text-sm text-gray-500">Allowed: <?= implode(', ', $allowedExts) ?>, max <?= round($maxFileSize/1024/1024) ?>MB</span>
    </form>

    <table class="w-full border-collapse">
        <thead>
            <tr>
                <th class="border-b py-2 text-left">File</th>
                <th class="border-b py-2 text-left">Type</th>
                <th class="border-b py-2 text-left">Size</th>
                <th class="border-b py-2 text-left">Preview</th>
                <th class="border-b py-2 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($files as $file): ?>
            <tr>
                <td class="py-2 border-b">
                    <a href="<?= htmlspecialchars($file['url']) ?>" target="_blank"><?= htmlspecialchars($file['name']) ?></a>
                </td>
                <td class="py-2 border-b"><?= htmlspecialchars($file['type']) ?></td>
                <td class="py-2 border-b"><?= number_format($file['size']/1024, 2) ?> KB</td>
                <td class="py-2 border-b">
                    <?php if (in_array($file['ext'], ['jpg','jpeg','png','gif','webp','svg'])): ?>
                        <img src="<?= htmlspecialchars($file['url']) ?>" alt="" style="max-width:60px;max-height:60px;cursor:pointer;border:1px solid #ccc;border-radius:4px;" onclick="showPreviewModal('<?= htmlspecialchars($file['url']) ?>','image')">
                    <?php elseif (in_array($file['ext'], ['txt','md'])): ?>
                        <button type="button" class="bg-gray-300 text-gray-800 px-2 py-1 rounded" onclick="showPreviewModal('<?= htmlspecialchars($file['url']) ?>','text')">Preview</button>
                    <?php elseif ($file['ext'] === 'pdf'): ?>
                        <button type="button" class="bg-gray-300 text-gray-800 px-2 py-1 rounded" onclick="showPreviewModal('<?= htmlspecialchars($file['url']) ?>','pdf')">Preview</button>
                    <?php else: ?>
                        <span class="text-gray-400">No preview</span>
                    <?php endif; ?>
                </td>
                <td class="py-2 border-b">
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this file?')">
                        <input type="hidden" name="action" value="delete_file">
                        <input type="hidden" name="filename" value="<?= htmlspecialchars($file['name']) ?>">
                        <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Delete</button>
                    </form>
                    <a href="<?= htmlspecialchars($file['url']) ?>" download class="bg-gray-500 text-white px-3 py-1 rounded hover:bg-gray-600 ml-2">Download</a>
                    <button type="button" class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 ml-2" onclick="showRenameModal('<?= htmlspecialchars($file['name']) ?>')">Rename</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Rename Modal -->
    <div id="renameModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h3 class="text-xl font-bold mb-4">Rename File</h3>
            <form method="POST" id="rename-form" class="space-y-4">
                <input type="hidden" name="action" value="rename_file">
                <input type="hidden" name="old_name" id="rename-old-name">
                <div>
                    <label class="block mb-1">New File Name:</label>
                    <input type="text" name="new_name" id="rename-new-name" class="w-full px-3 py-2 border border-gray-300 rounded" required>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="hideRenameModal()" class="bg-gray-400 text-white px-4 py-2 rounded">Cancel</button>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Rename</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-2xl relative">
            <button onclick="hidePreviewModal()" class="absolute top-2 right-2 bg-gray-400 text-white px-2 py-1 rounded">&times;</button>
            <div id="previewContent"></div>
        </div>
    </div>

    <script>
    function showRenameModal(filename) {
        document.getElementById('rename-old-name').value = filename;
        document.getElementById('rename-new-name').value = filename;
        document.getElementById('renameModal').classList.remove('hidden');
    }
    function hideRenameModal() {
        document.getElementById('renameModal').classList.add('hidden');
    }

    function showPreviewModal(url, type) {
        var modal = document.getElementById('previewModal');
        var content = document.getElementById('previewContent');
        content.innerHTML = '';
        if (type === 'image') {
            content.innerHTML = '<img src="' + url + '" style="max-width:100%;max-height:70vh;border-radius:8px;">';
        } else if (type === 'text') {
            fetch(url)
                .then(response => response.text())
                .then(text => {
                    content.innerHTML = '<pre style="max-height:60vh;overflow:auto;background:#f3f3f3;padding:1em;border-radius:6px;">' + 
                        escapeHtml(text.substring(0, 5000)) + 
                        (text.length > 5000 ? "\n... (truncated)" : "") + 
                        '</pre>';
                });
        } else if (type === 'pdf') {
            content.innerHTML = '<iframe src="' + url + '" style="width:90vw;height:70vh;border:none;"></iframe>';
        }
        modal.classList.remove('hidden');
    }
    function hidePreviewModal() {
        document.getElementById('previewModal').classList.add('hidden');
    }
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    // Close modals on background click
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('renameModal').addEventListener('click', function(e) {
            if (e.target === this) hideRenameModal();
        });
        document.getElementById('previewModal').addEventListener('click', function(e) {
            if (e.target === this) hidePreviewModal();
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

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
        $adminMenuFile = ADMIN_CONFIG_DIR . '/menus.json';
        $publicMenuFile = CONFIG_DIR . '/menus.json';
        
        if (!empty($menuData)) {
            $menuData = json_decode($menuData, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Save to admin config
                file_put_contents($adminMenuFile, json_encode($menuData, JSON_PRETTY_PRINT));
                // Save to public config
                file_put_contents($publicMenuFile, json_encode($menuData, JSON_PRETTY_PRINT));
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
    // Load the base template
    $template = file_get_contents(ADMIN_TEMPLATE_DIR . '/base.html');
    
    // Set the page title based on the action
    $action = $_GET['action'] ?? '';
    $pageTitle = 'Dashboard';
    $content = '';
    
    // Calculate statistics
    $totalPages = 0;
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(CONTENT_DIR));
    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        if (strtolower($file->getExtension()) === 'md') {
            $totalPages++;
        }
    }
    
    $activePlugins = [];
    $pluginsFile = ADMIN_CONFIG_DIR . '/plugins.json';
    if (file_exists($pluginsFile)) {
        $activePlugins = json_decode(file_get_contents($pluginsFile), true) ?? [];
        if (!is_array($activePlugins)) {
            $activePlugins = [];
        }
    }
    
    // Store the count for the template
    $activePluginsCount = count($activePlugins);
    
    if ($action === 'manage_users') {
        $pageTitle = 'User Management';
        $content = file_get_contents(ADMIN_TEMPLATE_DIR . '/users.html');
        
        // Generate user list
        $users = json_decode(file_get_contents($usersFile), true);
        $userList = '';
        foreach ($users as $user) {
            $username = htmlspecialchars($user['username']);
            $role = htmlspecialchars($user['role'] ?? 'author');
            
            // Ensure admin user always has administrator role
            if ($username === 'admin') {
                $role = 'administrator';
            }
            
            $userList .= '<tr>
                <td class="py-2 px-4 border-b">' . $username . ' <span class="text-gray-500">(' . $role . ')</span></td>
                <td class="py-2 px-4 border-b">
                    <div class="flex space-x-2">
                        <button onclick="editUser(\'' . $username . '\', \'' . $role . '\')" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">Edit</button>
                        <button onclick="deleteUser(\'' . $username . '\')" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Delete</button>
                    </div>
                </td>
            </tr>';
        }
        $content = str_replace('{{user_list}}', $userList, $content);
    } elseif ($action === 'manage_themes') {
        $pageTitle = 'Theme Management';
        $content = file_get_contents(ADMIN_TEMPLATE_DIR . '/themes.html');
        
        // Get theme options forms
        $themeOptionsForms = '';
        if ($supportsHerobanner) {
            $themeOptionsForms .= '<form method="POST" enctype="multipart/form-data" class="mb-4">
                <label class="block font-medium mb-1">Hero Banner Image:</label>';
            if ($herobannerUrl) {
                $themeOptionsForms .= '<img src="' . htmlspecialchars($herobannerUrl) . '" style="max-width:300px;max-height:120px;display:block;margin-bottom:1em;">';
            }
            $themeOptionsForms .= '<input type="file" name="herobanner" accept="image/*" class="mb-2">
                <button type="submit" name="action" value="upload_herobanner" class="bg-blue-500 text-white px-4 py-2 rounded">Upload</button>
            </form>';
        }

        if ($supportsLogo) {
            $themeOptionsForms .= '<form method="POST" enctype="multipart/form-data" class="mb-4">
                <label class="block font-medium mb-1">Logo Image:</label>';
            if ($logoUrl) {
                $themeOptionsForms .= '<img src="' . htmlspecialchars($logoUrl) . '" style="max-width:120px;max-height:60px;display:block;margin-bottom:1em;">';
            }
            $themeOptionsForms .= '<input type="file" name="logo" accept="image/*" class="mb-2">
                <button type="submit" name="action" value="upload_logo" class="bg-blue-500 text-white px-4 py-2 rounded">Upload</button>
            </form>';
        }

        // Get available themes
        $themes = [];
        $themesDir = PROJECT_ROOT . '/themes';
        $activeTheme = $themeManager->getActiveTheme();
        
        if (is_dir($themesDir)) {
            foreach (glob($themesDir . '/*', GLOB_ONLYDIR) as $themeDir) {
                $themeName = basename($themeDir);
                $themeInfo = [
                    'name' => $themeName,
                    'description' => 'No description available',
                    'version' => '1.0.0',
                    'author' => 'Unknown',
                    'active' => ($themeName === $activeTheme)
                ];
                
                // Try to load theme.json if it exists
                $themeJson = $themeDir . '/theme.json';
                if (file_exists($themeJson)) {
                    $info = json_decode(file_get_contents($themeJson), true);
                    if ($info) {
                        $themeInfo = array_merge($themeInfo, $info);
                    }
                }
                
                $themes[] = $themeInfo;
            }
        }

        // Build theme cards
        $themeCards = '';
        $themeCardTemplate = file_get_contents(ADMIN_TEMPLATE_DIR . '/theme-card.html');
        foreach ($themes as $theme) {
            $themeCard = $themeCardTemplate;
            $themeCard = str_replace('{{name}}', htmlspecialchars($theme['name']), $themeCard);
            $themeCard = str_replace('{{description}}', htmlspecialchars($theme['description']), $themeCard);
            $themeCard = str_replace('{{version}}', htmlspecialchars($theme['version']), $themeCard);
            $themeCard = str_replace('{{author}}', htmlspecialchars($theme['author']), $themeCard);
            
            // Handle active/inactive state
            if ($theme['active']) {
                // Remove the inactive section
                $themeCard = preg_replace('/\{\{\^active\}\}.*?\{\{\/active\}\}/s', '', $themeCard);
                // Keep the active section content
                $themeCard = preg_replace('/\{\{#active\}\}(.*?)\{\{\/active\}\}/s', '$1', $themeCard);
            } else {
                // Remove the active section
                $themeCard = preg_replace('/\{\{#active\}\}.*?\{\{\/active\}\}/s', '', $themeCard);
                // Keep the inactive section content
                $themeCard = preg_replace('/\{\{\^active\}\}(.*?)\{\{\/active\}\}/s', '$1', $themeCard);
            }
            
            $themeCards .= $themeCard;
        }

        // Replace template variables
        $content = str_replace('{{theme_options_forms}}', $themeOptionsForms, $content);
        $content = str_replace('{{themes}}', $themeCards, $content);
    } elseif ($action === 'manage_plugins') {
        $pageTitle = 'Plugin Management';
        $content = file_get_contents(ADMIN_TEMPLATE_DIR . '/plugins.html');
    } elseif ($action === 'manage_menus') {
        $pageTitle = 'Menu Management';
        $content = file_get_contents(ADMIN_TEMPLATE_DIR . '/menu-content.html');
        
        // Get available menus
        $menus = [];
        $menusFile = ADMIN_CONFIG_DIR . '/menus.json';
        if (file_exists($menusFile)) {
            $menus = json_decode(file_get_contents($menusFile), true) ?: [];
        }
        
        // If no menus exist, create a default menu
        if (empty($menus)) {
            $menus['main'] = [
                'name' => 'Main Menu',
                'menu_class' => 'main-nav',
                'items' => []
            ];
            file_put_contents($menusFile, json_encode($menus, JSON_PRETTY_PRINT));
        }
        
        // Build menu options
        $menuOptions = '';
        foreach ($menus as $menuId => $menu) {
            $menuName = $menu['name'] ?? ucfirst($menuId);
            $menuOptions .= sprintf(
                '<option value="%s">%s</option>',
                htmlspecialchars($menuId),
                htmlspecialchars($menuName)
            );
        }
        
        // Replace only the menu options in the template
        $content = str_replace('{{menu_options}}', $menuOptions, $content);
        
    } elseif ($action === 'delete_menu') {
        // Start output buffering and disable error display
        ob_start();
        ini_set('display_errors', 0);
        error_reporting(0);
        
        // Enable error logging
        ini_set('log_errors', 1);
        ini_set('error_log', PROJECT_ROOT . '/error.log');
        
        try {
            // Clear any previous output
            ob_clean();
            header('Content-Type: application/json');
            
            // Verify session is active
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            
            if (!isLoggedIn()) {
                throw new Exception('You must be logged in to delete menus');
            }
            
            if (!function_exists('fcms_check_permission')) {
                throw new Exception('Permission system not available');
            }
            
            if (!fcms_check_permission($_SESSION['username'], 'manage_menus')) {
                throw new Exception('You do not have permission to delete menus');
            }
            
            $input = file_get_contents('php://input');
            if (empty($input)) {
                throw new Exception('No input data received');
            }
            
            $data = json_decode($input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON data received: ' . json_last_error_msg());
            }
            
            $menuId = $data['menu_id'] ?? '';
            if (empty($menuId)) {
                throw new Exception('Menu ID is required');
            }
            
            $adminMenuFile = ADMIN_CONFIG_DIR . '/menus.json';
            $publicMenuFile = CONFIG_DIR . '/menus.json';
            
            // Check if menu files exist
            if (!file_exists($adminMenuFile) || !file_exists($publicMenuFile)) {
                throw new Exception('Menu configuration files not found');
            }
            
            // Read current menus
            $menus = json_decode(file_get_contents($adminMenuFile), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid menu configuration: ' . json_last_error_msg());
            }
            
            // Check if menu exists
            if (!isset($menus[$menuId])) {
                throw new Exception('Menu not found');
            }
            
            // Delete the menu
            unset($menus[$menuId]);
            
            // Save to both locations
            if (!file_put_contents($adminMenuFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                throw new Exception('Failed to save admin menu configuration');
            }
            if (!file_put_contents($publicMenuFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                throw new Exception('Failed to save public menu configuration');
            }
            
            echo json_encode(['success' => true]);
            exit;
            
        } catch (Exception $e) {
            error_log('Menu deletion error: ' . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
        
    } elseif ($action === 'load_menu') {
        // Handle AJAX request to load menu data
        header('Content-Type: application/json');
        
        $menuId = $_GET['menu_id'] ?? '';
        $menusFile = ADMIN_CONFIG_DIR . '/menus.json';
        $menus = [];
        
        if (file_exists($menusFile)) {
            $menus = json_decode(file_get_contents($menusFile), true) ?: [];
        }
        
        if (isset($menus[$menuId])) {
            $menuData = $menus[$menuId];
            if (!isset($menuData['items'])) {
                $menuData['items'] = [];
            }
            if (!isset($menuData['menu_class'])) {
                $menuData['menu_class'] = '';
            }
            // Convert old structure to new structure
            $menuData['class'] = $menuData['menu_class'] ?? '';
            foreach ($menuData['items'] as &$item) {
                if (isset($item['item_class'])) {
                    $item['class'] = $item['item_class'];
                    unset($item['item_class']);
                }
            }
            echo json_encode($menuData);
        } else {
            echo json_encode(['error' => 'Menu not found']);
        }
        exit;
        
    } elseif ($action === 'save_menu') {
        // Handle AJAX request to save menu data
        header('Content-Type: application/json');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $menuId = $data['menu_id'] ?? '';
        $items = $data['items'] ?? [];
        $menuClass = $data['class'] ?? '';
        
        $adminMenuFile = ADMIN_CONFIG_DIR . '/menus.json';
        $publicMenuFile = CONFIG_DIR . '/menus.json';
        $menus = [];
        
        if (file_exists($adminMenuFile)) {
            $menus = json_decode(file_get_contents($adminMenuFile), true) ?: [];
        }
        
        if (isset($menus[$menuId])) {
            // Update menu data
            $menus[$menuId]['menu_class'] = $menuClass;
            $menus[$menuId]['items'] = $items;
            
            // Save to both locations
            file_put_contents($adminMenuFile, json_encode($menus, JSON_PRETTY_PRINT));
            file_put_contents($publicMenuFile, json_encode($menus, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Menu not found']);
        }
        exit;
        
    } elseif ($action === 'create_menu') {
        // Handle AJAX request to create new menu
        header('Content-Type: application/json');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $name = $data['name'] ?? '';
        $menuClass = $data['class'] ?? '';
        
        if (empty($name)) {
            echo json_encode(['error' => 'Menu name is required']);
            exit;
        }
        
        $adminMenuFile = ADMIN_CONFIG_DIR . '/menus.json';
        $publicMenuFile = CONFIG_DIR . '/menus.json';
        $menus = [];
        
        if (file_exists($adminMenuFile)) {
            $menus = json_decode(file_get_contents($adminMenuFile), true) ?: [];
        }
        
        $menuId = 'menu_' . time();
        $menus[$menuId] = [
            'name' => $name,
            'menu_class' => $menuClass,
            'items' => []
        ];
        
        // Save to both locations
        file_put_contents($adminMenuFile, json_encode($menus, JSON_PRETTY_PRINT));
        file_put_contents($publicMenuFile, json_encode($menus, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
        exit;

    } elseif ($action === 'manage_widgets') {
        $pageTitle = 'Widget Management';
        require_once(ADMIN_INCLUDES_DIR . '/widgetmanager.php');
        $widgetData = fcms_render_widget_manager();
        $content = file_get_contents(ADMIN_TEMPLATE_DIR . '/widgets.html');
        // Replace template variables with widget data
        $content = str_replace('{{sidebar_selection}}', $widgetData['sidebar_selection'], $content);
        $content = str_replace('{{widget_list}}', $widgetData['widget_list'], $content);
        $content = str_replace('{{current_sidebar}}', $widgetData['current_sidebar'], $content);
    } elseif ($action === 'files') {
        $pageTitle = 'File Manager';
        $content = fcms_render_file_manager();
    } elseif (isset($_GET['edit']) && !empty($_GET['edit'])) {
        $pageTitle = 'Content Editor';
        $content = file_get_contents(ADMIN_TEMPLATE_DIR . '/editor.html');
    } else {
        // Dashboard view - combine site settings and content management
        $content = file_get_contents(ADMIN_TEMPLATE_DIR . '/site-settings.html');
        
        // Build content list
        $contentFiles = [];
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(CONTENT_DIR));
        foreach ($rii as $file) {
            if ($file->isDir()) continue;
            if (strtolower($file->getExtension()) === 'md') {
                $contentFiles[] = $file->getPathname();
            }
        }
        $contentList = '';
        foreach ($contentFiles as $file) {
            $filename = ltrim(str_replace(CONTENT_DIR, '', $file), '/\\');
            $fileContent = file_get_contents($file);
            $displayName = $filename;
            if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $fileContent, $matches)) {
                $metadata = json_decode($matches[1], true);
                if ($metadata && isset($metadata['title'])) {
                    $displayName = $metadata['title'] . ' <span class="text-gray-400 text-xs">(' . $filename . ')</span>';
                }
            }
            $contentList .= '<li class="py-2 px-4 hover:bg-gray-100">
                <div class="flex justify-between items-center">
                    <span>' . htmlspecialchars($displayName) . '</span>
                    <div class="flex space-x-2">
                        <a href="?edit=' . urlencode($filename) . '" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">
                            Edit
                        </a>
                        <button onclick="deletePage(\'' . htmlspecialchars($filename, ENT_QUOTES) . '\')" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
                            Delete
                        </button>
                    </div>
                </div>
            </li>';
        }
        
        // Load content management template and replace content list
        $contentManagement = file_get_contents(ADMIN_TEMPLATE_DIR . '/content-management.html');
        $contentManagement = str_replace('{{content_list}}', $contentList, $contentManagement);
        
        // Combine site settings and content management
        $content .= $contentManagement;
    }

    // Replace template variables
    $template = str_replace('{{page_title}}', htmlspecialchars($pageTitle), $template);
    $template = str_replace('{{username}}', htmlspecialchars($_SESSION['username']), $template);
    $template = str_replace('{{content}}', $content, $template);
    $template = str_replace('{{site_name}}', htmlspecialchars($siteName), $template);
    $template = str_replace('{{custom_css}}', htmlspecialchars($customCss), $template);
    $template = str_replace('{{custom_js}}', htmlspecialchars($customJs), $template);
    $template = str_replace('{{total_pages}}', $totalPages, $template);
    $template = str_replace('{{active_plugins}}', $activePluginsCount, $template);

    // Handle error and success messages
    if (isset($error)) {
        $template = str_replace('{{error}}', '<div class="max-w-7xl mx-auto mt-4"><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">' . htmlspecialchars($error) . '</div></div>', $template);
    } else {
        $template = str_replace('{{error}}', '', $template);
    }

    if (isset($success)) {
        $template = str_replace('{{success}}', '<div class="max-w-7xl mx-auto mt-4"><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">' . htmlspecialchars($success) . '</div></div>', $template);
    } else {
        $template = str_replace('{{success}}', '', $template);
    }

    // Handle plugin sections
    $pluginNavItems = '';
    foreach ($activePlugins as $plugin) {
        if (isset($plugin['admin_sections'])) {
            foreach ($plugin['admin_sections'] as $section) {
                if (fcms_check_permission($_SESSION['username'], $section['capability'])) {
                    $pluginNavItems .= '<a href="?action=' . $section['id'] . '" class="hover:text-green-200">' . $section['title'] . '</a>';
                }
            }
        }
    }
    $template = str_replace('{{plugin_nav_items}}', $pluginNavItems, $template);
}

echo $template;
?>
