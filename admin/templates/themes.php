<?php
// Load theme options
$themeOptionsFile = CONFIG_DIR . '/theme_options.json';
$themeOptions = file_exists($themeOptionsFile) ? json_decode(file_get_contents($themeOptionsFile), true) : [];

// Check if current theme supports logo and hero banner
$activeTheme = $themeManager->getActiveTheme();
$themeDir = PROJECT_ROOT . '/themes/' . $activeTheme;
$pageTemplate = file_exists($themeDir . '/templates/page.html') ? file_get_contents($themeDir . '/templates/page.html') : '';
$homeTemplate = file_exists($themeDir . '/templates/home.html') ? file_get_contents($themeDir . '/templates/home.html') : '';

$supportsLogo = (strpos($pageTemplate, '{{logo}}') !== false) || (strpos($homeTemplate, '{{logo}}') !== false);
$supportsHerobanner = (strpos($pageTemplate, '{{herobanner}}') !== false) || (strpos($homeTemplate, '{{herobanner}}') !== false) ||
                      (strpos($pageTemplate, '{{heroBanner}}') !== false) || (strpos($homeTemplate, '{{heroBanner}}') !== false);
?>

<!-- Theme Management -->
<div class="space-y-8">
    <?php if ($supportsLogo || $supportsHerobanner): ?>
    <!-- Theme Options -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium mb-4">Theme Options</h3>
        <form id="theme-options-form" class="space-y-4">
            <?php if ($supportsLogo): ?>
            <div>
                <label class="block mb-1">Logo</label>
                <div class="flex items-center space-x-4">
                    <?php if (!empty($themeOptions['logo'])): ?>
                    <div class="flex items-center space-x-2">
                        <img src="<?php echo htmlspecialchars($themeOptions['logo']); ?>" alt="Current Logo" class="h-12">
                        <button type="button" onclick="removeImage('logo')" class="text-red-500 hover:text-red-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="logo" accept="image/*" class="flex-1">
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($supportsHerobanner): ?>
            <div>
                <label class="block mb-1">Hero Banner</label>
                <div class="flex items-center space-x-4">
                    <?php if (!empty($themeOptions['herobanner'])): ?>
                    <div class="flex items-center space-x-2">
                        <img src="<?php echo htmlspecialchars($themeOptions['herobanner']); ?>" alt="Current Hero Banner" class="h-24">
                        <button type="button" onclick="removeImage('herobanner')" class="text-red-500 hover:text-red-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="herobanner" accept="image/*" class="flex-1">
                </div>
            </div>
            <?php endif; ?>
            
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Save Theme Options</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Available Themes -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium mb-4">Available Themes</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($themes as $theme): 
                // Check for theme screenshot
                $themeDir = PROJECT_ROOT . '/themes/' . $theme['id'];
                $screenshot = null;
                $screenshotExtensions = ['png', 'jpg', 'gif'];
                foreach ($screenshotExtensions as $ext) {
                    $screenshotPath = $themeDir . '/assets/screenshot.' . $ext;
                    if (file_exists($screenshotPath)) {
                        $screenshot = '/themes/' . $theme['id'] . '/assets/screenshot.' . $ext;
                        break;
                    }
                }
            ?>
            <div class="border rounded-lg p-4 <?php echo $theme['id'] === $themeManager->getActiveTheme() ? 'ring-2 ring-green-500 bg-green-50' : ''; ?>">
                <?php if ($screenshot): ?>
                <div class="mb-4">
                    <img src="<?php echo htmlspecialchars($screenshot); ?>" alt="<?php echo htmlspecialchars($theme['name']); ?> Screenshot" class="w-full h-48 object-cover rounded">
                </div>
                <?php endif; ?>
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
                    <form method="POST" action="/admin?action=manage_themes">
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
function removeImage(type) {
    const formData = new FormData();
    formData.append('action', 'save_theme_options');
    formData.append('remove_' + type, '1');
    
    fetch('?action=manage_themes', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error removing image: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error removing image:', error);
        alert('Failed to remove image. Please try again.');
    });
}

document.getElementById('theme-options-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'save_theme_options');
    const logoFile = this.querySelector('input[name="logo"]')?.files[0];
    const herobannerFile = this.querySelector('input[name="herobanner"]')?.files[0];
    
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
