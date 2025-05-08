<?php
// Get all content files for parent selection
$contentFiles = glob(CONTENT_DIR . '/*.md');
$pages = [];
foreach ($contentFiles as $file) {
    $fileContent = file_get_contents($file);
    $pageTitle = '';
    if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $fileContent, $matches)) {
        $pageMetadata = json_decode($matches[1], true);
        if ($pageMetadata && isset($pageMetadata['title'])) {
            $pageTitle = $pageMetadata['title'];
        }
    }
    if (!$pageTitle) {
        $pageTitle = ucwords(str_replace(['-', '_'], ' ', basename($file, '.md')));
    }
    $pages[basename($file, '.md')] = $pageTitle;
}
?>

<div class="bg-white shadow rounded-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold fira-code">Create New Page</h2>
        <div class="flex gap-4">
            <a href="?action=dashboard" class="text-gray-500 hover:text-gray-600">Back to Dashboard</a>
        </div>
    </div>

    <form method="POST" id="editForm" class="space-y-6">
        <input type="hidden" name="action" value="save_content">
        
        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block mb-2">Title</label>
                <input type="text" name="title" required class="w-full px-3 py-2 border border-gray-300 rounded">
            </div>
            <div>
                <label class="block mb-2">URL Slug</label>
                <input type="text" name="path" required pattern="[a-z0-9-]+" title="Only lowercase letters, numbers, and hyphens allowed" class="w-full px-3 py-2 border border-gray-300 rounded">
                <p class="text-sm text-gray-500 mt-1">Use lowercase letters, numbers, and hyphens only</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block mb-2">Parent Page</label>
                <select name="parent" class="w-full px-3 py-2 border border-gray-300 rounded">
                    <option value="">None (Top Level)</option>
                    <?php foreach ($pages as $pagePath => $pageTitle): ?>
                        <option value="<?php echo htmlspecialchars($pagePath); ?>">
                            <?php echo htmlspecialchars($pageTitle); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block mb-2">Template</label>
                <select name="template" class="w-full px-3 py-2 border border-gray-300 rounded">
                    <?php foreach ($templates as $template): ?>
                        <option value="<?php echo htmlspecialchars($template); ?>">
                            <?php echo ucfirst(htmlspecialchars($template)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block mb-2">Content</label>
            <div id="editor" style="height: 600px;"></div>
            <input type="hidden" name="content" id="content">
        </div>

        <div class="flex justify-end gap-4">
            <button type="button" onclick="window.location.href='?action=dashboard'" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50">Cancel</button>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Create Page</button>
        </div>
    </form>
</div>

<!-- Toast UI Editor -->
<link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css" />
<script src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editor = new toastui.Editor({
        el: document.querySelector('#editor'),
        height: '600px',
        initialEditType: 'wysiwyg',
        previewStyle: 'vertical',
        toolbarItems: [
            ['heading', 'bold', 'italic', 'strike'],
            ['hr', 'quote'],
            ['ul', 'ol', 'task', 'indent', 'outdent'],
            ['table', 'link'],
            ['code', 'codeblock']
        ]
    });

    // Generate slug from title
    const titleInput = document.querySelector('input[name="title"]');
    const pathInput = document.querySelector('input[name="path"]');
    titleInput.addEventListener('input', function() {
        if (!pathInput.value) { // Only auto-generate if path is empty
            pathInput.value = this.value
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }
    });

    // Update hidden input before form submission
    document.getElementById('editForm').addEventListener('submit', function() {
        document.getElementById('content').value = editor.getMarkdown();
    });
});
</script> 