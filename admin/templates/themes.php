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
    <?php if ($activeTheme === 'nightfall'): ?>
    <!-- Nightfall Theme Options -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium mb-4">Nightfall Theme Options</h3>
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="save_theme_options">

            <!-- Author Settings -->
            <div class="border-b pb-6">
                <h4 class="text-md font-medium mb-4">Author Settings</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1">Author Name</label>
                        <input type="text" name="author_name" value="<?php echo htmlspecialchars($themeOptions['author_name'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded" required>
                    </div>
                    <div>
                        <label class="block mb-1">Avatar Image Path</label>
                        <input type="text" name="author_avatar" value="<?php echo htmlspecialchars($themeOptions['author_avatar'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded" placeholder="uploads/avatar.jpg">
                        <small class="text-gray-500">Path relative to your site root (e.g., uploads/avatar.jpg)</small>
                    </div>
                    <div>
                        <label class="block mb-1">Avatar Size</label>
                        <select name="avatar_size" class="w-full px-3 py-2 border border-gray-300 rounded">
                            <option value="size-s" <?php echo ($themeOptions['avatar_size'] ?? '') === 'size-s' ? 'selected' : ''; ?>>Small (80px)</option>
                            <option value="size-m" <?php echo ($themeOptions['avatar_size'] ?? '') === 'size-m' ? 'selected' : ''; ?>>Medium (120px)</option>
                            <option value="size-l" <?php echo ($themeOptions['avatar_size'] ?? '') === 'size-l' ? 'selected' : ''; ?>>Large (160px)</option>
                        </select>
                    </div>
                    <div class="flex items-center">
                        <label class="flex items-center">
                            <input type="checkbox" name="avatar_first" <?php echo ($themeOptions['avatar_first'] ?? false) ? 'checked' : ''; ?> class="mr-2">
                            Show avatar before name
                        </label>
                    </div>
                </div>
            </div>

            <!-- Terminal Header -->
            <div class="border-b pb-6">
                <h4 class="text-md font-medium mb-4">Terminal Header</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1">Username</label>
                        <input type="text" name="user" value="<?php echo htmlspecialchars($themeOptions['user'] ?? 'user'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded" placeholder="user">
                    </div>
                    <div>
                        <label class="block mb-1">Hostname</label>
                        <input type="text" name="hostname" value="<?php echo htmlspecialchars($themeOptions['hostname'] ?? 'localhost'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded" placeholder="localhost">
                    </div>
                </div>
                <p class="text-sm text-gray-500 mt-2">This will display as: <code><?php echo htmlspecialchars($themeOptions['user'] ?? 'user'); ?>@<?php echo htmlspecialchars($themeOptions['hostname'] ?? 'localhost'); ?> ~ $</code></p>
            </div>

            <!-- Social Links -->
            <div class="border-b pb-6">
                <h4 class="text-md font-medium mb-4">Social Links</h4>
                <div id="social-links-container">
                    <?php foreach (($themeOptions['social_links'] ?? []) as $index => $social): ?>
                    <div class="social-link-group border rounded p-4 mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block mb-1">Name</label>
                                <input type="text" name="social_name[]" value="<?php echo htmlspecialchars($social['name']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded" placeholder="GitHub">
                            </div>
                            <div>
                                <label class="block mb-1">URL</label>
                                <input type="text" name="social_url[]" value="<?php echo htmlspecialchars($social['url']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded" placeholder="https://github.com/username">
                            </div>
                            <div>
                                <label class="block mb-1">Icon Class</label>
                                <input type="text" name="social_icon[]" value="<?php echo htmlspecialchars($social['icon']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded" placeholder="fab fa-github">
                            </div>
                            <div>
                                <label class="block mb-1">Target</label>
                                <input type="text" name="social_target[]" value="<?php echo htmlspecialchars($social['target']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded" placeholder="_blank">
                            </div>
                        </div>
                        <button type="button" class="remove-social-link mt-2 bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">Remove</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="add-social-link" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Add Social Link</button>
            </div>

            <!-- Footer -->
            <div class="border-b pb-6">
                <h4 class="text-md font-medium mb-4">Footer</h4>
                <div>
                    <label class="block mb-1">Custom Footer HTML</label>
                    <textarea name="footer_html" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded" placeholder="Leave empty for default footer"><?php echo htmlspecialchars($themeOptions['footer_html'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Color Scheme -->
            <div class="pb-6">
                <h4 class="text-md font-medium mb-4">Color Scheme</h4>
                <div>
                    <label class="block mb-1">Primary Color</label>
                    <select name="color_scheme" class="w-full px-3 py-2 border border-gray-300 rounded">
                        <option value="blue" <?php echo ($themeOptions['color_scheme'] ?? '') === 'blue' ? 'selected' : ''; ?>>Blue</option>
                        <option value="orange" <?php echo ($themeOptions['color_scheme'] ?? '') === 'orange' ? 'selected' : ''; ?>>Orange</option>
                        <option value="green" <?php echo ($themeOptions['color_scheme'] ?? '') === 'green' ? 'selected' : ''; ?>>Green</option>
                        <option value="red" <?php echo ($themeOptions['color_scheme'] ?? '') === 'red' ? 'selected' : ''; ?>>Red</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">Save Theme Options</button>
        </form>
    </div>
    <?php elseif (!empty($themeOptionFields)): ?>
    <!-- Generic Theme Options -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium mb-4">Theme Options</h3>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
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
                    <input type="file" name="<?php echo htmlspecialchars($optionKey); ?>" accept="image/*" class="flex-1 px-3 py-2 border border-gray-300 rounded">
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

// Social links management
document.getElementById('add-social-link')?.addEventListener('click', function() {
    const container = document.getElementById('social-links-container');
    const socialDiv = document.createElement('div');
    socialDiv.className = 'social-link-group border rounded p-4 mb-4';
    socialDiv.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block mb-1">Name</label>
                <input type="text" name="social_name[]" class="w-full px-3 py-2 border border-gray-300 rounded" placeholder="GitHub">
            </div>
            <div>
                <label class="block mb-1">URL</label>
                <input type="text" name="social_url[]" class="w-full px-3 py-2 border border-gray-300 rounded" placeholder="https://github.com/username">
            </div>
            <div>
                <label class="block mb-1">Icon Class</label>
                <input type="text" name="social_icon[]" class="w-full px-3 py-2 border border-gray-300 rounded" placeholder="fab fa-github">
            </div>
            <div>
                <label class="block mb-1">Target</label>
                <input type="text" name="social_target[]" class="w-full px-3 py-2 border border-gray-300 rounded" placeholder="_blank">
            </div>
        </div>
        <button type="button" class="remove-social-link mt-2 bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">Remove</button>
    `;
    container.appendChild(socialDiv);
});

// Remove social link
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-social-link')) {
        e.target.closest('.social-link-group').remove();
    }
});

// No JavaScript needed for traditional form submission
</script>
