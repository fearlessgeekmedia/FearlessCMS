<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($plugins as $plugin): ?>
    <div class="border rounded-lg p-4 <?php echo $plugin['active'] ? 'ring-2 ring-green-500' : ''; ?>">
        <h3 class="text-lg font-medium mb-2"><?php echo htmlspecialchars($plugin['name']); ?></h3>
        <p class="text-sm text-gray-600 mb-4"><?php echo htmlspecialchars($plugin['description']); ?></p>
        <div class="text-sm text-gray-500 mb-4">
            <p>Version: <?php echo htmlspecialchars($plugin['version']); ?></p>
            <p>Author: <?php echo htmlspecialchars($plugin['author']); ?></p>
        </div>
        <?php if ($plugin['active']): ?>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Active</span>
        <form method="POST" action="">
            <input type="hidden" name="action" value="toggle_plugin" />
            <input type="hidden" name="plugin_name" value="<?php echo htmlspecialchars($plugin['id']); ?>" />
            <input type="hidden" name="active" value="false" />
            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 mt-2">Deactivate</button>
        </form>
        <?php else: ?>
        <form method="POST" action="">
            <input type="hidden" name="action" value="toggle_plugin" />
            <input type="hidden" name="plugin_name" value="<?php echo htmlspecialchars($plugin['id']); ?>" />
            <input type="hidden" name="active" value="true" />
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Activate</button>
        </form>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
