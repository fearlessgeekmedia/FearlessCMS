<?php
// Get CMS mode manager
global $cmsModeManager;

// Debug: Log what variables are available in the template
error_log("DEBUG: Template variables - contentData length: " . (isset($contentData) ? strlen($contentData) : 'NOT SET'));
error_log("DEBUG: Template variables - title: " . (isset($title) ? $title : 'NOT SET'));
error_log("DEBUG: Template variables - path: " . (isset($path) ? $path : 'NOT SET'));
error_log("DEBUG: Template variables - contentData preview: " . (isset($contentData) ? substr($contentData, 0, 200) : 'NOT SET'));

// Extract content without metadata
$contentWithoutMetadata = $contentData ?? '';
$metadata = [];

// Try to extract metadata, but fallback to full content if it fails
if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $contentData, $matches)) {
    $metadata = json_decode($matches[1], true);
    if ($metadata) {
        // Get content after metadata, but be more careful about the extraction
        $metadataEnd = strpos($contentData, '-->', strlen($matches[0]) - 3) + 3;
        $contentWithoutMetadata = substr($contentData, $metadataEnd);
        
        // Trim any leading whitespace/newlines
        $contentWithoutMetadata = ltrim($contentWithoutMetadata, " \t\n\r\0\x0B");
        
        // Debug the extraction
        error_log("DEBUG: Metadata extraction - full match length: " . strlen($matches[0]));
        error_log("DEBUG: Metadata extraction - metadataEnd position: " . $metadataEnd);
        error_log("DEBUG: Metadata extraction - content length after: " . strlen($contentWithoutMetadata));
        error_log("DEBUG: Metadata extraction - content preview: " . substr($contentWithoutMetadata, 0, 100));
    } else {
        // If JSON decode fails, use full content
        $contentWithoutMetadata = $contentData;
    }
} else {
    // No metadata found, use full content
    $contentWithoutMetadata = $contentData;
}

// Ensure we have content to work with
if (empty($contentWithoutMetadata) && !empty($contentData)) {
    $contentWithoutMetadata = $contentData;
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
        <input type="hidden" name="content" id="content">
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
                    <option value="<?php echo htmlspecialchars($template); ?>" <?php echo (isset($currentTemplate) && $currentTemplate === $template) ? 'selected' : ''; ?>>
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
                <textarea id="codeEditor" class="w-full h-96 p-4 font-mono text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Edit HTML code here..."></textarea>
                <div class="mt-2 text-sm text-gray-600">
                    <p>💡 <strong>Code View Mode:</strong> Edit raw HTML code. Use this for precise formatting, custom HTML, or troubleshooting.</p>
                </div>
            </div>
            
        </div>

        <div class="flex justify-end gap-4">
            <button type="button" onclick="window.location.href='?action=dashboard'" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50">Cancel</button>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Save Changes</button>
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
            // Disable Quill's built-in sanitization to preserve HTML structure
            sanitize: false,
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
                    // Allow more HTML elements and preserve styling
                    matchers: []
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
            bounds: '.quill-editor',
            // Allow more HTML elements including div tags
            formats: [
                'header', 'font', 'size',
                'bold', 'italic', 'underline', 'strike', 'blockquote',
                'list', 'bullet', 'indent',
                'link', 'image', 'video',
                'color', 'background',
                'align', 'direction',
                'code', 'script', 'formula',
                'div', 'span'
            ]
        });

        // Configure Quill to preserve article tags (which Quill allows)
        editor.clipboard.addMatcher('article', function(node, delta) {
            // Preserve article tags and their attributes
            return delta;
        });
        
        // Add matcher for any HTML element to preserve structure
        editor.clipboard.addMatcher('*', function(node, delta) {
            // Preserve all HTML elements and their attributes
            return delta;
        });
        
        // Function to convert div tags to article tags for Quill compatibility
        function convertDivToArticle(html) {
            return html.replace(/<div/g, '<article').replace(/<\/div>/g, '</article>');
        }
        
        // Function to convert article tags back to div tags for display
        function convertArticleToDiv(html) {
            return html.replace(/<article/g, '<div').replace(/<\/article>/g, '</div>');
        }
        
        // Set initial content
        const initialContent = <?php echo json_encode($contentWithoutMetadata, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
        
        if (initialContent && initialContent.trim()) {
            // Check if content contains complex HTML that Quill might strip
            // Detect complex HTML including div tags, grid layouts, custom classes, and complex structures
            const hasComplexHTML = /<div[^>]*style=|<div[^>]*class=|grid|parallax|shortcode|\[.*?\]/i.test(initialContent);
            
            if (hasComplexHTML) {
                // For complex HTML, use a different approach - store in a hidden field and show in code view
                document.getElementById('richEditorContainer').classList.add('hidden');
                document.getElementById('codeEditorContainer').classList.remove('hidden');
                document.getElementById('codeEditor').value = initialContent;
            } else {
                // For simple content, use Quill with article tag conversion
                const quillCompatibleContent = convertDivToArticle(initialContent);
                
                try {
                    editor.clipboard.dangerouslyPasteHTML(quillCompatibleContent);
                } catch (e) {
                    // Fallback to setContents if dangerouslyPasteHTML fails
                    const delta = editor.clipboard.convert(quillCompatibleContent);
                    editor.setContents(delta);
                }
            }
            
            // Ensure Quill editor is visible for simple content
            if (!hasComplexHTML) {
                document.getElementById('richEditorContainer').classList.remove('hidden');
                document.getElementById('codeEditorContainer').classList.add('hidden');
            }
        } else {
            // Try to load from the full content data as fallback
            const fullContent = <?php echo json_encode($contentData ?? '', JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
            if (fullContent && fullContent.trim()) {
                editor.root.innerHTML = fullContent;
            }
        }
        
        // Toggle between rich editor and code editor with content sync
        document.getElementById('toggleMode').addEventListener('click', function() {
            const richEditorContainer = document.getElementById('richEditorContainer');
            const codeEditorContainer = document.getElementById('codeEditorContainer');
            const toggleButton = document.getElementById('toggleMode');
            const modeIndicator = document.getElementById('modeIndicator');
            const codeEditor = document.getElementById('codeEditor');

            if (richEditorContainer.classList.contains('hidden')) {
                // Switching from Code View to Rich Editor
                const htmlContent = codeEditor.value;
                
                // Check if content contains complex HTML that Quill will strip
                // Detect complex HTML including div tags, grid layouts, custom classes, and complex structures
                const hasComplexHTML = /<div[^>]*style=|<div[^>]*class=|grid|parallax|shortcode|\[.*?\]/i.test(htmlContent);
                if (hasComplexHTML) {
                    alert('Complex HTML detected! This content contains complex structures (grids, parallax, shortcodes, or custom styling) that will be stripped by the rich text editor. Staying in code view to preserve your HTML.');
                    return; // Don't switch modes
                }
                
                // For simple content, switch to rich editor
                richEditorContainer.classList.remove('hidden');
                codeEditorContainer.classList.add('hidden');
                modeIndicator.textContent = 'Rich Editor Mode';
                toggleButton.textContent = 'Switch to Code View';
                toggleButton.className = 'px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors';
                
                // Update rich editor with code editor content
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
            
            let contentToSave = '';
            
            if (richEditorContainer.classList.contains('hidden')) {
                // In code view mode, use code editor content
                contentToSave = codeEditor.value;
            } else {
                // In rich editor mode, use Quill content and convert article tags back to div tags
                contentToSave = convertArticleToDiv(editor.root.innerHTML);
            }
            
            // Set the content in the hidden field
            document.getElementById('content').value = contentToSave;
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
