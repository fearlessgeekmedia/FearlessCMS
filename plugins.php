<?php
// Get list of installed plugins
$plugins_dir = dirname(dirname(__DIR__)) . '/plugins';
$plugins = [];

// Get list of active plugins
$active_plugins = [];
$active_plugins_file = dirname(__DIR__) . '/config/plugins.json';
if (file_exists($active_plugins_file)) {
    $active_plugins = json_decode(file_get_contents($active_plugins_file), true);
    if (!is_array($active_plugins)) {
        $active_plugins = [];
    }
}

// Debug information
echo "<!-- Debug: Plugins directory: " . $plugins_dir . " -->\n";
echo "<!-- Debug: Directory exists: " . (is_dir($plugins_dir) ? 'Yes' : 'No') . " -->\n";
echo "<!-- Debug: Active plugins: " . implode(', ', $active_plugins) . " -->\n";

if (is_dir($plugins_dir)) {
    $dirs = scandir($plugins_dir);
    echo "<!-- Debug: Found directories: " . implode(', ', $dirs) . " -->\n";
    
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..' || $dir === '.git') continue;
        
        $plugin_file = $plugins_dir . '/' . $dir . '/plugin.json';
        echo "<!-- Debug: Checking plugin file: " . $plugin_file . " -->\n";
        echo "<!-- Debug: File exists: " . (file_exists($plugin_file) ? 'Yes' : 'No') . " -->\n";
        
        if (file_exists($plugin_file)) {
            $plugin_data = json_decode(file_get_contents($plugin_file), true);
            echo "<!-- Debug: Plugin data for " . $dir . ": " . json_encode($plugin_data) . " -->\n";
            if ($plugin_data) {
                // Use directory name as slug if not specified in plugin.json
                if (!isset($plugin_data['slug'])) {
                    $plugin_data['slug'] = $dir;
                }
                // Check if plugin is active
                $plugin_data['active'] = in_array($dir, $active_plugins);
                $plugins[$plugin_data['slug']] = $plugin_data;
            }
        }
    }
}

echo "<!-- Debug: Total plugins found: " . count($plugins) . " -->\n";
?>

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($plugins)): ?>
            <div class="col-span-full text-center py-8">
                <p class="text-gray-500">No plugins installed yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($plugins as $plugin): ?>
                <div class="bg-white border rounded-lg overflow-hidden">
                    <?php if (isset($plugin['banners']['low'])): ?>
                        <img src="<?php echo htmlspecialchars($plugin['banners']['low']); ?>" 
                             alt="<?php echo htmlspecialchars($plugin['name']); ?>" 
                             class="w-full h-32 object-cover">
                    <?php endif; ?>
                    
                    <div class="p-4">
                        <div class="flex justify-between items-start">
                            <h3 class="text-lg font-medium"><?php echo htmlspecialchars($plugin['name']); ?></h3>
                            <span class="text-sm text-gray-500">v<?php echo htmlspecialchars($plugin['version']); ?></span>
                        </div>
                        
                        <p class="text-sm text-gray-600 mt-2">
                            <?php echo htmlspecialchars($plugin['description']); ?>
                        </p>
                        
                        <div class="mt-4 flex justify-between items-center">
                            <div class="flex gap-2">
                                <?php if ($plugin['active']): ?>
                                    <button class="px-3 py-1 rounded bg-gray-500 text-white">Active</button>
                                    <button onclick="deactivatePlugin('<?php echo htmlspecialchars($plugin['slug']); ?>')" 
                                            class="px-3 py-1 rounded bg-yellow-500 text-white hover:bg-yellow-600">
                                        Deactivate
                                    </button>
                                <?php else: ?>
                                    <button onclick="activatePlugin('<?php echo htmlspecialchars($plugin['slug']); ?>')" 
                                            class="px-3 py-1 rounded bg-green-500 text-white hover:bg-green-600">
                                        Activate
                                    </button>
                                    <button onclick="deletePlugin('<?php echo htmlspecialchars($plugin['slug']); ?>')" 
                                            class="px-3 py-1 rounded bg-red-500 text-white hover:bg-red-600">
                                        Delete
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (isset($plugin['repository'])): ?>
                                <a href="<?php echo htmlspecialchars($plugin['repository']); ?>" 
                                   target="_blank" 
                                   class="text-blue-500 hover:text-blue-600">
                                    View Repository
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function deletePlugin(slug) {
    if (!confirm('Are you sure you want to delete this plugin? This action cannot be undone.')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete_plugin');
    formData.append('plugin_slug', slug);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Plugin deleted successfully!');
            window.location.reload(); // Refresh the page
        } else {
            alert('Error deleting plugin: ' + (result.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error deleting plugin:', error);
        alert('Error deleting plugin. Please try again.');
    });
}

function activatePlugin(slug) {
    const formData = new FormData();
    formData.append('action', 'activate_plugin');
    formData.append('plugin_slug', slug);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Plugin activated successfully!');
            window.location.reload(); // Refresh the page
        } else {
            alert('Error activating plugin: ' + (result.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error activating plugin:', error);
        alert('Error activating plugin. Please try again.');
    });
}

function deactivatePlugin(slug) {
    if (!confirm('Are you sure you want to deactivate this plugin?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'deactivate_plugin');
    formData.append('plugin_slug', slug);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Plugin deactivated successfully!');
            window.location.reload(); // Refresh the page
        } else {
            alert('Error deactivating plugin: ' + (result.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error deactivating plugin:', error);
        alert('Error deactivating plugin. Please try again.');
    });
}
</script>
