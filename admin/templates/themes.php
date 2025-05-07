<?php
// Load theme options
$themeOptionsFile = CONFIG_DIR . '/theme_options.json';
$themeOptions = file_exists($themeOptionsFile) ? json_decode(file_get_contents($themeOptionsFile), true) : [];
?>

<!-- Theme Management -->
<div class="space-y-8">
    <?php if ($themeManager->getActiveTheme() === 'heroic'): ?>
    <!-- Theme Options -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium mb-4">Theme Options</h3>
        <form id="theme-options-form" class="space-y-4">
            <div>
                <label class="block mb-1">Logo</label>
                <div class="flex items-center space-x-4">
                    <?php if (!empty($themeOptions['logo'])): ?>
                    <img src="/<?php echo htmlspecialchars($themeOptions['logo']); ?>" alt="Current Logo" class="h-12">
                    <?php endif; ?>
                    <input type="file" name="logo" accept="image/*" class="flex-1">
                </div>
            </div>
            <div>
                <label class="block mb-1">Hero Banner</label>
                <div class="flex items-center space-x-4">
                    <?php if (!empty($themeOptions['herobanner'])): ?>
                    <img src="/<?php echo htmlspecialchars($themeOptions['herobanner']); ?>" alt="Current Hero Banner" class="h-24">
                    <?php endif; ?>
                    <input type="file" name="herobanner" accept="image/*" class="flex-1">
                </div>
            </div>
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
document.getElementById('theme-options-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'save_theme_options');
    const logoFile = this.querySelector('input[name="logo"]').files[0];
    const herobannerFile = this.querySelector('input[name="herobanner"]').files[0];
    
    if (logoFile) {
        formData.append('logo', logoFile);
    }
    if (herobannerFile) {
        formData.append('herobanner', herobannerFile);
    }
    
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
