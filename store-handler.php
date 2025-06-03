<?php
// Define PROJECT_ROOT if not already defined
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}

// Include required files
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/plugins.php';

// Register store admin section
fcms_register_admin_section('store', [
    'label' => 'Store',
    'menu_order' => 40,
    'render_callback' => 'store_admin_page'
]);

/**
 * Simple markdown parser if Parsedown is not available
 */
class SimpleMarkdown {
    public function text($text) {
        // Convert headers
        $text = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $text);
        $text = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $text);
        $text = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $text);
        
        // Convert bold and italic
        $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $text);
        
        // Convert links
        $text = preg_replace('/\[(.*?)\]\((.*?)\)/s', '<a href="$2">$1</a>', $text);
        
        // Convert paragraphs
        $text = '<p>' . str_replace("\n\n", '</p><p>', $text) . '</p>';
        
        return $text;
    }
}

/**
 * Fetch content from GitHub repository
 */
function fetch_github_content($path) {
    $storeUrl = defined('STORE_URL') ? STORE_URL : 'https://github.com/fearlessgeekmedia/FearlessCMS-Store.git';
    
    error_log("[Store Handler] Starting fetch_github_content");
    error_log("[Store Handler] Store URL: " . $storeUrl);
    error_log("[Store Handler] Requested path: " . $path);
    
    // Determine if this is a raw content URL or a GitHub repository URL
    $rawUrl = $path;
    if (strpos($path, 'http') !== 0) {
        if (strpos($storeUrl, 'github.com') !== false) {
            // Transform GitHub repository URL to raw content URL
            $rawUrl = preg_replace('#https?://github\\.com/([^/]+)/([^/]+)\\.git#', 'https://raw.githubusercontent.com/$1/$2', $storeUrl) . '/main/' . $path;
        } else {
            // For other URLs, ensure we don't double-append store.json
            $baseUrl = rtrim($storeUrl, '/');
            if (strpos($baseUrl, 'store.json') !== false) {
                $baseUrl = dirname($baseUrl);
            }
            $rawUrl = $baseUrl . '/' . $path;
        }
    }
    
    error_log("[Store Handler] Final URL to fetch: " . $rawUrl);
    
    // Try cURL first
    error_log("[Store Handler] Attempting fetch using cURL");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $rawUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // Disable host verification
    curl_setopt($ch, CURLOPT_USERAGENT, 'FearlessCMS/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $content = curl_exec($ch);
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($content === false) {
        error_log("[Store Handler] cURL failed");
        error_log("[Store Handler] cURL error: " . $curl_error);
        error_log("[Store Handler] cURL error number: " . $curl_errno);
        error_log("[Store Handler] cURL HTTP code: " . $http_code);
        
        // Fall back to file_get_contents with SSL verification disabled
        error_log("[Store Handler] Attempting fallback fetch using file_get_contents");
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: FearlessCMS/1.0',
                    'Accept: application/json, text/plain, */*',
                    'Accept-Language: en-US,en;q=0.9'
                ],
                'ignore_errors' => true,
                'timeout' => 30,
                'protocol_version' => '1.1'
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        $content = @file_get_contents($rawUrl, false, $context);
        if ($content === false) {
            $error = error_get_last();
            error_log("[Store Handler] file_get_contents also failed");
            if ($error) {
                error_log("[Store Handler] Error details: " . $error['message']);
            }
            return false;
        }
    }
    
    error_log("[Store Handler] Successfully fetched content");
    error_log("[Store Handler] Content length: " . strlen($content));
    error_log("[Store Handler] Content preview: " . substr($content, 0, 1000));
    
    return $content;
}

/**
 * Get markdown parser instance
 */
function get_markdown_parser() {
    // Try to use Parsedown if available
    if (class_exists('Parsedown')) {
        return new Parsedown();
    }
    // Fall back to simple markdown parser
    return new SimpleMarkdown();
}

/**
 * Search plugins
 */
