<?php
// Simple test to see what's happening with the content
require_once __DIR__ . '/includes/config.php';
require_once PROJECT_ROOT . '/includes/ThemeManager.php';
require_once PROJECT_ROOT . '/includes/MenuManager.php';
require_once PROJECT_ROOT . '/includes/WidgetManager.php';
require_once PROJECT_ROOT . '/includes/TemplateRenderer.php';

// Load content
$contentFile = CONTENT_DIR . '/music.md';
$fileContent = file_get_contents($contentFile);

// Extract metadata
$metadata = [];
if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $fileContent, $matches)) {
    $metadata = json_decode($matches[1], true);
    $pageContent = preg_replace('/^<!--\s*json\s*.*?\s*-->\s*/s', '', $fileContent);
} else {
    $pageContent = $fileContent;
}

// Convert markdown to HTML
require_once PROJECT_ROOT . '/includes/Parsedown.php';
$Parsedown = new Parsedown();
$pageContentHtml = $Parsedown->text($pageContent);

echo "=== ORIGINAL CONTENT ===\n";
echo $pageContent;
echo "\n\n=== CONVERTED HTML ===\n";
echo $pageContentHtml;
echo "\n\n=== METADATA ===\n";
print_r($metadata);
?> 