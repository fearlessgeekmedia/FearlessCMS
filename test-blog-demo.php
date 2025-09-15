<?php
/**
 * Test script for blog demo functionality
 */

// Define constants
define('PROJECT_ROOT', __DIR__);
define('CONFIG_DIR', PROJECT_ROOT . '/config');
define('CONTENT_DIR', PROJECT_ROOT . '/content');

// Include necessary files
require_once 'includes/DemoModeManager.php';
require_once 'plugins/blog/blog.php';

// Start session
session_start();

echo "Testing Blog Demo Functionality\n";
echo "==============================\n\n";

// Simulate a demo session
$_SESSION['demo_mode'] = true;
$_SESSION['demo_session_id'] = 'demo_68c85f413ab278.87144679';
$_SESSION['demo_start_time'] = time();

echo "1. Demo Session Status:\n";
$demoManager = new DemoModeManager();
echo "   - Demo session: " . ($demoManager->isDemoSession() ? 'true' : 'false') . "\n";
echo "   - Demo user session: " . ($demoManager->isDemoUserSession() ? 'true' : 'false') . "\n";

echo "\n2. Testing blog_load_posts():\n";
$posts = blog_load_posts();
echo "   - Number of posts loaded: " . count($posts) . "\n";

if (!empty($posts)) {
    echo "   - Posts found:\n";
    foreach ($posts as $post) {
        echo "     * " . $post['title'] . " (slug: " . $post['slug'] . ")\n";
        echo "       Demo post: " . (isset($post['demo_post']) ? ($post['demo_post'] ? 'true' : 'false') : 'not set') . "\n";
    }
} else {
    echo "   - No posts found\n";
}

echo "\n3. Testing blog_load_demo_posts() directly:\n";
$demoPosts = blog_load_demo_posts();
echo "   - Number of demo posts loaded: " . count($demoPosts) . "\n";

if (!empty($demoPosts)) {
    echo "   - Demo posts found:\n";
    foreach ($demoPosts as $post) {
        echo "     * " . $post['title'] . " (slug: " . $post['slug'] . ")\n";
    }
} else {
    echo "   - No demo posts found\n";
}

echo "\n4. Checking demo content directory:\n";
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
    }
}

echo "\nTest completed!\n";
?>