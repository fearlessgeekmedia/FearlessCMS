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

// Get available templates dynamically from the active theme
$templates = [];
$templateDir = PROJECT_ROOT . '/themes/' . ($themeManager->getActiveTheme() ?? 'default') . '/templates';
if (is_dir($templateDir)) {
    foreach (glob($templateDir . '/*.html') as $template) {
        $templateName = basename($template, '.html');
        if ($templateName !== '404' && !str_ends_with($template, '.html.mod')) {
            $templates[] = $templateName;
        }
    }
}

// If no templates found, fall back to defaults
if (empty($templates)) {
    $templates = ['page-with-sidebar', 'page', 'home', 'blog', 'documentation'];
}

// Ensure page-with-sidebar is always first in the list
if (in_array('page-with-sidebar', $templates)) {
    $templates = array_merge(['page-with-sidebar'], array_filter($templates, function($t) { return $t !== 'page-with-sidebar'; }));
}
?>

<div class="bg-white shadow rounded-lg p-6">
    <!-- Success/Error Messages -->
    <?php if (isset($success) && !empty($success)): ?>
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error) && !empty($error)): ?>
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Create New Page</h1>
        <div class="flex gap-4">
            <button type="button" onclick="previewContent()" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                Preview
            </button>
            <a href="?action=dashboard" class="text-gray-500 hover:text-gray-600">Back to Dashboard</a>
        </div>
    </div>

    <form method="POST" id="newPageForm" class="space-y-6">
        <input type="hidden" name="action" value="create_page">
        <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>

        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700">Title</label>
                <input type="text" name="page_title" required class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter page title">
            </div>
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700">URL Slug</label>
                <input type="text" name="new_page_filename" required class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="page-url-slug">
                <p class="text-xs text-gray-500 mt-1">Use lowercase letters, numbers, dashes, and underscores only</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700">Template</label>
                <select name="template" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <?php foreach ($templates as $template): ?>
                        <option value="<?php echo htmlspecialchars($template); ?>" <?php echo $template === 'page-with-sidebar' ? 'selected' : ''; ?>>
                            <?php echo ucfirst(htmlspecialchars($template)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700">Parent Page</label>
                <select name="parent_page" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">None (Top Level)</option>
                    <?php foreach ($pages as $pagePath => $pageTitle): ?>
                        <option value="<?php echo htmlspecialchars($pagePath); ?>"><?php echo htmlspecialchars($pageTitle); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block mb-2 text-sm font-medium text-gray-700">Content</label>
            <p class="text-sm text-gray-600 mb-2">Create your page content using the rich editor below</p>
            
            <!-- Editor Mode Toggle -->
            <div class="mb-3 flex items-center space-x-2">
                <button type="button" id="toggleMode" class="px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                    Switch to Code View
                </button>
                <span class="text-sm text-gray-600" id="modeIndicator">Rich Editor Mode</span>
                <span class="text-xs text-gray-500">(Ctrl+Shift+C to toggle)</span>
            </div>
            
            <!-- Rich Editor Container -->
            <div id="richEditorContainer" class="quill-editor" style="height: 600px;"></div>
            
            <!-- Code Editor Container -->
            <div id="codeEditorContainer" class="hidden">
                <textarea id="codeEditor" name="new_page_content" class="w-full h-96 p-4 font-mono text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter your content here..."></textarea>
                <div class="mt-2 text-sm text-gray-600">
                    <p>💡 <strong>Code View Mode:</strong> Edit raw HTML code. Use this for precise formatting, custom HTML, or troubleshooting.</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-4">
            <button type="button" onclick="window.location.href='?action=dashboard'" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50">Cancel</button>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Create Page</button>
        </div>
    </form>
</div>

<!-- Quill.js Editor - CSS and JS loaded in base template -->

<style>
/* Quill.js Editor Styling */
.quill-editor {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
}

.quill-editor .ql-toolbar {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 8px 8px 0 0;
    padding: 0.5rem;
}

.quill-editor .ql-container {
    border: none;
    border-radius: 0 0 8px 8px;
    min-height: 500px;
}

.quill-editor .ql-editor {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    font-size: 14px;
    line-height: 1.6;
    min-height: 500px;
    padding: 1.5rem;
    background: #ffffff;
}

.quill-editor .ql-editor h1,
.quill-editor .ql-editor h2,
.quill-editor .ql-editor h3,
.quill-editor .ql-editor h4,
.quill-editor .ql-editor h5,
.quill-editor .ql-editor h6 {
    margin-top: 1em;
    margin-bottom: 0.5em;
    font-weight: 600;
    line-height: 1.25;
}

.quill-editor .ql-editor h1 { font-size: 2em; }
.quill-editor .ql-editor h2 { font-size: 1.5em; }
.quill-editor .ql-editor h3 { font-size: 1.25em; }
.quill-editor .ql-editor h4 { font-size: 1em; }
.quill-editor .ql-editor h5 { font-size: 0.875em; }
.quill-editor .ql-editor h6 { font-size: 0.85em; }

.quill-editor .ql-editor p {
    margin-bottom: 1em;
}

.quill-editor .ql-editor ul,
.quill-editor .ql-editor ol {
    margin-bottom: 1em;
    padding-left: 2em;
}

.quill-editor .ql-editor blockquote {
    border-left: 4px solid #e2e8f0;
    margin: 1em 0;
    padding-left: 1em;
    font-style: italic;
    color: #4a5568;
}

.quill-editor .ql-editor code {
    background: #f7fafc;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 0.875em;
}

.quill-editor .ql-editor pre {
    background: #f7fafc;
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin: 1em 0;
}

.quill-editor .ql-editor pre code {
    background: none;
    padding: 0;
}
</style>

<script>
// Initialize Quill.js editor
let quill;
let isRichMode = true;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Quill editor
    quill = new Quill('#richEditorContainer', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'script': 'sub'}, { 'script': 'super' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                [{ 'direction': 'rtl' }],
                [{ 'size': ['small', false, 'large', 'huge'] }],
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'font': [] }],
                [{ 'align': [] }],
                ['clean'],
                ['link', 'image', 'video', 'code-block']
            ]
        },
        placeholder: 'Start writing your content here...'
    });

    // Set initial content
    quill.setText('');

    // Handle form submission
    document.getElementById('newPageForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get content from the appropriate editor
        let content;
        if (isRichMode) {
            content = quill.root.innerHTML;
        } else {
            content = document.getElementById('codeEditor').value;
        }
        
        // Create a hidden input for the content
        let contentInput = document.createElement('input');
        contentInput.type = 'hidden';
        contentInput.name = 'new_page_content';
        contentInput.value = content;
        this.appendChild(contentInput);
        
        // Submit the form
        this.submit();
    });

    // Editor mode toggle
    document.getElementById('toggleMode').addEventListener('click', function() {
        if (isRichMode) {
            // Switch to code view
            document.getElementById('richEditorContainer').classList.add('hidden');
            document.getElementById('codeEditorContainer').classList.remove('hidden');
            document.getElementById('codeEditor').value = quill.root.innerHTML;
            document.getElementById('modeIndicator').textContent = 'Code View Mode';
            this.textContent = 'Switch to Rich Editor';
            isRichMode = false;
        } else {
            // Switch to rich editor
            document.getElementById('richEditorContainer').classList.remove('hidden');
            document.getElementById('codeEditorContainer').classList.add('hidden');
            quill.root.innerHTML = document.getElementById('codeEditor').value;
            document.getElementById('modeIndicator').textContent = 'Rich Editor Mode';
            this.textContent = 'Switch to Code View';
            isRichMode = true;
        }
    });

    // Keyboard shortcut for mode toggle
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && e.key === 'C') {
            e.preventDefault();
            document.getElementById('toggleMode').click();
        }
    });
});

function previewContent() {
    let content;
    if (isRichMode) {
        content = quill.root.innerHTML;
    } else {
        content = document.getElementById('codeEditor').value;
    }
    
    // Open preview in new window
    let previewWindow = window.open('', '_blank');
    previewWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Content Preview</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; padding: 2rem; max-width: 800px; margin: 0 auto; }
                h1, h2, h3, h4, h5, h6 { margin-top: 1.5em; margin-bottom: 0.5em; }
                p { margin-bottom: 1em; }
                ul, ol { margin-bottom: 1em; padding-left: 2em; }
                blockquote { border-left: 4px solid #e2e8f0; margin: 1em 0; padding-left: 1em; font-style: italic; color: #4a5568; }
                code { background: #f7fafc; padding: 0.125rem 0.25rem; border-radius: 0.25rem; font-family: monospace; }
                pre { background: #f7fafc; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; margin: 1em 0; }
                pre code { background: none; padding: 0; }
            </style>
        </head>
        <body>
            ${content}
        </body>
        </html>
    `);
    previewWindow.document.close();
}
</script>
