<!-- Cache Settings -->
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Cache Management</h2>
        <p class="text-gray-600">Configure and monitor your site's caching system to improve performance.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column - Cache Configuration -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium mb-4">Cache Configuration</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_cache_settings">
                    <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                    
                    <!-- Cache Enable/Disable -->
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                        <label class="block font-medium">Enable Caching</label>
                        <input type="checkbox" name="cache_enabled" value="1" <?php echo ($cache_enabled_checked ?? '') ? 'checked' : ''; ?> class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                    </div>

                    <!-- Cache Duration -->
                    <div>
                        <label class="block mb-2 font-medium">Cache Duration</label>
                        <div class="flex gap-2">
                            <input type="number" name="cache_duration" value="<?php echo $cache_duration ?? 3600; ?>" min="1" class="flex-1 px-3 py-2 border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500">
                            <select name="cache_duration_unit" class="px-3 py-2 border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500">
                                <option value="seconds" <?php echo ($cache_duration_unit_seconds_selected ?? '') ? 'selected' : ''; ?>>Seconds</option>
                                <option value="minutes" <?php echo ($cache_duration_unit_minutes_selected ?? '') ? 'selected' : ''; ?>>Minutes</option>
                                <option value="hours" <?php echo ($cache_duration_unit_hours_selected ?? '') ? 'selected' : ''; ?>>Hours</option>
                                <option value="days" <?php echo ($cache_duration_unit_days_selected ?? '') ? 'selected' : ''; ?>>Days</option>
                            </select>
                        </div>
                    </div>

                    <!-- Cache Options -->
                    <div class="space-y-3">
                        <h4 class="font-medium text-gray-700">What to Cache</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                <label class="block text-sm">Cache Pages</label>
                                <input type="checkbox" name="cache_pages" value="1" <?php echo ($cache_pages_checked ?? '') ? 'checked' : ''; ?> class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                <label class="block text-sm">Cache Assets</label>
                                <input type="checkbox" name="cache_assets" value="1" <?php echo ($cache_assets_checked ?? '') ? 'checked' : ''; ?> class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                <label class="block text-sm">Cache Database Queries</label>
                                <input type="checkbox" name="cache_queries" value="1" <?php echo ($cache_queries_checked ?? '') ? 'checked' : ''; ?> class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                <label class="block text-sm">Enable Compression</label>
                                <input type="checkbox" name="cache_compression" value="1" <?php echo ($cache_compression_checked ?? '') ? 'checked' : ''; ?> class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Cache Storage -->
                    <div>
                        <label class="block mb-2 font-medium">Cache Storage</label>
                        <select name="cache_storage" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500">
                            <option value="file" <?php echo ($cache_storage_file_selected ?? '') ? 'selected' : ''; ?>>File System</option>
                            <option value="memory" <?php echo ($cache_storage_memory_selected ?? '') ? 'selected' : ''; ?>>Memory (APCu/Redis)</option>
                        </select>
                    </div>

                    <!-- Cache Max Size -->
                    <div>
                        <label class="block mb-2 font-medium">Maximum Cache Size</label>
                        <input type="text" name="cache_max_size" value="<?php echo $cache_max_size ?? '100MB'; ?>" placeholder="100MB" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Format: 100MB, 1GB, etc.</p>
                    </div>

                    <button type="submit" class="w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        Update Cache Settings
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Column - Cache Management & Stats -->
        <div class="space-y-6">
            <!-- Cache Status -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Cache Status</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                        <div>
                            <div class="font-medium">Status</div>
                            <div class="text-sm text-gray-500" data-cache-status><?php echo $cacheStatus ?? 'Unknown'; ?></div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-500">Size</div>
                            <div class="font-medium" data-cache-size><?php echo $cacheSize ?? '0 B'; ?></div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-2">
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="clear_cache">
                            <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                            <button type="submit" class="w-full bg-yellow-500 text-white px-3 py-2 rounded text-sm hover:bg-yellow-600 focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition-colors">
                                Clear Cache
                            </button>
                        </form>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="clear_cache_stats">
                            <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                            <button type="submit" class="w-full bg-gray-500 text-white px-3 py-2 rounded text-sm hover:bg-gray-600 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                                Clear Stats
                            </button>
                        </form>
                    </div>
                    
                    <div class="text-xs text-gray-500 text-center">
                        Last cleared: <span data-last-cleared><?php echo $cacheLastCleared ?? 'Never'; ?></span>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Quick Stats</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Total Requests</span>
                        <span class="font-medium"><?php echo $cacheStats['total_requests'] ?? 0; ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Cache Hits</span>
                        <span class="font-medium text-green-600"><?php echo $cacheStats['cache_hits'] ?? 0; ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Cache Misses</span>
                        <span class="font-medium text-red-600"><?php echo $cacheStats['cache_misses'] ?? 0; ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Hit Rate</span>
                        <span class="font-medium text-blue-600"><?php echo ($cacheStats['hit_rate'] ?? 0) . '%'; ?></span>
                    </div>
                </div>
            </div>

            <!-- Help -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="font-medium text-blue-900 mb-2">💡 Cache Tips</h4>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li>• Enable caching for better performance</li>
                    <li>• Set appropriate cache duration</li>
                    <li>• Monitor hit rates regularly</li>
                    <li>• Clear cache after content updates</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Handle cache clearing with real-time updates
