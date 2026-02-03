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

class SiteExporter {
    private $baseUrl;
    private $exportDir;
    private $downloadedUrls = [];
    private $stats = [
        'files' => 0,
        'html' => 0,
        'css' => 0,
        'js' => 0,
        'images' => 0,
        'assets' => 0
    ];
    
    public function __construct($baseUrl, $exportDir) {
        // Replace localhost with 127.0.0.1 for reliability
        $this->baseUrl = rtrim(str_replace('localhost', '127.0.0.1', $baseUrl), '/');
        $this->exportDir = rtrim($exportDir, '/');
        
        // Create export directory
        if (!is_dir($this->exportDir)) {
            mkdir($this->exportDir, 0755, true);
        }
    }
    
    public function export() {
        // Use the main index.php router to render pages with theme
        $contentDir = CONTENT_DIR;
        if (is_dir($contentDir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($contentDir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            $files = new RegexIterator($files, '/\\.md$/');
            
            foreach ($files as $file) {
                // Skip files in _preview directory
                if (strpos($file->getPathname(), '/content/_preview/') !== false) {
                    continue;
                }
                
                $relativePath = str_replace($contentDir . '/', '', $file->getPathname());
                $pathWithoutExt = substr($relativePath, 0, -3);
                
                try {
                    // Render page by simulating a request
                    $html = $this->renderPageWithTheme($pathWithoutExt);
                    
                    if ($html) {
                        // Create output directory and file
                        // Home page goes to root index.html, others go to their own directories
                        if ($pathWithoutExt === 'home') {
                            $outputFile = $this->exportDir . '/index.html';
                        } else {
                            $outputFile = $this->exportDir . '/' . $pathWithoutExt . '/index.html';
                        }
                        @mkdir(dirname($outputFile), 0755, true);
                        file_put_contents($outputFile, $html);
                        
                        $this->stats['files']++;
                        $this->stats['html']++;
                        error_log("Export: Exported page: $pathWithoutExt");
                    }
                } catch (Exception $e) {
                    error_log("Export: Failed to export $pathWithoutExt: " . $e->getMessage());
                }
            }
        }
        
        $this->finishExport();
        return $this->stats;
    }
    
    private function renderPageWithTheme($path) {
        // Simulate a page request by buffering the output from the index.php file
        ob_start();
        
        try {
            // Set up a simulated request
            $_GET['page'] = $path;
            $_SERVER['REQUEST_URI'] = '/' . $path;
            $_SERVER['QUERY_STRING'] = 'page=' . urlencode($path);
            
            // Include the main router which handles page rendering
            require PROJECT_ROOT . '/index.php';
            
            $html = ob_get_clean();
            return $html ?: false;
        } catch (Exception $e) {
            ob_end_clean();
            error_log("Export renderPageWithTheme error: " . $e->getMessage());
            return false;
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
        
    }
    
    private function downloadPage($url, $outputDir) {
        // Prevent infinite loops
        if (isset($this->downloadedUrls[$url])) {
            return;
        }
        $this->downloadedUrls[$url] = true;
        
        $html = $this->fetchUrl($url);
        if (!$html) {
            return;
        }
        
        // Determine output file
        $outputFile = $outputDir . '/index.html';
        if (strpos($url, '/rss') !== false) {
            $outputFile = $outputDir . '/rss.xml';
        }
        
        @mkdir($outputDir, 0755, true);
        file_put_contents($outputFile, $html);
        $this->stats['files']++;
        $this->stats['html']++;
        
        // Extract and download CSS
        preg_match_all('/<link[^>]*href="([^"]*\.css[^"]*)"/', $html, $cssMatches);
        foreach ($cssMatches[1] as $css) {
            $this->downloadAsset($css, 'css');
        }
        
        // Extract and download JS
        preg_match_all('/<script[^>]*src="([^"]*\.js[^"]*)"/', $html, $jsMatches);
        foreach ($jsMatches[1] as $js) {
            $this->downloadAsset($js, 'js');
        }
        
        // Extract and download images
        preg_match_all('/<(img|source)[^>]*(?:src|srcset)="([^"]*\.(?:png|jpg|jpeg|gif|svg|ico|webp)[^"]*)"/', $html, $imgMatches);
        foreach ($imgMatches[2] as $img) {
            $this->downloadAsset($img, 'image');
        }
        
        // Extract and download fonts
        preg_match_all('/<link[^>]*href="([^"]*\.(?:woff|woff2|ttf|eot)[^"]*)"/', $html, $fontMatches);
        foreach ($fontMatches[1] as $font) {
            $this->downloadAsset($font, 'asset');
        }
        
        // Extract page links for crawling
        preg_match_all('/<a[^>]*href="([^"#?]*)"/', $html, $linkMatches);
        foreach ($linkMatches[1] as $link) {
            if (!empty($link) && strpos($link, 'http') !== 0 && strpos($link, 'mailto:') !== 0) {
                $absoluteUrl = $this->baseUrl . (strpos($link, '/') === 0 ? $link : '/' . $link);
                $pageDir = $this->exportDir . (strpos($link, '/') === 0 ? $link : '/' . $link);
                
                if (!isset($this->downloadedUrls[$absoluteUrl])) {
                    $this->downloadPage($absoluteUrl, $pageDir);
                }
            }
        }
    }
    
    private function downloadAsset($path, $type) {
        // Convert relative paths to absolute
        if (strpos($path, 'http') === 0) {
            $url = $path;
        } else {
            $url = $this->baseUrl . (strpos($path, '/') === 0 ? $path : '/' . $path);
        }
        
        // Check if already downloaded
        if (isset($this->downloadedUrls[$url])) {
            return;
        }
        $this->downloadedUrls[$url] = true;
        
        $outputPath = $this->exportDir . (strpos($path, '/') === 0 ? $path : '/' . $path);
        $this->downloadFile($url, $outputPath);
        
        $this->stats['files']++;
        if ($type === 'css') $this->stats['css']++;
        elseif ($type === 'js') $this->stats['js']++;
        elseif ($type === 'image') $this->stats['images']++;
        elseif ($type === 'asset') $this->stats['assets']++;
    }
    
    private function downloadFile($url, $outputPath) {
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $content = $this->fetchUrl($url);
        if ($content !== false) {
            file_put_contents($outputPath, $content);
        }
    }
    
    private function fetchUrl($url) {
        error_log("Export: Fetching URL: $url");
        
        // Use file_get_contents with stream context
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'user_agent' => 'FearlessCMS Exporter',
                'follow_location' => true,
                'max_redirects' => 5
            ],
            'https' => [
                'method' => 'GET',
                'timeout' => 10,
                'user_agent' => 'FearlessCMS Exporter',
                'follow_location' => true,
                'max_redirects' => 5,
                'verify_peer' => false
            ]
        ]);
        
        $content = @file_get_contents($url, false, $context);
        
        if ($content === false) {
            error_log("Export: Failed to fetch $url - check server is running on correct port");
            return false;
        }
        
        error_log("Export: Successfully fetched $url (" . strlen($content) . " bytes)");
        return $content;
    }
    
