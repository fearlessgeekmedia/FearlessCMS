
<div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
            <div class="bg-white shadow rounded-lg p-6 mb-8">
                <h3 class="text-lg font-medium mb-4">Site Settings</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_site_name">
                    <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                    <div>
                        <label class="block mb-1">Site Name</label>
                        <input type="text" name="site_name" value="<?php echo htmlspecialchars($siteName ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded">
                    </div>
                    <div>
                        <label class="block mb-1">Tagline</label>
                        <input type="text" name="site_description" value="<?php echo htmlspecialchars($siteDescription ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded">
                    </div>
                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Update Site Name</button>
                </form>
            </div>
        </div>
        <div>
            <div class="bg-white shadow rounded-lg p-6 mb-8">
                <h3 class="text-lg font-medium mb-4">Quick Stats</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded">
                        <div class="text-sm text-gray-500">Total Pages</div>
                        <div class="text-2xl font-bold"><?php echo $totalPages ?? 0; ?></div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded">
                        <div class="text-sm text-gray-500">Active Plugins</div>
                        <div class="text-2xl font-bold"><?php echo count($activePlugins ?? []); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Cache Status Summary -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Cache Status</h3>
                <?php if (class_exists('CacheManager')): ?>
                    <?php 
                    $cacheManager = new CacheManager();
                    $cacheConfig = $cacheManager->getConfig();
                    $cacheStats = $cacheManager->getStats();
                    $cacheStatus = $cacheManager->getCacheStatus();
                    $cacheSize = $cacheManager->getCacheSize();
                    ?>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Status:</span>
                            <span class="px-2 py-1 text-xs rounded <?php echo $cacheStatus === 'Excellent' ? 'bg-green-100 text-green-800' : ($cacheStatus === 'Good' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'); ?>">
                                <?php echo $cacheStatus; ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Size:</span>
                            <span class="font-medium"><?php echo $cacheSize; ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Enabled:</span>
                            <span class="text-sm"><?php echo ($cacheConfig['enabled'] ?? false) ? 'Yes' : 'No'; ?></span>
                        </div>
                        <div class="pt-2">
                            <a href="?action=manage_cache_settings" class="text-blue-600 hover:text-blue-800 text-sm">Configure Cache Settings →</a>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-gray-500">Cache system not available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full">
        <h3 class="text-lg font-medium mb-4">Confirm Delete</h3>
        <p class="mb-4">Are you sure you want to delete "<span id="deletePageTitle"></span>"?</p>
        <p class="text-sm text-red-600 mb-4">This action cannot be undone.</p>
        <div class="flex justify-end gap-4">
            <button onclick="closeDeleteModal()" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50">Cancel</button>
            <form method="POST" id="deleteForm" class="inline" data-no-ajax="true">
                <input type="hidden" name="action" value="delete_content">
                <input type="hidden" name="path" id="deletePagePath">
                <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Delete</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(path, title) {
    console.log('confirmDelete called with path:', path, 'title:', title);
    document.getElementById('deletePageTitle').textContent = title;
    document.getElementById('deletePagePath').value = path;
    console.log('Set deletePagePath value to:', document.getElementById('deletePagePath').value);
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
}
</script>
