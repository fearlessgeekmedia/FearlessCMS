<?php
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

// Get available templates
$templates = [];
$activeTheme = $themeManager->getActiveTheme();
$templateDir = PROJECT_ROOT . '/themes/' . $activeTheme . '/templates';

// Get all template files
$templateFiles = glob($templateDir . '/*.html');
foreach ($templateFiles as $template) {
    $templateName = basename($template, '.html');
    if ($templateName !== '404') { // Exclude 404 template
        $templates[] = $templateName;
    }
}

error_log("Available templates: " . print_r($templates, true));
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
            <label class="block mb-1">Parent Page</label>
            <select name="parent" class="w-full border rounded px-3 py-2">
                <option value="">None (Top Level)</option>
                <?php
                // Get all content files for parent selection
                $contentFiles = glob(CONTENT_DIR . '/*.md');
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
                    $pagePath = basename($file, '.md');
                    if ($pagePath !== $path) { // Don't allow self as parent
                        $selected = (isset($metadata['parent']) && $metadata['parent'] === $pagePath) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($pagePath) . '" ' . $selected . '>' . htmlspecialchars($pageTitle) . '</option>';
                    }
                }
                ?>
            </select>
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
            <div id="editor" style="height: 600px;"></div>
            <textarea name="content" id="content" style="display: none;"></textarea>
        </div>

        <div class="flex justify-between">
            <div class="space-x-2">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Save</button>
                <button type="button" id="preview-button" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Preview</button>
            </div>
            <a href="?action=dashboard" class="text-gray-600 hover:text-gray-800">Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editor = new toastui.Editor({
        el: document.querySelector('#editor'),
        height: '600px',
        initialEditType: 'wysiwyg',
        previewStyle: 'vertical',
        initialValue: <?php echo json_encode($contentWithoutMetadata); ?>,
        toolbarItems: [
            ['heading', 'bold', 'italic', 'strike'],
            ['hr', 'quote'],
            ['ul', 'ol', 'task', 'indent', 'outdent'],
            ['table', 'link', 'image'],
            ['code', 'codeblock']
        ]
    });

    // Preview button functionality
    document.getElementById('preview-button').addEventListener('click', function() {
        const markdownContent = editor.getMarkdown();
        const title = document.querySelector('input[name="title"]').value;
        const template = document.querySelector('select[name="template"]').value;
        
        // Create a temporary preview file
        fetch('/admin/preview-handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                content: markdownContent,
                title: title,
                template: template,
                path: '<?php echo $path; ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.open('/_preview/' + data.path.replace('.md', ''), '_blank');
            } else {
                alert('Failed to create preview: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to create preview');
        });
    });

    // Update hidden input before form submission
    document.getElementById('content-form').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        const markdownContent = editor.getMarkdown();
        console.log('Content length:', markdownContent.length);
        console.log('Content preview:', markdownContent.substring(0, 100));
        document.getElementById('content').value = markdownContent;
        // Add a small delay to ensure the value is set before submitting
        setTimeout(() => {
            this.submit();
        }, 100);
    });
});
</script> 