<?php
if (!defined('PROJECT_ROOT')) {
    die('Direct access not permitted');
}

function forms_submissions_page() {
    $form_id = $_GET['form_id'] ?? null;
    if (!$form_id) {
        header('Location: ?action=forms');
        exit;
    }
    
    // Get form data
    $form_file = FORMS_DATA_DIR . '/' . $form_id . '.json';
    if (!file_exists($form_file)) {
        header('Location: ?action=forms');
        exit;
    }
    $form = json_decode(file_get_contents($form_file), true);
    
    // Get submissions
    $submissions = [];
    if (is_dir(FORMS_SUBMISSIONS_DIR)) {
        foreach (glob(FORMS_SUBMISSIONS_DIR . '/' . $form_id . '_*.json') as $file) {
            $submission = json_decode(file_get_contents($file), true);
            if ($submission) {
                $submissions[] = $submission;
            }
        }
    }
    
    // Sort submissions by timestamp (newest first)
    usort($submissions, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    // Start output buffer
    ob_start();
    ?>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold">Submissions for <?php echo htmlspecialchars($form['title']); ?></h2>
            <a href="?action=forms" class="text-blue-600 hover:text-blue-900">
                Back to Forms
            </a>
        </div>
        
        <?php if (empty($submissions)): ?>
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">No Submissions Found</h3>
                    <div class="mt-2 max-w-xl text-sm text-gray-500">
                        <p>This form hasn't received any submissions yet.</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <?php foreach ($form['fields'] as $field): ?>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <?php echo htmlspecialchars($field['label']); ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($submission['timestamp']); ?>
                                </td>
                                <?php foreach ($form['fields'] as $field): ?>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($submission['fields'][$field['id']] ?? ''); ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
} 