<div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
            <div class="bg-white shadow rounded-lg p-6 mb-8">
                <h3 class="text-lg font-medium mb-4">Site Settings</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_site_name">
                    <div>
                        <label class="block mb-1">Site Name</label>
                        <input type="text" name="site_name" value="<?php echo htmlspecialchars($siteName); ?>" class="w-full px-3 py-2 border border-gray-300 rounded">
                    </div>
                    <div>
                        <label class="block mb-1">Tagline</label>
                        <input type="text" name="site_description" value="<?php echo htmlspecialchars($siteDescription ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded">
                    </div>
                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Update Site Name</button>
                </form>
            </div>
            <div class="bg-white shadow rounded-lg p-6 mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Content Management</h3>
                    <div class="flex gap-4">
                        <a href="?action=new_content" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">New Page</a>
                        <a href="?action=manage_content" class="text-blue-500 hover:text-blue-600">View All</a>
                    </div>
                </div>
                <div class="space-y-4">
                    <?php foreach ($recentContent as $content): ?>
                    <div class="border rounded p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-medium"><?php echo htmlspecialchars($content['title']); ?></h4>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($content['path']); ?></p>
                            </div>
                            <div class="flex gap-2">
                                <a href="?action=edit_content&path=<?php echo urlencode($content['path']); ?>&editor=toast" class="text-blue-500 hover:text-blue-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                    </svg>
                                </a>
                                <a href="/<?php echo htmlspecialchars($content['path']); ?>" target="_blank" class="text-gray-500 hover:text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                                    </svg>
                                </a>
                                <button onclick="confirmDelete('<?php echo htmlspecialchars($content['path']); ?>', '<?php echo htmlspecialchars($content['title']); ?>')" class="text-red-500 hover:text-red-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
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
            <form method="POST" id="deleteForm" class="inline">
                <input type="hidden" name="action" value="delete_content">
                <input type="hidden" name="path" id="deletePagePath">
                <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Delete</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(path, title) {
    document.getElementById('deletePageTitle').textContent = title;
    document.getElementById('deletePagePath').value = path;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
}
</script>
