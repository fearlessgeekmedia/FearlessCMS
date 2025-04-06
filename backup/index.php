<?php
require_once __DIR__ . '/themes/ThemeManager.php';

// Initialize theme manager
$themeManager = new ThemeManager();

// Load content based on URL
$page = isset($_GET['page']) ? $_GET['page'] : 'about';
$contentFile = "content/$page.md";

if (!file_exists($contentFile)) {
    $content = "Page not found!";
    $title = "404";
    $template = $themeManager->getTemplate('404', 'page');
} else {
    // Get title and content from the markdown file
    $content = file_get_contents($contentFile);
    $title = ucfirst($page);
    $template = $themeManager->getTemplate('page');
}

// Replace placeholders with actual content
$template = str_replace('{{title}}', $title, $template);
$template = str_replace('{{content}}', nl2br($content), $template);

// Output the final page
echo $template;
