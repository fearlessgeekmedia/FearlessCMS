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
            <div id="editor" style="height: 600px;"></div>
            <input type="hidden" name="content" id="content">
        </div>

        <div class="flex justify-end gap-4">
            <button type="button" onclick="window.location.href='?action=dashboard'" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50">Cancel</button>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Save Changes</button>
        </div>
    </form>
</div>

<!-- Toast UI Editor -->
<link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css" />
<script src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>

<script>
// Make editor globally accessible
let editor;

document.addEventListener('DOMContentLoaded', function() {
    editor = new toastui.Editor({
        el: document.querySelector('#editor'),
        height: '600px',
        initialEditType: 'wysiwyg',
        previewStyle: 'vertical',
        initialValue: <?php echo json_encode($contentWithoutMetadata); ?>,
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

    // Update hidden input before form submission
    document.getElementById('editForm').addEventListener('submit', function() {
        document.getElementById('content').value = editor.getMarkdown();
    });
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
    const editorContent = editor.getMarkdown();
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