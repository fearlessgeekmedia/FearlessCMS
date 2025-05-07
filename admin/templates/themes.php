
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
