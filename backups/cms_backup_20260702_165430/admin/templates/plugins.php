<?php
// Get the CMS mode manager instance
global $cmsModeManager;

// Get list of installed plugins
$plugins_dir = dirname(dirname(__DIR__)) . '/plugins';
$plugins = [];

// Get list of active plugins
$active_plugins = [];
    $active_plugins_file = PLUGIN_CONFIG;
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

// Check CMS mode restrictions
$canManagePlugins = $cmsModeManager->canManagePlugins();
$canActivatePlugins = $cmsModeManager->canActivatePlugins();
$canDeactivatePlugins = $cmsModeManager->canDeactivatePlugins();
$canDeletePlugins = $cmsModeManager->canDeletePlugins();
$currentMode = $cmsModeManager->getCurrentMode();

// Set page title based on mode
$pageTitle = $canManagePlugins ? 'Plugins' : 'Additional Features';
?>

<div class="space-y-6">
    <!-- CMS Mode Notice -->
    <?php if ($currentMode !== 'full-featured'): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">
                        CMS Mode: <?php echo htmlspecialchars($cmsModeManager->getModeName()); ?>
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p><?php echo htmlspecialchars($cmsModeManager->getModeDescription()); ?></p>
                        <?php if (!$canManagePlugins): ?>
                            <p class="mt-1"><strong>Plugin management is disabled in this mode.</strong></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Store Disabled Error Message -->
    <?php if (isset($_GET['error']) && $_GET['error'] === 'store_disabled'): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Store Access Denied</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p>Plugin store access is disabled in the current CMS mode. You can only manage existing plugins.</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$canManagePlugins): ?>
        <!-- Additional Features (No Plugin Management Mode) -->
        <div class="space-y-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Additional Features</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>The following features are currently active on your site. These features are managed by your hosting provider.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Features Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php 
                $activePlugins = array_filter($plugins, function($plugin) {
                    return $plugin['active'];
                });
                
                if (empty($activePlugins)): ?>
                    <div class="col-span-full text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No Additional Features</h3>
                        <p class="mt-1 text-sm text-gray-500">No additional features are currently active.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($activePlugins as $plugin): ?>
                        <div class="bg-white border rounded-lg overflow-hidden shadow-sm">
                            <?php if (isset($plugin['banners']['low'])): ?>
                                <img src="<?php echo htmlspecialchars($plugin['banners']['low']); ?>" 
                                     alt="<?php echo htmlspecialchars($plugin['name']); ?>" 
                                     class="w-full h-32 object-cover">
                            <?php endif; ?>
                            
                            <div class="p-4">
                                <div class="flex justify-between items-start">
                                    <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($plugin['name']); ?></h3>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                </div>
                                
                                <p class="text-sm text-gray-600 mt-2">
                                    <?php echo htmlspecialchars($plugin['description']); ?>
                                </p>
                                
                                <div class="mt-4 flex justify-between items-center">
                                    <span class="text-sm text-gray-500">v<?php echo htmlspecialchars($plugin['version']); ?></span>
                                    
                                    <?php if (isset($plugin['repository'])): ?>
                                        <a href="<?php echo htmlspecialchars($plugin['repository']); ?>" 
                                           target="_blank" 
                                           class="text-blue-500 hover:text-blue-600 text-sm">
                                            Learn More
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Information Notice -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-gray-800">About Additional Features</h3>
                        <div class="mt-2 text-sm text-gray-600">
                            <p>These features are pre-installed and managed by your hosting provider. You cannot install, activate, deactivate, or remove features in this mode. Contact your hosting provider if you need changes to these features.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Plugin Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($plugins)): ?>
                <div class="col-span-full text-center py-8">
                    <p class="text-gray-500">No plugins installed yet.</p>
                    <?php if ($cmsModeManager->canAccessStore()): ?>
                        <div class="mt-4">
                            <a href="?action=store" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                Browse Plugin Store
                            </a>
                        </div>
                    <?php endif; ?>
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
                                        <?php if ($canDeactivatePlugins): ?>
                                            <button onclick="deactivatePlugin('<?php echo htmlspecialchars($plugin['slug']); ?>')" 
                                                    class="px-3 py-1 rounded bg-yellow-500 text-white hover:bg-yellow-600">
                                                Deactivate
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($canActivatePlugins): ?>
                                            <button onclick="activatePlugin('<?php echo htmlspecialchars($plugin['slug']); ?>')" 
                                                    class="px-3 py-1 rounded bg-green-500 text-white hover:bg-green-600">
                                                Activate
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($canDeletePlugins): ?>
                                            <button onclick="deletePlugin('<?php echo htmlspecialchars($plugin['slug']); ?>')" 
                                                    class="px-3 py-1 rounded bg-red-500 text-white hover:bg-red-600">
                                                Delete
                                            </button>
                                        <?php endif; ?>
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

        <!-- Store Access Notice -->
        <?php if (!$cmsModeManager->canAccessStore() && !empty($plugins)): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Plugin Store Access</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Plugin store access is disabled in the current CMS mode. You can only manage existing plugins.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
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
    console.log('deactivatePlugin called with slug:', slug);
    
    if (!confirm('Are you sure you want to deactivate this plugin?')) {
        console.log('User cancelled deactivation');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'deactivate_plugin');
    formData.append('plugin_slug', slug);
    
    console.log('Sending POST request to:', window.location.href);
    console.log('FormData contents:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response received:', response);
        return response.text(); // Get raw text first
    })
    .then(text => {
        console.log('Raw response text:', text);
        try {
            const result = JSON.parse(text);
            console.log('JSON result:', result);
            if (result.success) {
                alert('Plugin deactivated successfully!');
                window.location.reload(); // Refresh the page
            } else {
                alert('Error deactivating plugin: ' + (result.error || 'Unknown error'));
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Raw text that failed to parse:', text);
            alert('Error parsing server response. Check console for details.');
        }
    })
    .catch(error => {
        console.error('Error deactivating plugin:', error);
        alert('Error deactivating plugin. Please try again.');
    });
}
</script>
