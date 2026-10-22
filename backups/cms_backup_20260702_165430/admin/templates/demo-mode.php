<?php
/**
 * Demo Mode Management Template
 */

// Check if user has permission to manage demo mode
if (!fcms_check_permission($_SESSION['username'], 'manage_users')) {
    $error = 'You do not have permission to manage demo mode.';
    return;
}

require_once PROJECT_ROOT . '/includes/DemoModeManager.php';
$demoManager = new DemoModeManager();

// Handle demo mode actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'enable_demo_mode') {
        $demoManager->enable();
        $success = 'Demo mode has been enabled. Users can now log in with username: demo, password: demo';
    } elseif ($action === 'disable_demo_mode') {
        $demoManager->disable();
        $success = 'Demo mode has been disabled.';
    }
}

$status = $demoManager->getStatus();
?>

<div class="space-y-6">
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Demo Mode Status</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-medium text-gray-900">Current Status</h4>
                <p class="text-sm text-gray-600 mt-1">
                    <?php if ($status['enabled']): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Enabled
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Disabled
                        </span>
                    <?php endif; ?>
                </p>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-medium text-gray-900">Demo Session</h4>
                <p class="text-sm text-gray-600 mt-1">
                    <?php if ($status['demo_session']): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Active
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            None
                        </span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Demo Mode Configuration</h3>
        
        <div class="space-y-4">
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Demo User Credentials</h4>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-sm text-gray-600">
                        <strong>Username:</strong> demo<br>
                        <strong>Password:</strong> demo
                    </p>
                </div>
            </div>
            
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Session Settings</h4>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-sm text-gray-600">
                        <strong>Session Timeout:</strong> 1 hour<br>
                        <strong>Cleanup Interval:</strong> 24 hours<br>
                        <strong>Max Demo Sessions:</strong> 10
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Demo Mode Management</h3>
        
        <div class="space-y-4">
            <?php if (!$status['enabled']): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h4 class="font-medium text-yellow-800 mb-2">Enable Demo Mode</h4>
                    <p class="text-sm text-yellow-700 mb-3">
                        Demo mode allows users to explore FearlessCMS with temporary credentials. 
                        This creates a safe environment for testing without affecting real data.
                    </p>
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="enable_demo_mode">
                        <?php echo csrf_token_field(); ?>
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            Enable Demo Mode
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h4 class="font-medium text-green-800 mb-2">Demo Mode Active</h4>
                    <p class="text-sm text-green-700 mb-3">
                        Demo mode is currently enabled. Users can log in with username: demo, password: demo
                    </p>
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="disable_demo_mode">
                        <?php echo csrf_token_field(); ?>
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                            Disable Demo Mode
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Demo Mode Information</h3>
        
        <div class="space-y-4">
            <div>
                <h4 class="font-medium text-gray-900 mb-2">What Demo Mode Provides</h4>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>• Temporary demo user account (demo/demo)</li>
                    <li>• Sample content and pages</li>
                    <li>• Full admin access for testing</li>
                    <li>• Automatic session timeout (1 hour)</li>
                    <li>• Isolated demo environment</li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Demo Limitations</h4>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>• Changes are temporary and reset on session timeout</li>
                    <li>• No real data is affected</li>
                    <li>• Demo content is automatically cleaned up</li>
                    <li>• Limited to 10 concurrent demo sessions</li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Security Notes</h4>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>• Demo mode should only be enabled for testing purposes</li>
                    <li>• Disable demo mode in production environments</li>
                    <li>• Monitor demo session activity</li>
                    <li>• Demo sessions are automatically cleaned up</li>
                </ul>
            </div>
        </div>
    </div>
</div>