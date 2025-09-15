<?php
/**
 * Simple test for demo blog functionality
 */

// Define constants
define('PROJECT_ROOT', __DIR__);
define('CONFIG_DIR', PROJECT_ROOT . '/config');
define('CONTENT_DIR', PROJECT_ROOT . '/content');

// Include necessary files
require_once 'includes/DemoModeManager.php';

echo "Testing Demo Blog Functionality\n";
echo "==============================\n\n";

echo "1. Demo Content Directory Check:\n";
$demoManager = new DemoModeManager();

echo "\n2. Demo Content Directory:\n";
$demoContentDir = $demoManager->getDemoContentDir();
echo "   - Demo content dir: " . $demoContentDir . "\n";
$demoBlogDir = $demoContentDir . '/blog';
echo "   - Demo blog dir: " . $demoBlogDir . "\n";
echo "   - Demo blog dir exists: " . (is_dir($demoBlogDir) ? 'true' : 'false') . "\n";

if (is_dir($demoBlogDir)) {
    $files = glob($demoBlogDir . '/*.md');
    echo "   - Markdown files in demo blog dir: " . count($files) . "\n";
    foreach ($files as $file) {
        echo "     * " . basename($file) . "\n";
        
        // Test parsing the file
        $content = file_get_contents($file);
        echo "       Content length: " . strlen($content) . " bytes\n";
        
        // Test frontmatter parsing
        if (preg_match('/<!-- json\s*(.*?)\s*-->/s', $content, $matches)) {
            $metadata = json_decode($matches[1], true);
            if ($metadata) {
                echo "       Title: " . ($metadata['title'] ?? 'N/A') . "\n";
                echo "       Demo post: " . (isset($metadata['demo_post']) ? ($metadata['demo_post'] ? 'true' : 'false') : 'not set') . "\n";
                echo "       Session ID: " . ($metadata['demo_session_id'] ?? 'N/A') . "\n";
            } else {
                echo "       Failed to parse metadata JSON\n";
            }
        } else {
            echo "       No frontmatter found\n";
        }
    }
}

echo "\n3. Testing blog_load_demo_posts function:\n";

// Define the function locally for testing
function blog_load_demo_posts() {
    $demoManager = new DemoModeManager();
    $demoContentDir = $demoManager->getDemoContentDir();
    $demoBlogDir = $demoContentDir . '/blog';
    
    if (!is_dir($demoBlogDir)) return [];
    
    $posts = [];
    $files = glob($demoBlogDir . '/*.md');
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $slug = basename($file, '.md');
        
        // Parse frontmatter
        if (preg_match('/<!-- json\s*(.*?)\s*-->/s', $content, $matches)) {
            $metadata = json_decode($matches[1], true);
            if ($metadata) {
                $posts[] = [
                    'id' => crc32($slug), // Generate consistent ID
                    'title' => $metadata['title'] ?? $slug,
                    'slug' => $slug,
                    'date' => $metadata['date'] ?? date('Y-m-d'),
                    'content' => trim(str_replace($matches[0], '', $content)),
                    'status' => 'published',
                    'featured_image' => $metadata['featured_image'] ?? '',
                    'demo_post' => true
                ];
            }
        }
    }
    
    return $posts;
}

$demoPosts = blog_load_demo_posts();
echo "   - Number of demo posts loaded: " . count($demoPosts) . "\n";

if (!empty($demoPosts)) {
    echo "   - Demo posts found:\n";
    foreach ($demoPosts as $post) {
        echo "     * " . $post['title'] . " (slug: " . $post['slug'] . ")\n";
        echo "       Content preview: " . substr(strip_tags($post['content']), 0, 50) . "...\n";
    }
} else {
    echo "   - No demo posts found\n";
}

echo "\nTest completed!\n";
?>