    private function copyDirectory($source, $destination) {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        foreach (scandir($source) as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $itemSource = $source . '/' . $item;
            $itemDest = $destination . '/' . $item;
            
            if (is_dir($itemSource)) {
                $this->copyDirectory($itemSource, $itemDest);
            } else {
                copy($itemSource, $itemDest);
                $this->stats['files']++;
                $this->stats['assets']++;
            }
        }
    }
}

// Helper function for recursive delete
function recursiveDelete($path) {
    if (is_dir($path)) {
        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') continue;
            recursiveDelete($path . '/' . $item);
        }
        rmdir($path);
    } else {
        unlink($path);
    }
}

// Handle export request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export_site') {
    try {
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        error_log("Export: Starting export with baseUrl: $baseUrl");
        error_log("Export: HTTP_HOST = " . $_SERVER['HTTP_HOST']);
        error_log("Export: SERVER_NAME = " . $_SERVER['SERVER_NAME']);
        error_log("Export: SERVER_PORT = " . $_SERVER['SERVER_PORT']);
        
        $exportDir = PROJECT_ROOT . '/export';
        
        // Clean old export
        if (is_dir($exportDir)) {
            recursiveDelete($exportDir);
        }
        
        $exporter = new SiteExporter($baseUrl, $exportDir);
        $stats = $exporter->export();
        error_log("Export: Export completed with stats: " . json_encode($stats));
        
        echo json_encode([
            'success' => true,
            'message' => 'Export completed successfully',
            'stats' => $stats,
            'exportPath' => '/export/'
        ]);
    } catch (Exception $e) {
        error_log('Export error: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}
?>
