<?php
// Get CMS mode manager
global $cmsModeManager;

// Get available templates
$templates = [];
$templateDir = PROJECT_ROOT . '/themes/' . ($themeManager->getActiveTheme() ?? 'punk_rock') . '/templates';
if (is_dir($templateDir)) {
    foreach (glob($templateDir . '/*.html') as $template) {
        $templateName = basename($template, '.html');
        // Exclude 404 template and module files (files ending with .mod)
        if ($templateName !== '404' && !str_ends_with($template, '.html.mod')) {
            $templates[] = $templateName;
        }
    }
}

// Debug output
error_log("New content template - Active theme: " . ($themeManager->getActiveTheme() ?? 'NOT AVAILABLE'));
error_log("New content template - Template directory: " . $templateDir);
error_log("New content template - Templates found: " . print_r($templates, true));

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

// Debug log
error_log("Available templates: " . print_r($templates, true));
?>

<div class="bg-white shadow rounded-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold fira-code">Create New Page</h2>
        <div class="flex gap-4">
            <a href="?action=dashboard" class="text-gray-500 hover:text-gray-600">Back to Dashboard</a>
        </div>
    </div>

    <form method="POST" action="?action=create_page" id="editForm" class="space-y-6">
        <input type="hidden" name="action" value="create_page">
        
        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block mb-2">Title</label>
                <input type="text" name="page_title" required class="w-full px-3 py-2 border border-gray-300 rounded">
            </div>
            <div>
                <label class="block mb-2">URL Slug</label>
                <input type="text" name="new_page_filename" required pattern="[a-z0-9\-]+" title="Only lowercase letters, numbers, and hyphens allowed" class="w-full px-3 py-2 border border-gray-300 rounded">
                <p class="text-sm text-gray-500 mt-1">Use lowercase letters, numbers, and hyphens only</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block mb-2">Parent Page</label>
                <select name="parent_page" class="w-full px-3 py-2 border border-gray-300 rounded">
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
                    <?php if (empty($templates)): ?>
                        <option value="page">Page (No templates found)</option>
                    <?php else: ?>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?php echo htmlspecialchars($template); ?>">
                                <?php echo ucfirst(htmlspecialchars($template)); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <?php if (empty($templates)): ?>
                    <p class="text-sm text-red-500 mt-1">Debug: No templates found. Template dir: <?php echo htmlspecialchars($templateDir); ?></p>
                <?php else: ?>
                    <p class="text-sm text-gray-500 mt-1">Found <?php echo count($templates); ?> templates</p>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <label class="block mb-2">Content</label>
            <div id="editor" style="height: 600px;"></div>
            <input type="hidden" name="new_page_content" id="content">
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
            ['table', 'link'<?php if ($cmsModeManager->canUploadContentImages()): ?>, 'image'<?php endif; ?>],
            ['code', 'codeblock']
        ]<?php if ($cmsModeManager->canUploadContentImages()): ?>,
        hooks: {
            addImageBlobHook: function(blob, callback) {
                // Create form data
                const formData = new FormData();
                formData.append('file', blob);
                formData.append('action', 'upload_image');

                // Upload image
                fetch('?action=upload_image', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        callback(data.url);
                    } else {
                        alert('Failed to upload image: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to upload image');
                });
            }
        }<?php endif; ?>
    });

    // Generate slug from title
    const titleInput = document.querySelector('input[name="page_title"]');
    const pathInput = document.querySelector('input[name="new_page_filename"]');
    titleInput.addEventListener('input', function() {
        if (!pathInput.value) { // Only auto-generate if path is empty
            pathInput.value = this.value
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }
    });

    // Update hidden input before form submission
    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default submission
        const content = editor.getMarkdown();
        document.getElementById('content').value = content;
        console.log('Submitting form with data:', {
            action: this.action,
            page_title: this.page_title.value,
            new_page_filename: this.new_page_filename.value,
            parent_page: this.parent_page.value,
            template: this.template.value,
            content_length: content.length
        });
        this.submit(); // Submit the form
    });
});
</script> 