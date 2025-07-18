<?php echo "<div style='color:red'>DEBUG: editorMode is " . (isset($editorMode) ? $editorMode : 'NOT SET') . "</div>"; ?>
<?php
// Get available templates
$templates = [];
$templateDir = PROJECT_ROOT . '/themes/' . $themeManager->getActiveTheme() . '/templates';
if (is_dir($templateDir)) {
    foreach (glob($templateDir . '/*.html') as $template) {
        $templateName = basename($template, '.html');
        if ($templateName !== '404') { // Exclude 404 template
            $templates[] = $templateName;
        }
    }
}

// Get current template from metadata
$currentTemplate = 'page'; // Default template
$contentWithoutMetadata = $contentData;
if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $contentData, $matches)) {
    $metadata = json_decode($matches[1], true);
    if ($metadata && isset($metadata['template'])) {
        $currentTemplate = $metadata['template'];
    }
    // Remove the metadata from the content
    $contentWithoutMetadata = preg_replace('/^<!--\s*json\s*.*?\s*-->\s*/s', '', $contentData);
}
?>

<div class="bg-white shadow rounded-lg p-6">
    <form method="POST" action="" id="content-form">
        <input type="hidden" name="action" value="save_content" />
        <input type="hidden" name="path" value="<?php echo htmlspecialchars($path); ?>" />
        
        <div class="mb-4">
            <label class="block mb-1">Title</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>" class="w-full border rounded px-3 py-2" required>
        </div>

        <div class="mb-4">
            <label class="block mb-1">Template</label>
            <select name="template" class="w-full border rounded px-3 py-2">
                <?php foreach ($templates as $template): ?>
                <option value="<?php echo htmlspecialchars($template); ?>" <?php echo $template === $currentTemplate ? 'selected' : ''; ?>>
                    <?php echo ucfirst(htmlspecialchars($template)); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label class="block mb-1">Content</label>
            <textarea name="content" id="content" style="width: 100%; height: 600px; font-family: monospace;"><?php echo htmlspecialchars($contentWithoutMetadata); ?></textarea>
        </div>

        <div class="flex justify-between">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Save</button>
            <a href="?action=dashboard" class="text-gray-600 hover:text-gray-800">Cancel</a>
        </div>
    </form>
</div> 