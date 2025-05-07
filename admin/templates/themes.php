<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($themes as $themeId => $theme): ?>
    <div class="border rounded-lg p-4 <?php echo $themeId === $themeManager->getActiveTheme() ? 'ring-2 ring-green-500' : ''; ?>">
        <h3 class="text-lg font-medium mb-2"><?php echo htmlspecialchars($theme['name'] ?? ucfirst($themeId)); ?></h3>
        <p class="text-sm text-gray-600 mb-4"><?php echo htmlspecialchars($theme['description'] ?? 'A theme for FearlessCMS'); ?></p>
        <div class="text-sm text-gray-500 mb-4">
            <p>Version: <?php echo htmlspecialchars($theme['version'] ?? '1.0'); ?></p>
            <p>Author: <?php echo htmlspecialchars($theme['author'] ?? 'Unknown'); ?></p>
        </div>
        <?php if ($themeId === $themeManager->getActiveTheme()): ?>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Active</span>
        <?php else: ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="activate_theme" />
                <input type="hidden" name="theme" value="<?php echo htmlspecialchars($themeId); ?>" />
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Activate</button>
            </form>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
