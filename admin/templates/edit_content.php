
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
$contentWithoutMetadata = $contentData;
if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $contentData, $matches)) {
    $metadata = json_decode($matches[1], true);
    if ($metadata && isset($metadata['template'])) {
        $currentTemplate = $metadata['template'];
    }
    // Remove the metadata from the content
    $contentWithoutMetadata = preg_replace('/^<!--\s*json\s*.*?\s*-->\s*/s', '', $contentData);
}
?>

<div class="bg-white shadow rounded-lg p-6">
    <form method="POST" action="" id="content-form" aria-label="Edit content form">
        <input type="hidden" name="action" value="save_content" />
        <input type="hidden" name="path" value="<?php echo htmlspecialchars($path); ?>" />

        <fieldset class="mb-6">
            <legend class="text-lg font-semibold text-gray-900 mb-4">Content Information</legend>
            
            <div class="mb-4">
                <label for="content-title" class="block mb-2 text-sm font-medium text-gray-700">
                    Title <span class="text-red-500" aria-label="required">*</span>
                </label>
                <input 
                    type="text" 
                    id="content-title"
                    name="title" 
                    value="<?php echo htmlspecialchars($title); ?>" 
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                    required
                    aria-describedby="title-help"
                    aria-required="true"
                >
                <div id="title-help" class="mt-1 text-sm text-gray-500">
                    Enter a descriptive title for your content. This will be displayed as the page heading.
                </div>
            </div>

            <div class="mb-4">
                <label for="content-template" class="block mb-2 text-sm font-medium text-gray-700">
                    Template
                </label>
                <select 
                    id="content-template"
                    name="template" 
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    aria-describedby="template-help"
                >
                    <?php foreach ($templates as $template): ?>
                    <option value="<?php echo htmlspecialchars($template); ?>" <?php echo $template === $currentTemplate ? 'selected' : ''; ?>>
                        <?php echo ucfirst(htmlspecialchars($template)); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div id="template-help" class="mt-1 text-sm text-gray-500">
                    Select the template that will be used to display this content. Different templates have different layouts and styling.
                </div>
            </div>
        </fieldset>

        <fieldset class="mb-6">
            <legend class="text-lg font-semibold text-gray-900 mb-4">Content Editor</legend>
            
            <div class="mb-4">
                <label for="content-editor" class="block mb-2 text-sm font-medium text-gray-700">
                    Content <span class="text-red-500" aria-label="required">*</span>
                </label>
                <div class="relative">
                    <textarea 
                        id="content-editor"
                        name="content" 
                        style="width: 100%; height: 600px; font-family: monospace;" 
                        class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        aria-describedby="content-help content-shortcuts"
                        aria-required="true"
                        required
                    ><?php echo $contentWithoutMetadata; ?></textarea>
                    
                    <!-- Keyboard shortcuts help -->
                    <div id="content-shortcuts" class="mt-2 text-sm text-gray-600">
                        <strong>Keyboard shortcuts:</strong> 
                        <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">Ctrl+S</kbd> Save, 
                        <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">Ctrl+Z</kbd> Undo, 
                        <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">Ctrl+Y</kbd> Redo
                    </div>
                </div>
                <div id="content-help" class="mt-1 text-sm text-gray-500">
                    Write your content here. You can use HTML tags, Markdown syntax, or plain text. The content will be processed according to your theme's template.
                </div>
            </div>
        </fieldset>

        <div class="flex justify-between items-center">
            <div class="text-sm text-gray-500">
                <span id="save-status" aria-live="polite"></span>
            </div>
            <div class="flex space-x-3">
                <button 
                    type="submit" 
                    class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                    aria-describedby="save-help"
                >
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Save Content
                    </span>
                </button>
                <a 
                    href="?action=dashboard" 
                    class="text-gray-600 hover:text-gray-800 px-6 py-2 rounded border border-gray-300 hover:bg-gray-50 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
                    aria-describedby="cancel-help"
                >
                    Cancel
                </a>
            </div>
        </div>
        
        <div id="save-help" class="sr-only">Click to save your content changes</div>
        <div id="cancel-help" class="sr-only">Click to cancel editing and return to dashboard</div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('content-form');
    const titleInput = document.getElementById('content-title');
    const templateSelect = document.getElementById('content-template');
    const contentEditor = document.getElementById('content-editor');
    const saveStatus = document.getElementById('save-status');
    
    // Enhanced keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+S to save
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            form.submit();
            announceSaveStatus('Saving content...');
        }
        
        // Ctrl+Z and Ctrl+Y for undo/redo (browser handles these)
        if (e.ctrlKey && (e.key === 'z' || e.key === 'y')) {
            // Let browser handle undo/redo
            setTimeout(() => {
                announceSaveStatus('Content modified');
            }, 100);
        }
    });
    
    // Auto-save indicator
    let autoSaveTimer;
    function setupAutoSave() {
        [titleInput, templateSelect, contentEditor].forEach(element => {
            element.addEventListener('input', function() {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => {
                    announceSaveStatus('Content modified - remember to save');
                }, 1000);
            });
        });
    }
    
    // Announce status to screen readers
    function announceSaveStatus(message) {
        saveStatus.textContent = message;
        
        // Also announce to screen reader if available
        if (typeof announceToScreenReader === 'function') {
            announceToScreenReader(message);
        }
    }
    
    // Form submission handling
    form.addEventListener('submit', function() {
        announceSaveStatus('Saving content...');
        
        // Disable form during submission
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="flex items-center"><svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Saving...</span>';
        }
    });
    
    // Initialize
    setupAutoSave();
    
    // Focus management
    titleInput.focus();
    
    // Announce page load to screen readers
    if (typeof announceToScreenReader === 'function') {
        announceToScreenReader('Content editor loaded. Use Tab to navigate between fields, Ctrl+S to save.');
    }
});
</script>
