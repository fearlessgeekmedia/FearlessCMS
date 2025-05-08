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
if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $contentData, $matches)) {
    $metadata = json_decode($matches[1], true);
    if ($metadata && isset($metadata['template'])) {
        $currentTemplate = $metadata['template'];
    }
}
?>

<div class="bg-white shadow rounded-lg p-6">
    <form method="POST" action="">
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
            <textarea name="content" id="editor" class="w-full border rounded px-3 py-2" rows="20"><?php echo htmlspecialchars($contentData); ?></textarea>
        </div>

        <div class="flex justify-between">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Save</button>
            <a href="?action=dashboard" class="text-gray-600 hover:text-gray-800">Cancel</a>
        </div>
    </form>
</div>

<script>
// Initialize editor
ClassicEditor
    .create(document.querySelector('#editor'))
    .catch(error => {
        console.error(error);
    });
</script> 