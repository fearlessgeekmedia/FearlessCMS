<?php
// Main index.php file for FearlessCMS
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define paths
define('BASE_DIR', __DIR__);
define('CONTENT_DIR', BASE_DIR . '/content');
define('CONFIG_DIR', BASE_DIR . '/config');
define('ADMIN_DIR', BASE_DIR . '/admin');

// Debug function - only logs to a file, doesn't output to prevent header issues
function debug_log($message) {
    if (isset($_GET['debug'])) {
        $logFile = BASE_DIR . '/debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] " . (is_string($message) ? $message : print_r($message, true)) . "\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

// Include ThemeManager class
// First check if it exists in the expected location
if (file_exists(ADMIN_DIR . '/ThemeManager.php')) {
    require_once ADMIN_DIR . '/ThemeManager.php';
} else {
    // If not found, let's define the class here as a fallback
    class ThemeManager {
        private $activeTheme;
        private $themesPath;

        public function __construct() {
            $this->themesPath = BASE_DIR . '/themes';
            $this->loadActiveTheme();
        }

        private function loadActiveTheme() {
            $configFile = BASE_DIR . '/config/config.json';
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                $this->activeTheme = $config['active_theme'] ?? 'default';
            } else {
                $this->activeTheme = 'default';
            }
        }

        public function getTemplate($templateName, $fallbackTemplate = 'page') {
            // First try the requested template in active theme
            $templatePath = $this->themesPath . "/{$this->activeTheme}/templates/$templateName.html";
            if (file_exists($templatePath)) {
                return file_get_contents($templatePath);
            }

            // If not found, try the fallback template in active theme
            $fallbackPath = $this->themesPath . "/{$this->activeTheme}/templates/$fallbackTemplate.html";
            if (file_exists($fallbackPath)) {
                return file_get_contents($fallbackPath);
            }

            // Finally, try the default theme's fallback template
            $defaultPath = $this->themesPath . "/default/templates/$fallbackTemplate.html";
            if (file_exists($defaultPath)) {
                return file_get_contents($defaultPath);
            }

            // If all else fails, return a simple HTML template
            return '<!DOCTYPE html><html><head><title>{{title}}</title></head><body><h1>{{title}}</h1><div>{{content}}</div></body></html>';
        }

        public function getActiveTheme() {
            return $this->activeTheme;
        }

        public function setActiveTheme($themeName) {
            if (!is_dir($this->themesPath . "/$themeName")) {
                throw new Exception("Theme '$themeName' does not exist");
            }

            $config = ['active_theme' => $themeName];
            file_put_contents(BASE_DIR . '/config/config.json', json_encode($config, JSON_PRETTY_PRINT));
            $this->activeTheme = $themeName;
        }
    }
}

// Create theme manager
$themeManager = new ThemeManager();

// Get requested page from URL
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$path = trim($path, '/');

// Default to home if no path specified
if (empty($path)) {
    $path = 'home'; // Changed from 'index' to 'home' since you have home.md
}

debug_log("Requested path: $path");
debug_log("Looking for file: " . CONTENT_DIR . '/' . $path . '.md');

// Make sure content directory exists
if (!is_dir(CONTENT_DIR)) {
    debug_log("Content directory does not exist: " . CONTENT_DIR);
    mkdir(CONTENT_DIR, 0755, true);
    file_put_contents(CONTENT_DIR . '/home.md', '# Welcome to FearlessCMS' . PHP_EOL . PHP_EOL . 'This is your first page. Edit it in the admin panel.');
}

// Check if the requested file exists
$contentFile = CONTENT_DIR . '/' . $path . '.md';
if (!file_exists($contentFile)) {
    debug_log("File not found directly, searching for case-insensitive match");
    
    // Try to find a matching file without case sensitivity
    $files = glob(CONTENT_DIR . '/*.md');
    debug_log("Available files: " . print_r($files, true));
    
    $found = false;
    foreach ($files as $file) {
        $basename = basename($file, '.md');
        debug_log("Checking if '$basename' matches '$path'");
        
        if (strtolower($basename) === strtolower($path)) {
            $contentFile = $file;
            $found = true;
            debug_log("Match found: $contentFile");
            break;
        }
    }
    
    if (!$found) {
        // Special case for root URL - try to use home.md if it exists
        if ($path === 'home' && file_exists(CONTENT_DIR . '/index.md')) {
            $contentFile = CONTENT_DIR . '/index.md';
            $found = true;
            debug_log("Using index.md for homepage");
        } else if ($path === 'index' && file_exists(CONTENT_DIR . '/home.md')) {
            $contentFile = CONTENT_DIR . '/home.md';
            $found = true;
            debug_log("Using home.md for homepage");
        }
    }
    
    if (!$found) {
        // If still not found, show 404 page
        debug_log("No matching file found, showing 404");
        header('HTTP/1.0 404 Not Found');
        $template = $themeManager->getTemplate('404', 'page');
        $notFoundContent = "<p>The page you requested could not be found. Please check the URL or return to the <a href='/'>homepage</a>.</p>";
        echo str_replace(['{{title}}', '{{content}}'], ['404 - Page Not Found', $notFoundContent], $template);
        exit;
    }
}

debug_log("Using content file: $contentFile");

// Read the content file
$content = file_get_contents($contentFile);
$title = basename($contentFile, '.md'); // Default title is filename

debug_log("Initial title (from filename): $title");
debug_log("Content length: " . strlen($content));

// Extract JSON frontmatter if it exists
if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $content, $matches)) {
    $jsonData = $matches[1];
    debug_log("Found JSON frontmatter: $jsonData");
    
    $metadata = json_decode($jsonData, true);
    
    // Get title from metadata
    if ($metadata && isset($metadata['title'])) {
        $title = $metadata['title'];
        debug_log("Using title from frontmatter: $title");
    }
    
    // Remove frontmatter from content for rendering
    $content = preg_replace('/^<!--\s*json\s*(.*?)\s*-->\s*/s', '', $content);
}

