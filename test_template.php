<?php
// Test the full template rendering process
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

// Get site name from config
$configFile = CONFIG_DIR . '/config.json';
$siteName = 'FearlessCMS';
$siteDescription = '';
$custom_css = '';
$custom_js = '';
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if (isset($config['site_name'])) {
        $siteName = $config['site_name'];
    }
}

// Load theme options
$themeOptionsFile = CONFIG_DIR . '/theme_options.json';
$themeOptions = file_exists($themeOptionsFile) ? json_decode(file_get_contents($themeOptionsFile), true) : [];

// Initialize managers
$themeManager = new ThemeManager();
$menuManager = new MenuManager();
$widgetManager = new WidgetManager();

// Initialize template renderer
$templateRenderer = new TemplateRenderer(
    $themeManager->getActiveTheme(),
    $themeOptions,
    $menuManager,
    $widgetManager
);

// Prepare template data
$templateData = [
    'title' => $metadata['title'] ?? 'Music',
    'content' => $pageContentHtml,
    'siteName' => $siteName,
    'siteDescription' => $siteDescription,
    'currentYear' => date('Y'),
    'logo' => $themeOptions['logo'] ?? null,
    'heroBanner' => $themeOptions['herobanner'] ?? null,
    'mainMenu' => $menuManager->renderMenu('main'),
    'custom_css' => $custom_css,
    'custom_js' => $custom_js
];

echo "=== TEMPLATE DATA ===\n";
echo "Content length: " . strlen($templateData['content']) . "\n";
echo "Content preview: " . substr($templateData['content'], 0, 100) . "\n";

// Render template
$templateName = $metadata['template'] ?? 'page';
echo "\n=== RENDERING TEMPLATE: $templateName ===\n";

$template = $templateRenderer->render($templateName, $templateData);

echo "\n=== FINAL OUTPUT ===\n";
echo "Output length: " . strlen($template) . "\n";
echo "Output preview: " . substr($template, 0, 500) . "\n";

// Check for curly braces
if (strpos($template, '{') !== false && strpos($template, '}') !== false) {
    echo "\n=== CURLY BRACES FOUND ===\n";
    $start = strpos($template, '{');
    $end = strrpos($template, '}');
    echo "First { at position: $start\n";
    echo "Last } at position: $end\n";
    echo "Content around braces: " . substr($template, max(0, $start-50), 100) . "\n";
}
?> 