<?php
if (!defined('PROJECT_ROOT')) {
    die('Direct access not permitted');
}

function forms_admin_list_page() {
    // Get list of forms
    $forms = [];
    if (is_dir(FORMS_DATA_DIR)) {
        $files = glob(FORMS_DATA_DIR . '/*.json');
        foreach ($files as $file) {
            $form = json_decode(file_get_contents($file), true);
            if ($form && isset($form['id'])) {
                $forms[] = $form;
            }
        }
    }
    
    // Start output buffer
    ob_start();
    ?>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold">Forms</h2>
            <div class="flex space-x-4">
                <a href="?action=forms&subpage=settings" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                    </svg>
                    Settings
                </a>
                <a href="?action=forms&subpage=new" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    New Form
                </a>
            </div>
        </div>
        
        <?php if (empty($forms)): ?>
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">No Forms Found</h3>
                    <div class="mt-2 max-w-xl text-sm text-gray-500">
                        <p>Get started by creating your first form.</p>
                    </div>
                    <div class="mt-5">
                        <a href="?action=forms&subpage=new" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            Create Form
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($forms as $form): ?>
                        <li>
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center">
                                            <p class="text-sm font-medium text-blue-600 truncate">
                                                <?php echo htmlspecialchars($form['title']); ?>
                                            </p>
                                            <?php if (!empty($form['description'])): ?>
                                                <p class="ml-2 text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($form['description']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mt-2">
                                            <p class="text-sm text-gray-500">Shortcode:</p>
                                            <code class="text-sm bg-gray-100 px-2 py-1 rounded">[form id="<?php echo htmlspecialchars($form['id']); ?>"]</code>
                                        </div>
                                    </div>
                                    <div class="ml-2 flex-shrink-0 flex">
                                        <a href="?action=forms&subpage=submissions&form_id=<?php echo htmlspecialchars($form['id']); ?>" class="font-medium text-blue-600 hover:text-blue-500 mr-4">
                                            View Submissions
                                        </a>
                                        <a href="?action=forms&subpage=edit&form_id=<?php echo htmlspecialchars($form['id']); ?>" class="font-medium text-blue-600 hover:text-blue-500">
                                            Edit
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
} 