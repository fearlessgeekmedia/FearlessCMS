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
        <form id="theme-options-form" class="space-y-4">
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
                    <input type="file" name="<?php echo htmlspecialchars($optionKey); ?>" accept="image/*" class="flex-1">
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
            <div class="border rounded-lg p-4 <?php echo $theme['id'] === $themeManager->getActiveTheme() ? 'ring-2 ring-green-500 bg-green-50' : ''; ?>">
                <h3 class="text-lg font-medium mb-2"><?php echo htmlspecialchars($theme['name']); ?></h3>
                <p class="text-sm text-gray-600 mb-4"><?php echo htmlspecialchars($theme['description']); ?></p>
                <div class="text-sm text-gray-500 mb-4">
                    <p>Version: <?php echo htmlspecialchars($theme['version']); ?></p>
                    <p>Author: <?php echo htmlspecialchars($theme['author']); ?></p>
                </div>
                <?php if ($theme['id'] === $themeManager->getActiveTheme()): ?>
                    <div class="flex items-center gap-2 text-green-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="font-medium">Active Theme</span>
                    </div>
                <?php else: ?>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="activate_theme" />
                        <input type="hidden" name="theme" value="<?php echo htmlspecialchars($theme['id']); ?>" />
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 w-full">Activate Theme</button>
                    </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
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

// Generic theme options form (for non-Nightfall themes)
document.getElementById('theme-options-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'save_theme_options');
    <?php foreach ($themeOptionFields as $optionKey => $option):
        if (!is_array($option) || ($option['type'] ?? '') !== 'image') continue;
    ?>
    const <?php echo $optionKey; ?>File = this.querySelector('input[name="<?php echo $optionKey; ?>"]').files[0];
    if (<?php echo $optionKey; ?>File) {
        formData.append('<?php echo $optionKey; ?>', <?php echo $optionKey; ?>File);
    }
    <?php endforeach; ?>
    fetch('?action=manage_themes', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Theme options saved successfully!');
            window.location.reload();
        } else {
            alert('Error saving theme options: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error saving theme options:', error);
        alert('Failed to save theme options. Please try again.');
    });
});
</script>
