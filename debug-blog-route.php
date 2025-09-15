<?php
/**
 * Debug script to test blog routing
 */

// Define constants
define('PROJECT_ROOT', __DIR__);
define('CONFIG_DIR', PROJECT_ROOT . '/config');
define('CONTENT_DIR', PROJECT_ROOT . '/content');

// Include necessary files
require_once 'includes/DemoModeManager.php';
require_once 'includes/plugins.php';

// Start session
if (function_exists('session_start')) {
    session_start();
}

echo "Debug Blog Route Test\n";
echo "===================\n\n";

// Test the blog route handling
$path = 'blog/this-is-a-test';
echo "Testing path: $path\n\n";

// Initialize variables for plugin handling
$handled = false;
$title = '';
$content = '';

echo "1. Before hook execution:\n";
echo "   - Handled: " . ($handled ? 'true' : 'false') . "\n";
echo "   - Title: '$title'\n";
echo "   - Content: '$content'\n\n";

// Let plugins handle the route first
fcms_do_hook_ref('route', $handled, $title, $content, $path);

echo "2. After hook execution:\n";
echo "   - Handled: " . ($handled ? 'true' : 'false') . "\n";
echo "   - Title: '$title'\n";
echo "   - Content length: " . strlen($content) . "\n\n";

if ($handled) {
    echo "3. Blog plugin handled the route successfully!\n";
    echo "   - Title: $title\n";
    echo "   - Content preview: " . substr(strip_tags($content), 0, 100) . "...\n";
} else {
    echo "3. Blog plugin did NOT handle the route.\n";
    echo "   This means the main content routing will try to find a file.\n";
}

echo "\n4. Demo session status:\n";
$demoManager = new DemoModeManager();
echo "   - Demo session: " . ($demoManager->isDemoSession() ? 'true' : 'false') . "\n";
echo "   - Demo user session: " . ($demoManager->isDemoUserSession() ? 'true' : 'false') . "\n";

echo "\n5. Testing blog_load_posts():\n";
// Test the blog_load_posts function
if (function_exists('blog_load_posts')) {
    $posts = blog_load_posts();
    echo "   - Number of posts: " . count($posts) . "\n";
    if (!empty($posts)) {
        foreach ($posts as $post) {
            echo "     * " . $post['title'] . " (slug: " . $post['slug'] . ")\n";
        }
    }
} else {
    echo "   - blog_load_posts() function not found\n";
}

echo "\nDebug completed!\n";
?>