document.addEventListener('DOMContentLoaded', function() {
    // Handle cache clear form submission
    const clearCacheForm = document.querySelector('form input[name="action"][value="clear_cache"]')?.closest('form');
    if (clearCacheForm) {
        clearCacheForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            // Disable button and show loading state
            submitButton.disabled = true;
            submitButton.textContent = 'Clearing...';
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Create a temporary div to parse the response
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                
                // Extract updated cache statistics
                const newCacheSize = tempDiv.querySelector('[data-cache-size]')?.textContent || '0 B';
                const newCacheStatus = tempDiv.querySelector('[data-cache-status]')?.textContent || 'Unknown';
                const newLastCleared = tempDiv.querySelector('[data-last-cleared]')?.textContent || 'Never';
                
                // Update the display
                const cacheSizeElement = document.querySelector('[data-cache-size]');
                const cacheStatusElement = document.querySelector('[data-cache-status]');
                const lastClearedElement = document.querySelector('[data-last-cleared]');
                
                if (cacheSizeElement) cacheSizeElement.textContent = newCacheSize;
                if (cacheStatusElement) cacheStatusElement.textContent = newCacheStatus;
                if (lastClearedElement) lastClearedElement.textContent = newLastCleared;
                
                // Show success message
                showToast('Cache cleared successfully!', 'success');
            })
            .catch(error => {
                console.error('Error clearing cache:', error);
                showToast('Error clearing cache', 'error');
            })
            .finally(() => {
                // Re-enable button
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            });
        });
    }
    
    // Handle cache stats clear form submission
    const clearStatsForm = document.querySelector('form input[name="action"][value="clear_cache_stats"]')?.closest('form');
    if (clearStatsForm) {
        clearStatsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            // Disable button and show loading state
            submitButton.disabled = true;
            submitButton.textContent = 'Clearing...';
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Create a temporary div to parse the response
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                
                // Extract updated cache statistics
                const newCacheSize = tempDiv.querySelector('[data-cache-size]')?.textContent || '0 B';
                const newCacheStatus = tempDiv.querySelector('[data-cache-status]')?.textContent || 'Unknown';
                const newLastCleared = tempDiv.querySelector('[data-last-cleared]')?.textContent || 'Never';
                
                // Update the display
                const cacheSizeElement = document.querySelector('[data-cache-size]');
                const cacheStatusElement = document.querySelector('[data-cache-status]');
                const lastClearedElement = document.querySelector('[data-last-cleared]');
                
                if (cacheSizeElement) cacheSizeElement.textContent = newCacheSize;
                if (cacheStatusElement) cacheStatusElement.textContent = newCacheStatus;
                if (lastClearedElement) lastClearedElement.textContent = newLastCleared;
                
                // Show success message
                showToast('Cache statistics cleared successfully!', 'success');
            })
            .catch(error => {
                console.error('Error clearing cache stats:', error);
                showToast('Error clearing cache statistics', 'error');
            })
            .finally(() => {
                // Re-enable button
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            });
        });
    }
    
    // Simple toast notification function
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-50 transition-all duration-300 ${
            type === 'success' ? 'bg-green-500' : 
            type === 'error' ? 'bg-red-500' : 
            'bg-blue-500'
        }`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // Remove toast after 3 seconds
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
});
</script> 