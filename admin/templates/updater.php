<?php
// Get the CMS mode manager instance
global $cmsModeManager;

$page_title = 'System Updates';

// Check if update access is allowed (use plugin management as proxy for admin access)
if (!$cmsModeManager->canManagePlugins()) {
    // Redirect to dashboard with a message
    header('Location: ?action=dashboard&error=updates_disabled');
    exit;
}

// Load update configuration
$config_file = CONFIG_DIR . '/config.json';
$config = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : [];
$update_repo = $config['update_repo_url'] ?? '';
$update_branch = $config['update_branch'] ?? 'main';

// Generate CSRF token for the updater
if (!function_exists('generate_updater_csrf_token')) {
    require_once dirname(__DIR__) . '/updater-handler.php';
}
$csrfToken = generate_updater_csrf_token();
?>

<div class="space-y-6">
    <!-- Update Settings -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Update Configuration</h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="updates">
            <input type="hidden" name="subaction" value="save_settings">
            <input type="hidden" name="updater_csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <div>
                <label for="repo" class="block text-sm font-medium text-gray-700">Repository URL</label>
                <input type="text" id="repo" name="repo" value="<?php echo htmlspecialchars($update_repo); ?>" 
                       placeholder="https://github.com/username/repository.git" 
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
                <p class="mt-1 text-sm text-gray-500">GitHub repository URL for updates</p>
            </div>
            
            <div>
                <label for="branch" class="block text-sm font-medium text-gray-700">Branch</label>
                <input type="text" id="branch" name="branch" value="<?php echo htmlspecialchars($update_branch); ?>" 
                       placeholder="main" 
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
                <p class="mt-1 text-sm text-gray-500">Branch to update from (default: main)</p>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Save Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Perform Update -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Perform Update</h2>
        
        <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Important Notes</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc pl-5 space-y-1">
                            <li>• Always backup your site before updating</li>
                            <li>• Updates will restart the CMS</li>
                            <li>• Core CMS files will be updated using the bash updater script</li>
                            <li>• Custom themes and plugins will be preserved</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="updates">
            <input type="hidden" name="subaction" value="perform_update">
            <input type="hidden" name="updater_csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <div>
                <label for="update_branch" class="block text-sm font-medium text-gray-700">Update Branch</label>
                <input type="text" id="update_branch" name="branch" value="<?php echo htmlspecialchars($update_branch); ?>" 
                       placeholder="main" 
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
            </div>
            
            <div class="space-y-3">
                <div class="flex items-center">
                    <input id="create_backup" name="create_backup" type="checkbox" checked 
                           class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                    <label for="create_backup" class="ml-2 block text-sm text-gray-900">
                        Create backup before updating (recommended)
                    </label>
                </div>
                
                <div class="flex items-center">
                    <input id="dry_run" name="dry_run" type="checkbox" 
                           class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                    <label for="dry_run" class="ml-2 block text-sm text-gray-900">
                        Dry run (show what would be updated without making changes)
                    </label>
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Perform Update
                </button>
            </div>
        </form>
    </div>

    <!-- Version Comparison -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Version Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Current Version -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Current Version</h3>
                <div class="text-2xl font-bold text-gray-900">
                    <?php 
                    if (defined('FCMS_VERSION')) {
                        echo FCMS_VERSION;
                    } else {
                        // Fallback: read from version.php
                        $version_file = dirname(dirname(__DIR__)) . '/version.php';
                        if (file_exists($version_file)) {
                            $content = file_get_contents($version_file);
                            if (preg_match("/define\('APP_VERSION', '([^']+)'/", $content, $matches)) {
                                echo htmlspecialchars($matches[1]);
                            } else {
                                echo 'Unknown';
                            }
                        } else {
                            echo 'Unknown';
                        }
                    }
                    ?>
                </div>
                <p class="text-sm text-gray-500 mt-1">Installed on your system</p>
            </div>
            
            <!-- Available Version -->
            <div class="bg-blue-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-blue-700 mb-2">Available Version</h3>
                <div class="text-2xl font-bold text-blue-900">
                    <?php
                    // Try to get available version from GitHub
                    $available_version = 'Checking...';
                    if (!empty($update_repo)) {
                        try {
                            // Simple check - we'll show a message that the version will be checked during update
                            $available_version = 'Will check during update';
                        } catch (Exception $e) {
                            $available_version = 'Error checking';
                        }
                    } else {
                        $available_version = 'Repository not configured';
                    }
                    echo htmlspecialchars($available_version);
                    ?>
                </div>
                <p class="text-sm text-blue-500 mt-1">Latest from repository</p>
            </div>
        </div>
        
        <!-- Update Status -->
        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
            <p class="text-sm text-yellow-800">
                <strong>Note:</strong> The available version will be checked when you perform the update. 
                This ensures you always get the most current version information.
            </p>
        </div>
    </div>

    <!-- System Information -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">System Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <span class="text-sm font-medium text-gray-500">PHP Version:</span>
                <span class="ml-2 text-sm text-gray-900"><?php echo PHP_VERSION; ?></span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-500">Update Repository:</span>
                <span class="ml-2 text-sm text-gray-900"><?php echo !empty($update_repo) ? htmlspecialchars($update_repo) : 'Not configured'; ?></span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-500">Update Branch:</span>
                <span class="ml-2 text-sm text-gray-900"><?php echo htmlspecialchars($update_branch); ?></span>
            </div>
        </div>
    </div>
</div> 