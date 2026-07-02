<?php
/**
 * FearlessCMS Pure PHP Export Handler
 * Exports the site to a static HTML directory without shell scripts
 */

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Verify CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validate_csrf_token()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid security token']);
    exit;
}

set_time_limit(600); // 10 minutes for export

/**
 * Global function to render a page in the global scope
 */
function fcms_export_render_page($path) {
    // Save current request state
    $oldGet = $_GET;
    $oldServer = $_SERVER;
    
    // Simulate a page request
    ob_start();
    
    try {
        $_GET['page'] = $path;
        $_SERVER['REQUEST_URI'] = '/' . $path;
        $_SERVER['QUERY_STRING'] = 'page=' . urlencode($path);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // We need to define a constant to tell index.php we are in export mode
        if (!defined('FCMS_EXPORT_MODE')) {
            define('FCMS_EXPORT_MODE', true);
        }
        
        // Ensure globals are available to index.php
        global $config, $demoManager, $pageRenderer, $router, $cmsModeManager, $themeManager, $menuManager, $widgetManager, $contentLoader;
        
        error_log("Export: Including index.php for path: $path");
        
        // Use include instead of require to allow multiple calls
        include PROJECT_ROOT . '/index.php';
        
        $html = ob_get_clean();
        error_log("Export: Captured HTML length: " . strlen($html));
        
        // Restore request state
        $_GET = $oldGet;
        $_SERVER = $oldServer;
        
        return $html;
    } catch (Exception $e) {
        ob_end_clean();
        $_GET = $oldGet;
        $_SERVER = $oldServer;
        error_log("Export renderPageWithTheme error for $path: " . $e->getMessage());
        return false;
    }
}

class SiteExporter {
    private $baseUrl;
    private $exportDir;
    private $stats = [
        'files' => 0,
        'html' => 0,
        'css' => 0,
        'js' => 0,
        'images' => 0,
        'assets' => 0
    ];
    
    public function __construct($baseUrl, $exportDir) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->exportDir = rtrim($exportDir, '/');
        
        // Create export directory
        if (!is_dir($this->exportDir)) {
            mkdir($this->exportDir, 0755, true);
        }
        
