<?php
// Get CMS mode manager
global $cmsModeManager;

// Debug: Log what variables are available in the template
error_log("DEBUG: Template variables - contentData length: " . (isset($contentData) ? strlen($contentData) : 'NOT SET'));
error_log("DEBUG: Template variables - title: " . (isset($title) ? $title : 'NOT SET'));
error_log("DEBUG: Template variables - path: " . (isset($path) ? $path : 'NOT SET'));
error_log("DEBUG: Template variables - contentData preview: " . (isset($contentData) ? substr($contentData, 0, 200) : 'NOT SET'));

// Editor mode from admin/index.php; default to html
$editorMode = $editorMode ?? 'html';
$editorMode = in_array($editorMode, ['html', 'markdown']) ? $editorMode : 'html';

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
$contentFiles = array_merge(glob(CONTENT_DIR . '/*.md'), glob(CONTENT_DIR . '/*.html'));
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
    $extension = pathinfo($file, PATHINFO_EXTENSION);
    if (!$pageTitle) {
        $pageTitle = ucwords(str_replace(['-', '_'], ' ', basename($file, '.' . $extension)));
    }
    $pages[basename($file, '.' . $extension)] = $pageTitle;
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
        <input type="hidden" name="editor_mode" id="editorModeInput" value="<?php echo htmlspecialchars($editorMode); ?>">
        <input type="hidden" name="content" id="content">
        <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>

        <div class="grid grid-cols-3 gap-6">
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700">Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700">URL Slug</label>
                <input type="text" name="new_slug" value="<?php echo htmlspecialchars($path); ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="page-url-slug">
                <p class="text-xs text-gray-500 mt-1">Use lowercase letters, numbers, dashes, and underscores only</p>
            </div>
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700">Parent Page</label>
                <select name="parent" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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

            <!-- Content Type Selector -->
            <div class="mb-4 flex items-center gap-2">
                <span class="text-sm font-medium text-gray-700 mr-2">Content Type:</span>
                <button type="button" id="contentTypeHtml" class="px-4 py-2 text-sm rounded border transition-colors <?php echo $editorMode === 'html' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'; ?>">
                    HTML
                </button>
                <button type="button" id="contentTypeMarkdown" class="px-4 py-2 text-sm rounded border transition-colors <?php echo $editorMode === 'markdown' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'; ?>">
                    Markdown
                </button>
                <span class="text-xs text-gray-500 ml-2" id="contentTypeShortcut">(Ctrl+Shift+M to toggle)</span>
            </div>

            <!-- HTML Editor Area (Quill + Code View) -->
            <div id="htmlEditorArea">
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

            <!-- Markdown Editor Area (ToastUI Editor) -->
            <div id="markdownEditorArea" class="hidden">
                <p class="text-sm text-gray-600 mb-2">Editing in Markdown mode with ToastUI Editor</p>
                <div id="toastuiEditorContainer" style="height: 600px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;"></div>
            </div>
        </div>

        <div class="flex justify-end gap-4">
            <button type="button" onclick="window.location.href='?action=dashboard'" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50">Cancel</button>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Save Changes</button>
        </div>
    </form>
</div>

<!-- ToastUI Editor can upload to ?action=upload_image -->
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

/* Syntax highlighting for ToastUI Markdown editor */
.toastui-editor-contents {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    font-size: 14px;
    line-height: 1.6;
    padding: 1.5rem !important;
    min-height: 550px;
}

.toastui-editor-md-preview .toastui-editor-contents {
    padding: 1.5rem !important;
}

/* ToastUI tab/button styling to match admin */
.toastui-editor-tabs .tab-item {
    color: #475569;
}

.toastui-editor-tabs .tab-item.active {
    color: #3b82f6;
}
</style>

<script>
// Make editor and toastuiEditor globally accessible
let editor;
let toastuiEditor;