function search_plugins($query) {
    $storeUrl = defined('STORE_URL') ? STORE_URL : 'https://github.com/fearlessgeekmedia/FearlessCMS-Store.git';
    
    // Determine if this is a raw content URL or a GitHub repository URL
    $base_url = $storeUrl;
    if (strpos($storeUrl, 'github.com') !== false) {
        // Transform GitHub repository URL to raw content URL
        $base_url = str_replace(['github.com', '.git'], ['raw.githubusercontent.com', ''], $storeUrl) . '/main';
    } elseif (strpos($storeUrl, 'raw.githubusercontent.com') !== false) {
        // If it's a raw content URL, remove the store.json part to get the base URL
        $base_url = dirname($storeUrl);
    }
    
    $store_json_url = $base_url . '/store.json';
    error_log("Searching plugins using URL: " . $store_json_url);
    
    $storeContent = fetch_github_content($store_json_url);
    $store = json_decode($storeContent, true);
    
    if (!$store || !isset($store['plugins'])) {
        error_log("No plugins found in store data");
        return [];
    }
    
    $results = [];
    foreach ($store['plugins'] as $plugin) {
        if (stripos($plugin['name'], $query) !== false || 
            stripos($plugin['description'], $query) !== false) {
            $results[] = $plugin;
        }
    }
    
    return $results;
}

/**
 * Search themes
 */
function search_themes($query) {
    $storeUrl = defined('STORE_URL') ? STORE_URL : 'https://github.com/fearlessgeekmedia/FearlessCMS-Store.git';
    
    // Determine if this is a raw content URL or a GitHub repository URL
    $base_url = $storeUrl;
    if (strpos($storeUrl, 'github.com') !== false) {
        // Transform GitHub repository URL to raw content URL
        $base_url = str_replace(['github.com', '.git'], ['raw.githubusercontent.com', ''], $storeUrl) . '/main';
    } elseif (strpos($storeUrl, 'raw.githubusercontent.com') !== false) {
        // If it's a raw content URL, remove the store.json part to get the base URL
        $base_url = dirname($storeUrl);
    }
    
    $store_json_url = $base_url . '/store.json';
    error_log("Searching themes using URL: " . $store_json_url);
    
    $storeContent = fetch_github_content($store_json_url);
    $store = json_decode($storeContent, true);
    
    if (!$store || !isset($store['themes'])) {
        error_log("No themes found in store data");
        return [];
    }
    
    $results = [];
    foreach ($store['themes'] as $theme) {
        if (stripos($theme['name'], $query) !== false || 
            stripos($theme['description'], $query) !== false) {
            $results[] = $theme;
        }
    }
    
    return $results;
}