        if (!is_writable($this->exportDir)) {
            error_log("Export: Export directory is NOT writable: " . $this->exportDir);
        } else {
            error_log("Export: Export directory is writable: " . $this->exportDir);
        }
    }
    
    public function export() {
        error_log("Export: Starting internal rendering export...");
        
        // 1. Export regular pages from CONTENT_DIR
        $contentDir = CONTENT_DIR;
        if (is_dir($contentDir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($contentDir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            $files = new RegexIterator($files, '/\\.md$/');
            
            foreach ($files as $file) {
                error_log("Export: Found content file: " . $file->getPathname());
                // Skip files in _preview directory
                if (strpos($file->getPathname(), '/content/_preview/') !== false) {
                    continue;
                }
                
                $relativePath = str_replace($contentDir . '/', '', $file->getPathname());
                $pathWithoutExt = substr($relativePath, 0, -3);
                
                $this->exportPage($pathWithoutExt);
            }
        }

        // 2. Export blog pages if blog plugin is active
        if (function_exists('blog_load_posts')) {
            error_log("Export: Exporting blog content...");
            // Export blog index
            $this->exportPage('blog');
            
            // Export individual posts
            $posts = blog_load_posts();
            error_log("Export: Found " . count($posts) . " blog posts to consider");
            foreach ($posts as $post) {
                if (isset($post['status']) && $post['status'] === 'published' && isset($post['slug'])) {
                    error_log("Export: Exporting post: " . $post['slug']);
                    $this->exportPage('blog/' . $post['slug']);
                } else {
                    error_log("Export: Skipping post: " . ($post['slug'] ?? 'unknown') . " (status: " . ($post['status'] ?? 'unknown') . ")");
                }
            }
            
            // Export RSS feed (special handling)
            $this->exportRssFeed();
        }
        
        // 3. Copy assets
        $this->finishExport();
        
        error_log("Export: FINISHED. Total stats: " . json_encode($this->stats));
        return $this->stats;
    }

    private function exportPage($path) {
        error_log("Export: Rendering page: $path");
        $html = fcms_export_render_page($path);
        
        if ($html) {
            error_log("Export: Successfully rendered $path, length: " . strlen($html));
            // Determine output file
            if ($path === 'home') {
                $outputFile = $this->exportDir . '/index.html';
            } else {
                $outputFile = $this->exportDir . '/' . $path . '/index.html';
            }
            
            @mkdir(dirname($outputFile), 0755, true);
            $writeResult = file_put_contents($outputFile, $html);
            if ($writeResult === false) {
                error_log("Export: FAILED to write to $outputFile");
            } else {
                error_log("Export: Successfully wrote " . $writeResult . " bytes to $outputFile");
            }
            
            $this->stats['files']++;
            $this->stats['html']++;
            return true;
        } else {
            error_log("Export: Rendered HTML is EMPTY for $path");
        }
        return false;
    }

    private function exportRssFeed() {
        if (function_exists('blog_generate_rss')) {
            $rss = blog_generate_rss();
            $outputFile = $this->exportDir . '/blog/rss.xml';
            @mkdir(dirname($outputFile), 0755, true);
            file_put_contents($outputFile, $rss);
            $this->stats['files']++;
            error_log("Export: Exported RSS feed");
        }
    }
    
    private function finishExport() {
        // Copy uploads directory
        $uploadsSource = PROJECT_ROOT . '/uploads';
        if (is_dir($uploadsSource)) {
            $this->copyDirectory($uploadsSource, $this->exportDir . '/uploads');
        }
        
        // Copy theme assets
        $themesSource = THEMES_DIR;
        if (is_dir($themesSource)) {
            foreach (scandir($themesSource) as $theme) {
                if ($theme !== '.' && $theme !== '..' && is_dir($themesSource . '/' . $theme . '/assets')) {
                    $this->copyDirectory(
                        $themesSource . '/' . $theme . '/assets',
                        $this->exportDir . '/themes/' . $theme . '/assets'
                    );
                }
            }
        }
        
        // Copy public/css/output.css
        $cssSource = PROJECT_ROOT . '/public/css/output.css';
        if (file_exists($cssSource)) {
            @mkdir($this->exportDir . '/public/css', 0755, true);
            copy($cssSource, $this->exportDir . '/public/css/output.css');
            $this->stats['files']++;
            $this->stats['css']++;
        }
    }
    
    private function copyDirectory($source, $destination) {
        if (!is_dir($destination)) {
            @mkdir($destination, 0755, true);
        }
        
        foreach (scandir($source) as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $itemSource = $source . '/' . $item;
            $itemDest = $destination . '/' . $item;
            
            if (is_dir($itemSource)) {
                $this->copyDirectory($itemSource, $itemDest);
            } else {
                @copy($itemSource, $itemDest);
                $this->stats['files']++;
                $this->stats['assets']++;
            }
        }
    }
}

// Helper function for recursive delete
if (!function_exists('recursiveDelete')) {
    function recursiveDelete($path) {
        if (is_dir($path)) {
            foreach (scandir($path) as $item) {
                if ($item === '.' || $item === '..') continue;
                recursiveDelete($path . '/' . $item);
            }
            rmdir($path);
        } elseif (file_exists($path)) {
            unlink($path);
        }
    }
}

// Handle export request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export_site') {
    try {
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $exportDir = PROJECT_ROOT . '/export';
        
        // Clean old export
        if (is_dir($exportDir)) {
            recursiveDelete($exportDir);
        }
        
        $exporter = new SiteExporter($baseUrl, $exportDir);
        $stats = $exporter->export();
        
        // Determine if it's an AJAX request
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
                  (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
                  (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Export completed successfully',
                'stats' => $stats,
                'exportPath' => '/export/'
            ]);
        } else {
            // Store stats in session for the dashboard to display
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['success'] = "Site exported successfully! " . $stats['html'] . " pages, " . $stats['assets'] . " assets.";
            header('Location: /admin/?action=dashboard');
        }
    } catch (Exception $e) {
        error_log('Export error: ' . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}