document.addEventListener('DOMContentLoaded', function() {
    const editorModeInput = document.getElementById('editorModeInput');
    const htmlEditorArea = document.getElementById('htmlEditorArea');
    const markdownEditorArea = document.getElementById('markdownEditorArea');
    const contentTypeHtmlBtn = document.getElementById('contentTypeHtml');
    const contentTypeMarkdownBtn = document.getElementById('contentTypeMarkdown');
    const initialEditorMode = <?php echo json_encode($editorMode); ?>;

    // Shared initial content (metadata already stripped in PHP)
    const initialContent = <?php echo json_encode($contentWithoutMetadata, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

    function setContentType(mode) {
        editorModeInput.value = mode;

        if (mode === 'markdown') {
            htmlEditorArea.classList.add('hidden');
            markdownEditorArea.classList.remove('hidden');
            contentTypeHtmlBtn.classList.remove('bg-blue-600', 'text-white', 'border-blue-600');
            contentTypeHtmlBtn.classList.add('bg-white', 'text-gray-700', 'border-gray-300');
            contentTypeMarkdownBtn.classList.remove('bg-white', 'text-gray-700', 'border-gray-300');
            contentTypeMarkdownBtn.classList.add('bg-blue-600', 'text-white', 'border-blue-600');

            if (!toastuiEditor) {
                initToastUI();
            } else {
                const currentMarkdown = toastuiEditor.getMarkdown();
                const newContent = currentMarkdown || initialContent || '';
                toastuiEditor.setMarkdown(newContent);
                toastuiEditor.focus();
            }
        } else {
            markdownEditorArea.classList.add('hidden');
            htmlEditorArea.classList.remove('hidden');
            contentTypeMarkdownBtn.classList.remove('bg-blue-600', 'text-white', 'border-blue-600');
            contentTypeMarkdownBtn.classList.add('bg-white', 'text-gray-700', 'border-gray-300');
            contentTypeHtmlBtn.classList.remove('bg-white', 'text-gray-700', 'border-gray-300');
            contentTypeHtmlBtn.classList.add('bg-blue-600', 'text-white', 'border-blue-600');

            // Initialize Quill if it hasn't been initialized yet (e.g. page loaded in markdown mode)
            if (typeof Quill !== 'undefined' && typeof editor === 'undefined') {
                initQuillHTML();
            }

            if (toastuiEditor) {
                const mdContent = toastuiEditor.getMarkdown();
                const codeEditor = document.getElementById('codeEditor');
                codeEditor.value = mdContent;

                // Also update Quill rich editor if available
                if (typeof editor !== 'undefined' && editor) {
                    editor.root.innerHTML = mdContent;
                }
            }
        }
    }

    function initToastUI() {
        if (typeof window.toastui === 'undefined') {
            console.error('ToastUI Editor not loaded');
            return;
        }

        let contentForMarkdown = initialContent || '';

        toastuiEditor = new window.toastui.Editor({
            el: document.querySelector('#toastuiEditorContainer'),
            previewStyle: 'vertical',
            initialEditType: 'markdown',
            height: '600px',
            usageStatistics: false,
            hooks: {
                addImageBlobHook: function(blob, callback) {
                    const formData = new FormData();
                    formData.append('action', 'upload_image');
                    formData.append('image', blob);

                    fetch('?action=upload_image', {
                        method: 'POST',
                        body: formData
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (data.success) {
                            callback(data.url, 'image');
                        } else {
                            alert('Upload failed: ' + (data.error || 'Unknown error'));
                            callback('', '');
                        }
                    })
                    .catch(function(error) {
                        alert('Upload failed: ' + error);
                        callback('', '');
                    });

                    return false;
                }
            }
        });

        toastuiEditor.setMarkdown(contentForMarkdown);
    }

    // Initialize Quill.js
    if (typeof Quill !== 'undefined' && initialEditorMode === 'html') {
        initQuillHTML();
        setContentType('html');
    } else if (typeof Quill !== 'undefined' && initialEditorMode === 'markdown') {
        setContentType('markdown');
    } else if (typeof Quill !== 'undefined') {
        initQuillHTML();
        setContentType('html');
    } else {
        console.error('Quill.js not loaded');
        if (initialEditorMode === 'html') {
            setContentType('html');
        } else {
            setContentType('markdown');
        }
    }

    // Content type button handlers
    contentTypeHtmlBtn.addEventListener('click', function() {
        setContentType('html');
    });

    contentTypeMarkdownBtn.addEventListener('click', function() {
        setContentType('markdown');
    });

    // Keyboard shortcut to toggle content type (Ctrl+Shift+M)
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && (e.key === 'm' || e.key === 'M')) {
            e.preventDefault();
            const currentMode = document.getElementById('editorModeInput').value;
            setContentType(currentMode === 'html' ? 'markdown' : 'html');
        }
    });

    // Update hidden input before form submission (handle both modes)
    document.getElementById('editForm').addEventListener('submit', function() {
        const currentMode = document.getElementById('editorModeInput').value;
        document.getElementById('editorModeInput').value = currentMode;

        let contentToSave = '';

        if (currentMode === 'markdown' && toastuiEditor) {
            contentToSave = toastuiEditor.getMarkdown();
        } else {
            contentToSave = getHTMLContent();
        }

        document.getElementById('content').value = contentToSave;
    });
});

function getHTMLContent() {
    const richEditorContainer = document.getElementById('richEditorContainer');
    const codeEditor = document.getElementById('codeEditor');

    if (richEditorContainer.classList.contains('hidden')) {
        return codeEditor.value;
    }

    if (typeof editor !== 'undefined' && editor) {
        return convertArticleToDiv(editor.root.innerHTML);
    }

    return '';
}

