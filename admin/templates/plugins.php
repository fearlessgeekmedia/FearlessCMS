<?php
error_log("Plugins template - Current session: " . print_r($_SESSION, true));
?>
<div class="space-y-6">
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Available Plugins</h3>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($plugins as $plugin): ?>
            <div class="border rounded-lg p-4">
                <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($plugin['name']); ?></h4>
                <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($plugin['description']); ?></p>
                <div class="mt-2 text-sm text-gray-500">
            <p>Version: <?php echo htmlspecialchars($plugin['version']); ?></p>
            <p>Author: <?php echo htmlspecialchars($plugin['author']); ?></p>
                </div>
                <button onclick="togglePlugin('<?php echo htmlspecialchars($plugin['id']); ?>')" 
                        class="mt-4 px-4 py-2 rounded-md text-sm font-medium <?php echo $plugin['active'] ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-green-600 hover:bg-green-700 text-white'; ?>">
                    <?php echo $plugin['active'] ? 'Deactivate' : 'Activate'; ?>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function togglePlugin(pluginId) {
    const formData = new FormData();
    formData.append('action', 'toggle_plugin');
    formData.append('plugin_id', pluginId);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Failed to update plugin');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the plugin');
    });
}
</script>
