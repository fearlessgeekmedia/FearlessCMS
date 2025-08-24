<?php
// Get CMS mode manager
global $cmsModeManager;

// Extract content without metadata
$contentWithoutMetadata = $contentData;
$metadata = [];
if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $contentData, $matches)) {
    $metadata = json_decode($matches[1], true);
    $contentWithoutMetadata = substr($contentData, strlen($matches[0]));
}

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
        <div class="flex gap-4">
            <button type="button" onclick="previewContent()" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                Preview
            </button>
            <a href="?action=dashboard" class="text-gray-500 hover:text-gray-600">Back to Dashboard</a>
        </div>
    </div>

    <form method="POST" id="editForm" class="space-y-6">
        <input type="hidden" name="action" value="save_content">
        <input type="hidden" name="path" value="<?php echo htmlspecialchars($path); ?>">
        <input type="hidden" name="editor_mode" value="html">
        <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>

        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block mb-2">Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>" class="w-full px-3 py-2 border border-gray-300 rounded">
            </div>
            <div>
                <label class="block mb-2">Parent Page</label>
                <select name="parent" class="w-full px-3 py-2 border border-gray-300 rounded">
                    <option value="">None (Top Level)</option>
                    <?php foreach ($pages as $pagePath => $pageTitle): ?>
                        <?php if ($pagePath !== $path): // Don't allow self as parent ?>
                            <option value="<?php echo htmlspecialchars($pagePath); ?>" <?php echo (isset($metadata['parent']) && $metadata['parent'] === $pagePath) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pageTitle); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block mb-2">Template</label>
            <select name="template" class="w-full px-3 py-2 border border-gray-300 rounded">
                <?php foreach ($templates as $template): ?>
                    <option value="<?php echo htmlspecialchars($template); ?>" <?php echo (isset($metadata['template']) && $metadata['template'] === $template) ? 'selected' : ''; ?>>
                        <?php echo ucfirst(htmlspecialchars($template)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block mb-2">Content</label>
            <p class="text-sm text-gray-600 mb-2">Editing in HTML mode with Quill.js editor</p>
            
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
                <textarea id="codeEditor" class="w-full h-96 p-4 font-mono text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Edit HTML code here..."><?php echo htmlspecialchars($contentWithoutMetadata); ?></textarea>
                <div class="mt-2 text-sm text-gray-600">
                    <p>💡 <strong>Code View Mode:</strong> Edit raw HTML code. Use this for precise formatting, custom HTML, or troubleshooting.</p>
                </div>
            </div>
            
            <input type="hidden" name="content" id="content">
        </div>

        <div class="flex justify-end gap-4">
            <button type="button" onclick="window.location.href='?action=dashboard'" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50">Cancel</button>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Save Changes</button>
        </div>
    </form>
</div>

<!-- Quill.js Editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

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
    color: #1e293b;
}

.quill-editor .ql-editor h1 {
    font-size: 2em;
}

.quill-editor .ql-editor h2 {
    font-size: 1.5em;
}

.quill-editor .ql-editor h3 {
    font-size: 1.25em;
}

.quill-editor .ql-editor p {
    margin-bottom: 1em;
}

.quill-editor .ql-editor blockquote {
    border-left: 4px solid #e2e8f0;
    margin: 1em 0;
    padding-left: 1em;
    color: #64748b;
}

.quill-editor .ql-editor code {
    background: #f1f5f9;
    padding: 0.2em 0.4em;
    border-radius: 3px;
    font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
}

.quill-editor .ql-editor pre {
    background: #f1f5f9;
    padding: 1rem;
    border-radius: 4px;
    margin: 1em 0;
    overflow-x: auto;
}

.quill-editor .ql-editor ul,
.quill-editor .ql-editor ol {
    margin-bottom: 1em;
    padding-left: 1.5em;
}

.quill-editor .ql-editor li {
    margin-bottom: 0.5em;
}

.quill-editor .ql-editor a {
    color: #3b82f6;
    text-decoration: underline;
}

.quill-editor .ql-editor a:hover {
    color: #2563eb;
}

/* Toolbar button styling */
.quill-editor .ql-toolbar button {
    color: #475569;
}

.quill-editor .ql-toolbar button:hover {
    color: #1e293b;
}

.quill-editor .ql-toolbar button.ql-active {
    color: #3b82f6;
}

.quill-editor .ql-toolbar .ql-stroke {
    stroke: currentColor;
}

.quill-editor .ql-toolbar .ql-fill {
    fill: currentColor;
}

.quill-editor .ql-toolbar .ql-picker {
    color: #475569;
}

.quill-editor .ql-toolbar .ql-picker-options {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Code Editor Styling */
#codeEditor {
    font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, 'Courier New', monospace;
    font-size: 13px;
    line-height: 1.5;
    background: #1e293b;
    color: #e2e8f0;
    border: 1px solid #475569;
    border-radius: 8px;
    padding: 1rem;
    resize: vertical;
    min-height: 400px;
}

