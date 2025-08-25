<?php
// Load theme options
$themeOptionsFile = CONFIG_DIR . '/theme_options.json';
$themeOptions = file_exists($themeOptionsFile) ? json_decode(file_get_contents($themeOptionsFile), true) : [];

// Load active theme config
$activeTheme = $themeManager->getActiveTheme();
$activeThemeConfigFile = THEMES_DIR . "/$activeTheme/config.json";
$activeThemeConfig = file_exists($activeThemeConfigFile) ? json_decode(file_get_contents($activeThemeConfigFile), true) : [];
$themeOptionFields = isset($activeThemeConfig['options']) ? $activeThemeConfig['options'] : [];

$themes = $themeManager->getThemes();
?>

<!-- Theme Management -->
<div class="space-y-8">
    <?php if (isset($_GET['success'])): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        ✓ Theme options saved successfully!
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        ✗ Error: <?php echo htmlspecialchars($_GET['error']); ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($themeOptionFields)): ?>
    <!-- Generic Theme Options -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium mb-4">Theme Options</h3>
        <form method="POST" enctype="multipart/form-data" class="space-y-4" id="themeOptionsForm">
            <input type="hidden" name="action" value="save_theme_options">
            <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
            <?php foreach ($themeOptionFields as $optionKey => $option):
                if (!is_array($option) || ($option['type'] ?? '') !== 'image') continue;
                $label = $option['label'] ?? ucfirst($optionKey);
            ?>
            <div>
                <label class="block mb-1"><?php echo htmlspecialchars($label); ?></label>
                <div class="flex items-center space-x-4">
                    <?php if (!empty($themeOptions[$optionKey])): ?>
                    <img src="/<?php echo htmlspecialchars($themeOptions[$optionKey]); ?>" alt="Current <?php echo htmlspecialchars($label); ?>" class="h-12">
                    <?php endif; ?>
                    <input type="file" name="<?php echo htmlspecialchars($optionKey); ?>" accept="image/*" class="flex-1 px-3 py-2 border border-gray-300 rounded" id="file_<?php echo htmlspecialchars($optionKey); ?>">
                </div>
            </div>
            <?php endforeach; ?>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Save Theme Options</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Available Themes -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium mb-4">Available Themes</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($themes as $theme): ?>
            <div class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow <?php echo $theme['id'] === $themeManager->getActiveTheme() ? 'ring-2 ring-green-500' : ''; ?>">
                <!-- Thumbnail Section -->
                <?php if (!empty($theme['thumbnail'])): ?>
                <div class="aspect-video bg-gray-100 overflow-hidden cursor-pointer" onmouseover="openThumbnailModal('<?php echo htmlspecialchars($theme['thumbnail']); ?>', '<?php echo htmlspecialchars($theme['name']); ?>')" onmouseout="closeThumbnailModal()">
                    <img src="/<?php echo htmlspecialchars($theme['thumbnail']); ?>"
                         alt="<?php echo htmlspecialchars($theme['name']); ?> preview"
                         class="w-full h-full object-cover hover:scale-105 transition-transform duration-200">
                </div>
                <?php else: ?>
                <div class="aspect-video bg-gray-100 flex items-center justify-center">
                    <div class="text-center text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-sm">No Preview</p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Theme Info Section -->
                <div class="p-4 <?php echo $theme['id'] === $themeManager->getActiveTheme() ? 'bg-green-50' : ''; ?>">
                    <h3 class="text-lg font-medium mb-2"><?php echo htmlspecialchars($theme['name']); ?></h3>
                    <p class="text-sm text-gray-600 mb-4"><?php echo htmlspecialchars($theme['description']); ?></p>
                    <div class="text-sm text-gray-500 mb-4">
                        <p>Version: <?php echo htmlspecialchars($theme['version']); ?></p>
                        <p>Author: <?php echo htmlspecialchars($theme['author']); ?></p>
                    </div>

                    <!-- Status/Action Section -->
                    <?php if ($theme['id'] === $themeManager->getActiveTheme()): ?>
                        <div class="flex items-center gap-2 text-green-700 bg-green-100 px-3 py-2 rounded">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="font-medium">Active Theme</span>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="activate_theme" />
                            <input type="hidden" name="theme" value="<?php echo htmlspecialchars($theme['id']); ?>" />
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 w-full transition-colors">Activate Theme</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Thumbnail Modal -->
<div id="thumbnailModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg max-w-4xl max-h-[90vh] overflow-hidden relative">
        <button onclick="closeThumbnailModal()" class="absolute top-4 right-4 bg-black bg-opacity-50 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-opacity-70 z-10">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        <img id="modalThumbnail" src="" alt="" class="w-full h-auto max-h-[90vh] object-contain">
        <div id="modalCaption" class="p-4 bg-gray-50 border-t">
            <h4 class="font-medium text-gray-900"></h4>
        </div>
    </div>
</div>

<script>
// Basic debugging test
console.log('Themes.php JavaScript loaded');

// Thumbnail modal functions
let closeTimer;

function openThumbnailModal(thumbnailPath, themeName) {
    clearTimeout(closeTimer);
    const modal = document.getElementById('thumbnailModal');
    const modalThumbnail = document.getElementById('modalThumbnail');
    const modalCaption = document.getElementById('modalCaption');

    modalThumbnail.src = '/' + thumbnailPath;
    modalThumbnail.alt = themeName + ' preview';
    modalCaption.querySelector('h4').textContent = themeName + ' Theme Preview';

    modal.classList.remove('hidden');
}

function closeThumbnailModal() {
    closeTimer = setTimeout(() => {
        const modal = document.getElementById('thumbnailModal');
        modal.classList.add('hidden');
    }, 300);
}

// Close modal when clicking outside or pressing Escape
document.getElementById('thumbnailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeThumbnailModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeThumbnailModal();
    }
});

// No JavaScript needed for traditional form submission

// Add debugging for theme options form
document.getElementById('themeOptionsForm')?.addEventListener('submit', function(e) {
    console.log('Form submission started');
    console.log('Form action:', this.action);
    console.log('Form method:', this.method);
    console.log('Form enctype:', this.enctype);
    
    // Log all form data
    const formData = new FormData(this);
    console.log('Form data entries:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ':', value);
    }
    
    // Log file inputs specifically
    const fileInputs = this.querySelectorAll('input[type="file"]');
    console.log('File inputs found:', fileInputs.length);
    fileInputs.forEach((input, index) => {
        console.log(`File input ${index}:`, {
            name: input.name,
            files: input.files,
            filesLength: input.files.length,
            firstFile: input.files[0]
        });
    });
    
    // Check if any files are selected
    let hasFiles = false;
    fileInputs.forEach(input => {
        if (input.files && input.files.length > 0) {
            hasFiles = true;
            console.log('File selected:', input.files[0].name, 'Size:', input.files[0].size);
        }
    });
    
    if (!hasFiles) {
        console.log('WARNING: No files selected for upload');
    }
    
    console.log('Form submission continuing...');
});
</script>