// Parse markdown to HTML (using a simple parser for now)
function parseMarkdown($text) {
    // Headers
    $text = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $text);
    $text = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $text);
    
    // Bold and italic
    $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $text);
    
    // Links
    $text = preg_replace('/\[(.*?)\]\((.*?)\)/s', '<a href="$2">$1</a>', $text);
    
    // Lists
    $text = preg_replace('/^- (.*?)$/m', '<li>$1</li>', $text);
    $text = preg_replace('/(<li>.*?<\/li>\n)+/s', '<ul>$0</ul>', $text);
    
    // Paragraphs (only wrap lines that don't start with HTML tags)
    $text = preg_replace('/^(?!<[a-z]).+/m', '<p>$0</p>', $text);
    
    return $text;
}

$parsedContent = parseMarkdown($content);
debug_log("Parsed content length: " . strlen($parsedContent));

// Get template
$template = $themeManager->getTemplate('page');
debug_log("Template length: " . strlen($template));

// Process menus in the template
if (preg_match_all('/\{\{menu=([a-zA-Z0-9_-]+)\}\}/', $template, $matches)) {
    // Check both possible menu file locations
    $menusFile = ADMIN_DIR . '/config/menus.json';
    if (!file_exists($menusFile)) {
        $menusFile = CONFIG_DIR . '/menus.json';
    }
    
    debug_log("Looking for menus file: $menusFile");
    
    if (file_exists($menusFile)) {
        $menuContent = file_get_contents($menusFile);
        debug_log("Menu file content: $menuContent");
        
        $menus = json_decode($menuContent, true);
        if ($menus === null) {
            debug_log("Error decoding menu JSON: " . json_last_error_msg());
            $menus = [];
        }
    } else {
        debug_log("Menu file not found");
        $menus = [];
        
        // Create a default menu file if it doesn't exist
        if (!is_dir(dirname($menusFile))) {
            mkdir(dirname($menusFile), 0755, true);
        }
        
        $defaultMenu = [
            'main' => [
                'menu_class' => 'main-nav',
                'items' => [
                    [
                        'label' => 'Home',
                        'url' => '/',
                        'item_class' => 'nav-item'
                    ]
                ]
            ]
        ];
        
        file_put_contents($menusFile, json_encode($defaultMenu, JSON_PRETTY_PRINT));
        $menus = $defaultMenu;
    }
    
    debug_log("Menus found: " . print_r(array_keys($menus), true));
    
    foreach ($matches[1] as $i => $menuName) {
        $menuHtml = '';
        if (isset($menus[$menuName])) {
            $menuClass = $menus[$menuName]['menu_class'] ?? '';
            $menuHtml = '<nav class="' . htmlspecialchars($menuClass) . '"><ul>';
            
            if (isset($menus[$menuName]['items']) && is_array($menus[$menuName]['items'])) {
                foreach ($menus[$menuName]['items'] as $item) {
                    if (isset($item['label']) && isset($item['url'])) {
                        $itemClass = $item['item_class'] ?? '';
                        $menuHtml .= '<li class="' . htmlspecialchars($itemClass) . '">';
                        $menuHtml .= '<a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['label']) . '</a>';
                        $menuHtml .= '</li>';
                    }
                }
            }
            
            $menuHtml .= '</ul></nav>';
        } else {
            debug_log("Menu '$menuName' not found in menus.json");
        }
        
        debug_log("Replacing {{menu=$menuName}} with: $menuHtml");
        $template = str_replace($matches[0][$i], $menuHtml, $template);
    }
}

// Replace placeholders in template
$template = str_replace('{{title}}', htmlspecialchars($title), $template);
$template = str_replace('{{content}}', $parsedContent, $template);

// Output the page
echo $template;
?>