/**
 * Handle admin actions
 */
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'store':
            if (isset($_GET['search_plugins'])) {
                $query = $_POST['query'] ?? '';
                $results = search_plugins($query);
                header('Content-Type: application/json');
                echo json_encode($results);
                exit;
            }
            
            if (isset($_GET['search_themes'])) {
                $query = $_POST['query'] ?? '';
                $results = search_themes($query);
                header('Content-Type: application/json');
                echo json_encode($results);
                exit;
            }
            
            if (isset($_GET['update_config'])) {
                header('Content-Type: application/json');
                
                if (!isset($_POST['store_repo'])) {
                    echo json_encode(['success' => false, 'message' => 'Store repository URL is required']);
                    exit;
                }
                
                $store_repo = $_POST['store_repo'];
                error_log("Updating store configuration with URL: " . $store_repo);
                
                // Validate the URL
                if (!filter_var($store_repo, FILTER_VALIDATE_URL)) {
                    error_log("Invalid store repository URL: " . $store_repo);
                    echo json_encode(['success' => false, 'message' => 'Invalid store repository URL']);
                    exit;
                }
                
                // Determine if this is a raw content URL or a GitHub repository URL
                $store_json_url = $store_repo;
                if (strpos($store_repo, 'github.com') !== false) {
                    // Transform GitHub repository URL to raw content URL
                    $store_json_url = str_replace(['github.com', '.git'], ['raw.githubusercontent.com', ''], $store_repo) . '/main/store.json';
                } elseif (strpos($store_repo, 'raw.githubusercontent.com') === false) {
                    // If it's neither a GitHub URL nor a raw content URL, append store.json
                    $store_json_url = rtrim($store_repo, '/') . '/store.json';
                }
                
                error_log("Testing store.json URL: " . $store_json_url);
                
                // Make a direct curl request to test the URL
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $store_json_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_USERAGENT, 'FearlessCMS/1.0');
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Accept: application/json, text/plain, */*',
                    'Accept-Language: en-US,en;q=0.9'
                ]);
                
                $content = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                
                error_log("Direct CURL test - HTTP code: " . $http_code);
                if ($error) {
                    error_log("Direct CURL test - Error: " . $error);
                }
                error_log("Direct CURL test - Response: " . substr($content, 0, 1000));
                
                curl_close($ch);
                
                if ($http_code !== 200) {
                    error_log("Failed to fetch store.json directly. HTTP code: " . $http_code);
                    echo json_encode(['success' => false, 'message' => 'Could not fetch store data from the provided repository']);
                    exit;
                }
                
                // Try to decode the JSON to validate it
                $store_data = json_decode($content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Invalid JSON in store.json: " . json_last_error_msg());
                    echo json_encode(['success' => false, 'message' => 'Invalid store data format']);
                    exit;
                }
                
                error_log("Successfully validated store.json");
                
                // Update the store URL in the configuration
                $config_file = dirname(__DIR__) . '/config/config.json';
                error_log("Updating config file: " . $config_file);
                
                if (file_exists($config_file)) {
                    $config = json_decode(file_get_contents($config_file), true) ?: [];
                    $config['store_url'] = $store_repo;
                    
                    if (file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT))) {
                        error_log("Successfully updated config file");
                        echo json_encode(['success' => true]);
                    } else {
                        error_log("Failed to write to config file");
                        echo json_encode(['success' => false, 'message' => 'Failed to update configuration file']);
                    }
                } else {
                    error_log("Config file not found: " . $config_file);
                    echo json_encode(['success' => false, 'message' => 'Configuration file not found']);
                }
                exit;
            }
            
            if (isset($_GET['install_plugin'])) {
                $plugin_slug = $_POST['plugin_slug'] ?? '';
                if (empty($plugin_slug)) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Plugin slug is required']);
                    exit;
                }
                
                // Get plugin data from store
                $store_data = fetch_github_content('store.json');
                if (!$store_data) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Could not fetch store data']);
                    exit;
                }
                
                $store = json_decode($store_data, true);
                if (!$store || !isset($store['plugins'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Invalid store data']);
                    exit;
                }
                
                $plugin_data = null;
                foreach ($store['plugins'] as $plugin) {
                    if ($plugin['slug'] === $plugin_slug) {
                        $plugin_data = $plugin;
                        break;
                    }
                }
                
                if (!$plugin_data) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Plugin not found in store']);
                    exit;
                }
                
                $success = install_plugin($plugin_data);
                header('Content-Type: application/json');
                echo json_encode(['success' => $success]);
                exit;
            }
            
            if (isset($_GET['install_theme'])) {
                $theme_slug = $_POST['theme_slug'] ?? '';
                if (empty($theme_slug)) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Theme slug is required']);
                    exit;
                }
                
                // Get theme data from store
                $store_data = fetch_github_content('store.json');
                if (!$store_data) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Could not fetch store data']);
                    exit;
                }
                
                $store = json_decode($store_data, true);
                if (!$store || !isset($store['themes'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Invalid store data']);
                    exit;
                }
                
                $theme_data = null;
                foreach ($store['themes'] as $theme) {
                    if ($theme['slug'] === $theme_slug) {
                        $theme_data = $theme;
                        break;
                    }
                }
                
                if (!$theme_data) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Theme not found in store']);
                    exit;
                }
                
                $success = install_theme($theme_data);
                header('Content-Type: application/json');
                echo json_encode(['success' => $success]);
                exit;
            }
            break;
            
        default:
            // Let other handlers process their actions
            return;
    }
}

/**
 * Get list of installed plugins
 */
function get_installed_plugins() {
    $plugins_dir = dirname(__DIR__) . '/plugins';
    $installed_plugins = [];
    
    if (is_dir($plugins_dir)) {
        $dirs = scandir($plugins_dir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            
            $plugin_file = $plugins_dir . '/' . $dir . '/plugin.json';
            if (file_exists($plugin_file)) {
                $plugin_data = json_decode(file_get_contents($plugin_file), true);
                if ($plugin_data && isset($plugin_data['slug'])) {
                    $installed_plugins[$plugin_data['slug']] = $plugin_data;
                }
            }
        }
    }
    
    return $installed_plugins;
}

/**
 * Install a plugin
 */
