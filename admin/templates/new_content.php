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
        <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>

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
            <div id="richEditorContainer" class="quill-editor" style="height: 600px;"></div>
            <input type="hidden" name="new_page_content" id="content">
        </div>

        <div class="flex justify-end gap-4">
            <button type="button" onclick="window.location.href='?action=dashboard'" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50">Cancel</button>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Create Page</button>
        </div>
    </form>
</div>

<!-- Quill.js Editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<style>
/* Quill.js Editor Styling */
.quill-editor {
    border: 1px solid #ccc;
    border-radius: 4px;
    background: white;
}

.quill-editor .ql-toolbar {
    border-top: none;
    border-left: none;
    border-right: none;
    border-bottom: 1px solid #ccc;
    background: #f8f9fa;
}

.quill-editor .ql-container {
    border: none;
    font-size: 14px;
}

.quill-editor .ql-editor {
    min-height: 550px;
    padding: 12px 15px;
    line-height: 1.6;
}

.quill-editor .ql-editor h1,
.quill-editor .ql-editor h2,
.quill-editor .ql-editor h3,
.quill-editor .ql-editor h4,
.quill-editor .ql-editor h5,
.quill-editor .ql-editor h6 {
    margin: 1em 0 0.5em 0;
    font-weight: 600;
    line-height: 1.25;
}

.quill-editor .ql-editor h1 {
    font-size: 2em;
    border-bottom: 2px solid #007bff;
    padding-bottom: 0.3em;
}

.quill-editor .ql-editor h2 {
    font-size: 1.5em;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 0.3em;
}

.quill-editor .ql-editor h3 {
    font-size: 1.25em;
}

.quill-editor .ql-editor p {
    margin: 0 0 1em 0;
}

.quill-editor .ql-editor blockquote {
    border-left: 4px solid #007bff;
    margin: 1em 0;
    padding-left: 1em;
    font-style: italic;
    color: #6c757d;
}

.quill-editor .ql-editor code {
    background: #f8f9fa;
    padding: 0.2em 0.4em;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}

.quill-editor .ql-editor pre {
    background: #f8f9fa;
    padding: 1em;
    border-radius: 4px;
    overflow-x: auto;
    border: 1px solid #e9ecef;
}

.quill-editor .ql-editor ul,
.quill-editor .ql-editor ol {
    margin: 1em 0;
    padding-left: 2em;
}

.quill-editor .ql-editor li {
    margin: 0.5em 0;
}

.quill-editor .ql-editor a {
    color: #007bff;
    color: #007bff;
    text-decoration: none;
}

.quill-editor .ql-editor a:hover {
    text-decoration: underline;
}

.quill-editor .ql-toolbar button {
    color: #495057;
}

.quill-editor .ql-toolbar button:hover {
    color: #007bff;
}

.quill-editor .ql-toolbar button.ql-active {
    color: #007bff;
}

.quill-editor .ql-toolbar .ql-stroke {
    stroke: currentColor;
}

.quill-editor .ql-toolbar .ql-fill {
    fill: currentColor;
}

.quill-editor .ql-toolbar .ql-picker {
    color: #495057;
}

.quill-editor .ql-toolbar .ql-picker-options {
    background: white;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Quill.js
    const editor = new Quill('#richEditorContainer', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'align': [] }],
                ['link'<?php if ($cmsModeManager->canUploadContentImages()): ?>, 'image'<?php endif; ?>],
                ['clean']
            ]
        },
        placeholder: 'Start writing your content here...'
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
        const content = editor.root.innerHTML; // Get HTML content from Quill
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