function convertDivToArticle(html) {
    return html.replace(/<div/g, '<article').replace(/<\/div>/g, '</article>');
}

function convertArticleToDiv(html) {
    return html.replace(/<article/g, '<div').replace(/<\/article>/g, '</div>');
}

function initQuillHTML() {
    if (typeof Quill === 'undefined') {
        console.error('Quill.js not loaded');
        document.getElementById('richEditorContainer').innerHTML = '<div class="p-4 text-red-600">Error: Quill.js editor failed to load. Please refresh the page or use Code View.</div>';
        document.getElementById('richEditorContainer').classList.add('hidden');
        document.getElementById('codeEditorContainer').classList.remove('hidden');
        document.getElementById('modeIndicator').textContent = 'Code View Mode (Quill Failed to Load)';
        document.getElementById('toggleMode').style.display = 'none';
        return;
    }

    var toolbarOptions = [
        ['bold', 'italic', 'underline', 'strike'],
        ['blockquote'],
        [{ 'header': 1 }, { 'header': 2 }],
        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
        [{ 'indent': '-1' }, { 'indent': '+1' }],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'align': [] }],
        ['clean'],
        ['link']
    ];

    editor = new Quill('#richEditorContainer', {
        theme: 'snow',
        modules: {
            toolbar: toolbarOptions,
            clipboard: {
                matchVisual: false
            }
        },
        placeholder: 'Start writing your content...',
        readOnly: false
    });

    var imageButton = document.createElement('button');
    imageButton.innerHTML = '<svg viewBox="0 0 18 18"><rect class="ql-stroke" height="10" width="12" x="3" y="4"></rect><circle class="ql-fill" cx="6" cy="6" r="1"></circle><polyline class="ql-even ql-fill" points="5 8,9 4,13 8,13 14,5 14"></polyline></svg>';
    imageButton.type = 'button';
    imageButton.className = 'ql-image';
    imageButton.setAttribute('aria-label', 'Insert image');
    
    imageButton.addEventListener('click', function() {
        var input = document.createElement('input');
        input.setAttribute('type', 'file');
        input.setAttribute('accept', 'image/*');
        input.click();
        
        input.onchange = function() {
            var file = input.files[0];
            if (file) {
                var formData = new FormData();
                formData.append('action', 'upload_image');
                formData.append('image', file);
                
                fetch('?action=upload_image', {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        var range = editor.getSelection();
                        editor.insertEmbed(range.index, 'image', data.url);
                    } else {
                        alert('Upload failed: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(function(error) {
                    alert('Upload failed: ' + error);
                });
            }
        };
    });
    
    var toolbar = editor.getModule('toolbar');
    toolbar.addHandler('image', function() {
        imageButton.click();
    });
    
    var toolbarContainer = document.querySelector('.ql-toolbar');
    if (toolbarContainer) {
        toolbarContainer.appendChild(imageButton);
    }

    editor.clipboard.addMatcher('article', function(node, delta) {
        return delta;
    });
    
    editor.clipboard.addMatcher('*', function(node, delta) {
        return delta;
    });
    
    const initialContent = <?php echo json_encode($contentWithoutMetadata, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    
    function loadContentIntoEditor() {
        if (initialContent && initialContent.trim()) {
            const hasComplexHTML = /<div[^>]*style=|<div[^>]*class=|grid|parallax|shortcode|\[.*?\]|<form|<input|<textarea|<select|<button|<table|<tr|<td|<th|<thead|<tbody|<tfoot|<colgroup|<col|<caption|<fieldset|<legend|<label|<optgroup|<option|<datalist|<output|<progress|<meter|<details|<summary|<dialog|<menu|<menuitem|<slot|<template|<svg|<canvas|<video|<audio|<iframe|<embed|<object|<param|<source|<track|<map|<area|<picture|<figure|<figcaption|<nav|<header|<footer|<main|<section|<article|<aside|<hgroup|<address|<blockquote|<pre|<kbd|<samp|<var|<time|<mark|<ruby|<rt|<rp|<bdi|<bdo|<span[^>]*style=|<span[^>]*class=|<ul|<li/i.test(initialContent);
            
            if (hasComplexHTML) {
                document.getElementById('richEditorContainer').classList.add('hidden');
                document.getElementById('codeEditorContainer').classList.remove('hidden');
                document.getElementById('codeEditor').value = initialContent;
                document.getElementById('modeIndicator').textContent = 'Code View Mode (Complex HTML Detected)';
                document.getElementById('toggleMode').textContent = 'Switch to Rich Editor';
                document.getElementById('toggleMode').className = 'px-3 py-1 text-sm bg-green-500 text-white rounded hover:bg-green-600 transition-colors';
            } else {
                try {
                    editor.root.innerHTML = initialContent;
                    document.getElementById('richEditorContainer').classList.remove('hidden');
                    document.getElementById('codeEditorContainer').classList.add('hidden');
                    document.getElementById('modeIndicator').textContent = 'Rich Editor Mode';
                    document.getElementById('toggleMode').textContent = 'Switch to Code View';
                    document.getElementById('toggleMode').className = 'px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors';
                } catch (e) {
                    document.getElementById('richEditorContainer').classList.add('hidden');
                    document.getElementById('codeEditorContainer').classList.remove('hidden');
                    document.getElementById('codeEditor').value = initialContent;
                }
            }
        } else {
            // No body content found after metadata - leave editor empty
            document.getElementById('richEditorContainer').classList.remove('hidden');
            document.getElementById('codeEditorContainer').classList.add('hidden');
            document.getElementById('modeIndicator').textContent = 'Rich Editor Mode';
            document.getElementById('toggleMode').textContent = 'Switch to Code View';
            document.getElementById('toggleMode').className = 'px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors';
        }
    }
    
    setTimeout(loadContentIntoEditor, 100);
    
    document.getElementById('toggleMode').addEventListener('click', function() {
        const richEditorContainer = document.getElementById('richEditorContainer');
        const codeEditorContainer = document.getElementById('codeEditorContainer');
        const toggleButton = document.getElementById('toggleMode');
        const modeIndicator = document.getElementById('modeIndicator');
        const codeEditor = document.getElementById('codeEditor');

        if (richEditorContainer.classList.contains('hidden')) {
            const htmlContent = codeEditor.value;
            const hasComplexHTML = /<div[^>]*style=|<div[^>]*class=|grid|parallax|shortcode|\[.*?\]|<form|<input|<textarea|<select|<button|<table|<tr|<td|<th|<thead|<tbody|<tfoot|<colgroup|<col|<caption|<fieldset|<legend|<label|<optgroup|<option|<datalist|<output|<progress|<meter|<details|<summary|<dialog|<menu|<menuitem|<slot|<template|<svg|<canvas|<video|<audio|<iframe|<embed|<object|<param|<source|<track|<map|<area|<picture|<figure|<figcaption|<nav|<header|<footer|<main|<section|<article|<aside|<hgroup|<address|<blockquote|<pre|<kbd|<samp|<var|<time|<mark|<ruby|<rt|<rp|<bdi|<bdo|<span[^>]*style=|<span[^>]*class=|<ul|<li/i.test(htmlContent);
            if (hasComplexHTML) {
                alert('Complex HTML detected! This content contains complex structures (grids, parallax, shortcodes, or custom styling) that will be stripped by the rich text editor. Staying in code view to preserve your HTML.');
                return;
            }
            
            richEditorContainer.classList.remove('hidden');
            codeEditorContainer.classList.add('hidden');
            modeIndicator.textContent = 'Rich Editor Mode';
            toggleButton.textContent = 'Switch to Code View';
            toggleButton.className = 'px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors';
            
            editor.root.innerHTML = htmlContent;
            editor.enable();
            
        } else {
            richEditorContainer.classList.add('hidden');
            codeEditorContainer.classList.remove('hidden');
            modeIndicator.textContent = 'Code View Mode';
            toggleButton.textContent = 'Switch to Rich Editor';
            toggleButton.className = 'px-3 py-1 text-sm bg-green-500 text-white rounded hover:bg-green-600 transition-colors';
            
            const htmlContent = editor.root.innerHTML;
            codeEditor.value = htmlContent;
        }
    });

    // Keyboard shortcut for Quill mode switching (Ctrl+Shift+C)
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && e.key === 'C') {
            const htmlEditorAreaEl = document.getElementById('htmlEditorArea');
            if (htmlEditorAreaEl.classList.contains('hidden')) {
                return;
            }
            e.preventDefault();
            document.getElementById('toggleMode').click();
        }
    });

    // Sync content between editors when switching modes
    document.getElementById('codeEditor').addEventListener('input', function() {
        document.getElementById('content').value = this.value;
    });
}

function previewContent() {
    const currentEditorMode = document.getElementById('editorModeInput').value;
    let editorContent = '';

    if (currentEditorMode === 'markdown' && typeof window.toastui !== 'undefined' && toastuiEditor) {
        editorContent = toastuiEditor.getMarkdown();
    } else if (typeof editor !== 'undefined' && editor) {
        editorContent = editor.root.innerHTML;
    } else {
        alert('Editor not initialized');
        return;
    }

    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    formData.append('action', 'preview_content');
    formData.set('content', editorContent);

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