function install_plugin($plugin_data) {
    error_log("Starting plugin installation for: " . json_encode($plugin_data));
    
    // Validate required plugin data
    if (!isset($plugin_data['slug']) || !isset($plugin_data['download_url'])) {
        error_log("Missing required plugin data: " . json_encode($plugin_data));
        return false;
    }

    $plugins_dir = dirname(__DIR__) . '/plugins';
    $plugin_slug = $plugin_data['slug'];
    $plugin_dir = $plugins_dir . '/' . $plugin_slug;
    
    error_log("Plugin directory: " . $plugin_dir);
    
    // Create plugins directory if it doesn't exist
    if (!is_dir($plugins_dir)) {
        error_log("Creating plugins directory: " . $plugins_dir);
        if (!mkdir($plugins_dir, 0755, true)) {
            error_log("Failed to create plugins directory");
            return false;
        }
    }
    
    // Create plugin directory
    if (!is_dir($plugin_dir)) {
        error_log("Creating plugin directory: " . $plugin_dir);
        if (!mkdir($plugin_dir, 0755, true)) {
            error_log("Failed to create plugin directory");
            return false;
        }
    }
    
    // Download plugin files
    $zip_url = $plugin_data['download_url'];
    $zip_file = $plugin_dir . '/temp.zip';
    
    error_log("Downloading plugin from: " . $zip_url);
    
    // Download the zip file
    $ch = curl_init($zip_url);
    $fp = fopen($zip_file, 'w');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Add this line to handle SSL issues
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);
    
    error_log("Download result - HTTP code: " . $http_code);
    
    if ($http_code !== 200) {
        error_log("Failed to download plugin. HTTP code: " . $http_code);
        return false;
    }
    
    // Extract the zip file
    $zip = new ZipArchive;
    if ($zip->open($zip_file) === TRUE) {
        error_log("Extracting zip file");
        $zip->extractTo($plugin_dir);
        $zip->close();
        unlink($zip_file); // Delete the zip file
        
        // Find the extracted directory (it should be the only directory in the plugin directory)
        $dirs = array_filter(scandir($plugin_dir), function($item) use ($plugin_dir) {
            return $item !== '.' && $item !== '..' && is_dir($plugin_dir . '/' . $item);
        });
        
        if (!empty($dirs)) {
            $extracted_dir = $plugin_dir . '/' . reset($dirs); // Get the first directory
            error_log("Found extracted directory: " . $extracted_dir);
            
            // Move all files and directories up one level
            $files = scandir($extracted_dir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $source = $extracted_dir . '/' . $file;
                $target = $plugin_dir . '/' . $file;
                error_log("Moving from $source to $target");
                rename($source, $target);
            }
            
            // Remove the empty extracted directory
            rmdir($extracted_dir);
        }
        
        error_log("Plugin installation completed successfully");
        return true;
    } else {
        error_log("Failed to open zip file");
        return false;
    }
}

/**
 * Recursively delete a directory
 */
function delete_directory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        if (!delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    
    return rmdir($dir);
}

/**
 * Delete a plugin
 */
function delete_plugin($plugin_slug) {
    $plugins_dir = dirname(__DIR__) . '/plugins';
    $plugin_dir = $plugins_dir . '/' . $plugin_slug;
    
    if (!is_dir($plugin_dir)) {
        error_log("Plugin directory does not exist: " . $plugin_dir);
        return false;
    }
    
    // Check if plugin is deactivated
    $plugin_file = $plugin_dir . '/plugin.json';
    if (file_exists($plugin_file)) {
        $plugin_data = json_decode(file_get_contents($plugin_file), true);
        if ($plugin_data && isset($plugin_data['active']) && $plugin_data['active']) {
            error_log("Cannot delete active plugin: " . $plugin_slug);
            return false; // Don't delete active plugins
        }
    }
    
    try {
        return delete_directory($plugin_dir);
    } catch (Exception $e) {
        error_log("Error deleting plugin directory: " . $e->getMessage());
        return false;
    }
}

/**
 * Handle plugin deletion
 */
if (isset($_POST['action']) && $_POST['action'] === 'delete_plugin') {
    header('Content-Type: application/json');
    
    try {
        $plugin_slug = $_POST['plugin_slug'] ?? '';
        if (empty($plugin_slug)) {
            echo json_encode(['success' => false, 'error' => 'Plugin slug is required']);
            exit;
        }
        
        // Check if plugin is active before deletion
        $plugin_file = dirname(__DIR__) . '/plugins/' . $plugin_slug . '/plugin.json';
        if (file_exists($plugin_file)) {
            $plugin_data = json_decode(file_get_contents($plugin_file), true);
            if ($plugin_data && isset($plugin_data['active']) && $plugin_data['active']) {
                echo json_encode(['success' => false, 'error' => 'Cannot delete an active plugin. Please deactivate it first.']);
                exit;
            }
        }
        
        $success = delete_plugin($plugin_slug);
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Plugin deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete plugin. Please try again.']);
        }
    } catch (Exception $e) {
        error_log("Error in plugin deletion handler: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'An unexpected error occurred while deleting the plugin.']);
    }
    exit;
}