#codeEditor:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Mode Toggle Styling */
#toggleMode {
    transition: all 0.2s ease;
    font-weight: 500;
}

#toggleMode:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

#modeIndicator {
    font-weight: 500;
    padding: 0.25rem 0.5rem;
    background: #f1f5f9;
    border-radius: 4px;
    border: 1px solid #e2e8f0;
}

/* Code Editor Container */
#codeEditorContainer {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #f8fafc;
    padding: 1rem;
}

#codeEditorContainer p {
    margin: 0;
    line-height: 1.5;
}
</style>

<script>
// Make editor globally accessible
let editor;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Quill.js
    if (typeof Quill !== 'undefined') {
        editor = new Quill('#richEditorContainer', { // Initialize with rich editor container
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'header': 1 }, { 'header': 2 }],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    [{ 'script': 'sub' }, { 'script': 'super' }],
                    [{ 'indent': '-1' }, { 'indent': '+1' }],
                    [{ 'direction': 'rtl' }],
                    [{ 'size': ['small', false, 'large', 'huge'] }],
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'font': [] }],
                    [{ 'align': [] }],
                    ['clean'],
                    ['link', 'image', 'video']
                ],
                clipboard: {
                    matchVisual: false,
                },
                keyboard: {
                    bindings: {
                        tab: {
                            key: 'Tab',
                            handler: function(range, context) {
                                Quill.insertText(range, '    ', Quill.sources.api.format('indent', -1));
                            }
                        }
                    }
                }
            },
            placeholder: 'Start writing your content in HTML...',
            readOnly: false,
            bounds: '.quill-editor'
        });

        console.log('Quill.js editor initialized successfully');
        
        // Set initial content
        const initialContent = <?php echo json_encode($contentWithoutMetadata, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES); ?>;
        if (initialContent && initialContent.trim()) {
            editor.root.innerHTML = initialContent;
        }
        
        // Toggle between rich editor and code editor
        document.getElementById('toggleMode').addEventListener('click', function() {
            const richEditorContainer = document.getElementById('richEditorContainer');
            const codeEditorContainer = document.getElementById('codeEditorContainer');
            const toggleButton = document.getElementById('toggleMode');
            const modeIndicator = document.getElementById('modeIndicator');
            const codeEditor = document.getElementById('codeEditor');

            if (richEditorContainer.classList.contains('hidden')) {
                // Switching from Code View to Rich Editor
                richEditorContainer.classList.remove('hidden');
                codeEditorContainer.classList.add('hidden');
                modeIndicator.textContent = 'Rich Editor Mode';
                toggleButton.textContent = 'Switch to Code View';
                toggleButton.className = 'px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors';
                
                // Update rich editor with code editor content
                const htmlContent = codeEditor.value;
                editor.root.innerHTML = htmlContent;
                editor.enable();
                
            } else {
                // Switching from Rich Editor to Code View
                richEditorContainer.classList.add('hidden');
                codeEditorContainer.classList.remove('hidden');
                modeIndicator.textContent = 'Code View Mode';
                toggleButton.textContent = 'Switch to Rich Editor';
                toggleButton.className = 'px-3 py-1 text-sm bg-green-500 text-white rounded hover:bg-green-600 transition-colors';
                
                // Update code editor with rich editor content
                const htmlContent = editor.root.innerHTML;
                codeEditor.value = htmlContent;
            }
        });

        // Keyboard shortcut for mode switching (Ctrl+Shift+C)
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'C') {
                e.preventDefault();
                document.getElementById('toggleMode').click();
            }
        });

        // Sync content between editors when switching modes
        document.getElementById('codeEditor').addEventListener('input', function() {
            // Update the hidden content field when code editor changes
            document.getElementById('content').value = this.value;
        });

        // Update hidden input before form submission (handle both modes)
        document.getElementById('editForm').addEventListener('submit', function() {
            const richEditorContainer = document.getElementById('richEditorContainer');
            const codeEditor = document.getElementById('codeEditor');
            
            if (richEditorContainer.classList.contains('hidden')) {
                // In code view mode, use code editor content
                document.getElementById('content').value = codeEditor.value;
            } else {
                // In rich editor mode, use Quill content
                document.getElementById('content').value = editor.root.innerHTML;
            }
        });

    } else {
        console.error('Quill.js not loaded');
        document.getElementById('richEditorContainer').innerHTML = '<div class="p-4 text-red-600">Error: Quill.js editor failed to load. Please refresh the page.</div>';
    }
});

function previewContent() {
    if (!editor) {
        alert('Editor not initialized');
        return;
    }

    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    formData.append('action', 'preview_content');

    // Get the current editor content
    const editorContent = editor.root.innerHTML;
    formData.set('content', editorContent);

    // Send to admin endpoint instead of root index.php
    fetch('?action=preview_content', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.open(data.previewUrl, '_blank');
        } else {
            alert('Failed to create preview: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to create preview. Please try again.');
    });
}
</script>