/**
 * Render the store admin page
 */
function store_admin_page() {
    // Load store configuration
    $config_file = dirname(__DIR__) . '/config/config.json';
    $config = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : [];
    $store_repo = $config['store_url'] ?? 'https://github.com/fearlessgeekmedia/FearlessCMS-Store.git';
    
    // Get current store URL
    $storeUrl = defined('STORE_URL') ? STORE_URL : $store_repo;
    
    // Fetch news and featured content
    $newsContent = fetch_github_content('news.md');
    $featuredContent = fetch_github_content('featured.md');
    
    // Get markdown parser
    $parser = get_markdown_parser();
    
    // Get installed plugins
    $installed_plugins = get_installed_plugins();
    
    // Start output buffer
    ob_start();
    ?>
    <div class="space-y-8">
        <!-- Store Configuration -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Store Configuration</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update_store_settings">
                <div>
                    <label class="block mb-1">Store URL</label>
                    <input type="text" 
                           name="store_url" 
                           value="<?php echo htmlspecialchars($storeUrl); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded">
                </div>
                <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Update Store URL</button>
            </form>
        </div>

        <!-- Featured Items -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Featured Items</h3>
            <div class="prose max-w-none">
                <?php 
                if ($featuredContent) {
                    echo $parser->text($featuredContent);
                } else {
                    echo '<p class="text-gray-500">Unable to load featured items.</p>';
                }
                ?>
            </div>
        </div>

        <!-- News -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Latest News</h3>
            <div class="prose max-w-none">
                <?php 
                if ($newsContent) {
                    echo $parser->text($newsContent);
                } else {
                    echo '<p class="text-gray-500">Unable to load news.</p>';
                }
                ?>
            </div>
        </div>

        <!-- Plugin Search -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Search Plugins</h3>
            <div class="space-y-4">
                <div class="flex gap-4">
                    <input type="text" 
                           id="plugin-search" 
                           placeholder="Search plugins..." 
                           class="flex-1 px-3 py-2 border border-gray-300 rounded">
                    <button onclick="searchPlugins()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Search
                    </button>
                </div>
                <div id="plugin-results" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Plugin results will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Theme Search -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Search Themes</h3>
            <div class="space-y-4">
                <div class="flex gap-4">
                    <input type="text" 
                           id="theme-search" 
                           placeholder="Search themes..." 
                           class="flex-1 px-3 py-2 border border-gray-300 rounded">
                    <button onclick="searchThemes()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Search
                    </button>
                </div>
                <div id="theme-results" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Theme results will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Store Statistics -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Store Statistics</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 p-4 rounded">
                    <div class="text-sm text-gray-500">Total Plugins</div>
                    <div class="text-2xl font-bold">0</div>
                </div>
                <div class="bg-gray-50 p-4 rounded">
                    <div class="text-sm text-gray-500">Total Themes</div>
                    <div class="text-2xl font-bold">0</div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function searchPlugins() {
        const searchTerm = document.getElementById('plugin-search').value;
        const resultsContainer = document.getElementById('plugin-results');
        
        // Show loading state
        resultsContainer.innerHTML = '<div class="col-span-full text-center py-4">Searching...</div>';
        
        // Make AJAX request
        const formData = new FormData();
        formData.append('action', 'search_plugins');
        formData.append('query', searchTerm);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(results => {
            if (results.length === 0) {
                resultsContainer.innerHTML = '<div class="col-span-full text-center py-4">No plugins found.</div>';
                return;
            }
            
            // Display results
            resultsContainer.innerHTML = results.map(plugin => {
                const isInstalled = isPluginInstalled(plugin.slug);
                const isActive = isPluginActive(plugin.slug);
                
                let actionButton = '';
                if (isInstalled) {
                    if (isActive) {
                        actionButton = '<button class="px-3 py-1 rounded bg-gray-500 text-white">Active</button>';
                    } else {
                        actionButton = '<button onclick="deletePlugin(\'' + plugin.slug + '\')" class="px-3 py-1 rounded bg-red-500 text-white hover:bg-red-600">Delete</button>';
                    }
                } else {
                    actionButton = '<button onclick="installPlugin(\'' + plugin.slug + '\')" class="px-3 py-1 rounded bg-green-500 text-white hover:bg-green-600">Install</button>';
                }
                
                return `
                    <div class="bg-white border rounded-lg overflow-hidden">
                        <img src="${plugin.banners.low}" alt="${plugin.name}" class="w-full h-32 object-cover">
                        <div class="p-4">
                            <h4 class="font-medium">${plugin.name}</h4>
                            <p class="text-sm text-gray-600 mt-1">${plugin.description}</p>
                            <div class="mt-4 flex justify-between items-center">
                                <span class="text-sm text-gray-500">v${plugin.version}</span>
                                <div class="flex gap-2">
                                    <a href="${plugin.repository}" target="_blank" class="text-blue-500 hover:text-blue-600">Learn More</a>
                                    ${actionButton}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        })
        .catch(error => {
            console.error('Error searching plugins:', error);
            resultsContainer.innerHTML = '<div class="col-span-full text-center py-4 text-red-500">Error searching plugins. Please try again.</div>';
        });
    }

    function isPluginInstalled(slug) {
        return <?php echo json_encode(array_keys($installed_plugins)); ?>.includes(slug);
    }

    function isPluginActive(slug) {
        const activePlugins = <?php 
            $active_plugins = array_filter($installed_plugins, function($plugin) {
                return isset($plugin['active']) && $plugin['active'];
            });
            echo json_encode(array_keys($active_plugins)); 
        ?>;
        return activePlugins.includes(slug);
    }

    function deletePlugin(slug) {
        if (!confirm('Are you sure you want to delete this plugin? This action cannot be undone.')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'delete_plugin');
        formData.append('plugin_slug', slug);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('Plugin deleted successfully!');
                window.location.reload();
            } else {
                alert('Error deleting plugin: ' + (result.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error deleting plugin:', error);
            alert('Error deleting plugin. Please try again.');
        });
    }

    function installPlugin(slug) {
        // First, get the plugin data from the store
        const formData = new FormData();
        formData.append('action', 'search_plugins');
        formData.append('query', slug);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(results => {
            const plugin = results.find(p => p.slug === slug);
            if (!plugin) {
                throw new Error('Plugin not found in store');
            }
            
            // Now install the plugin
            const installData = new FormData();
            installData.append('action', 'install_plugin');
            installData.append('plugin_data', JSON.stringify(plugin));
            
            return fetch(window.location.href, {
                method: 'POST',
                body: installData
            });
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('Plugin installed successfully!');
                window.location.reload();
            } else {
                alert('Error installing plugin: ' + (result.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error installing plugin:', error);
            alert('Error installing plugin. Please try again.');
        });
    }

    function searchThemes() {
        const searchTerm = document.getElementById('theme-search').value;
        const resultsContainer = document.getElementById('theme-results');
        
        // Show loading state
        resultsContainer.innerHTML = '<div class="col-span-full text-center py-4">Searching...</div>';
        
        // Make AJAX request
        const formData = new FormData();
        formData.append('action', 'search_themes');
        formData.append('query', searchTerm);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(results => {
            if (results.length === 0) {
                resultsContainer.innerHTML = '<div class="col-span-full text-center py-4">No themes found.</div>';
                return;
            }
            
            // Display results
            resultsContainer.innerHTML = results.map(theme => `
                <div class="bg-white border rounded-lg overflow-hidden">
                    <img src="${theme.banners.low}" alt="${theme.name}" class="w-full h-32 object-cover">
                    <div class="p-4">
                        <h4 class="font-medium">${theme.name}</h4>
                        <p class="text-sm text-gray-600 mt-1">${theme.description}</p>
                        <div class="mt-4 flex justify-between items-center">
                            <span class="text-sm text-gray-500">v${theme.version}</span>
                            <a href="${theme.repository}" target="_blank" class="text-blue-500 hover:text-blue-600">Learn More</a>
                        </div>
                    </div>
                </div>
            `).join('');
        })
        .catch(error => {
            console.error('Error searching themes:', error);
            resultsContainer.innerHTML = '<div class="col-span-full text-center py-4 text-red-500">Error searching themes. Please try again.</div>';
        });
    }
    </script>
    <?php
    return ob_get_clean();
} 