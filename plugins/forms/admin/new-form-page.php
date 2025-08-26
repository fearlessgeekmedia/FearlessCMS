<?php
if (!defined('PROJECT_ROOT')) {
    die('Direct access not permitted');
}

function forms_new_form_page() {
    $form_id = $_GET['form_id'] ?? null;
    $form = null;
    
    if ($form_id) {
        $form_file = FORMS_DATA_DIR . '/' . $form_id . '.json';
        if (file_exists($form_file)) {
            $form = json_decode(file_get_contents($form_file), true);
        }
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        error_log("Form submission received: " . print_r($_POST, true));
        
        $form_data = [
            'id' => $form_id ?? uniqid('form_'),
            'title' => $_POST['form_title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'email_recipients' => array_filter(array_map('trim', explode(',', $_POST['email_recipients'] ?? ''))),
            'email_from' => $_POST['email_from'] ?? '',
            'fields' => []
        ];
        
        error_log("Processing form data: " . print_r($form_data, true));
        
        // Process fields
        if (isset($_POST['field_label']) && is_array($_POST['field_label'])) {
            foreach ($_POST['field_label'] as $index => $label) {
                if (empty($label)) continue;
                
                $form_data['fields'][] = [
                    'id' => $_POST['field_id'][$index] ?? 'field_' . $index,
                    'label' => $label,
                    'type' => $_POST['field_type'][$index] ?? 'text',
                    'required' => isset($_POST['field_required'][$index]),
                    'options' => $_POST['field_options'][$index] ?? ''
                ];
            }
        }
        
        // Save form
        $form_file = FORMS_DATA_DIR . '/' . $form_data['id'] . '.json';
        $save_result = file_put_contents($form_file, json_encode($form_data, JSON_PRETTY_PRINT));
        
        error_log("Form save result: " . ($save_result !== false ? "success" : "failed"));
        error_log("Form saved to: " . $form_file);
        error_log("Form data saved: " . print_r($form_data, true));
        
        // Redirect to forms list
        header('Location: ?action=forms');
        exit;
    }
    
    // Start output buffer
    ob_start();
    ?>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold"><?php echo $form ? 'Edit Form' : 'New Form'; ?></h2>
            <a href="?action=forms" class="text-blue-600 hover:text-blue-900">
                Back to Forms
            </a>
        </div>
        
        <form method="POST" class="space-y-6">
            <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
            <div class="bg-white shadow sm:rounded-md p-6">
                <div class="space-y-4">
                    <div class="mb-4">
                        <label for="form_title" class="block text-sm font-medium text-gray-700">Form Title</label>
                        <input type="text" name="form_title" id="form_title" value="<?php echo htmlspecialchars($form['title'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"><?php echo htmlspecialchars($form['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="email_recipients" class="block text-sm font-medium text-gray-700">Email Recipients</label>
                        <input type="text" name="email_recipients" id="email_recipients" value="<?php echo htmlspecialchars(is_array($form['email_recipients'] ?? '') ? implode(', ', $form['email_recipients']) : ($form['email_recipients'] ?? '')); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="email1@example.com, email2@example.com">
                        <p class="mt-1 text-sm text-gray-500">Enter email addresses separated by commas</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="email_from" class="block text-sm font-medium text-gray-700">From Email Address</label>
                        <input type="email" name="email_from" id="email_from" value="<?php echo htmlspecialchars($form['email_from'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="noreply@example.com">
                        <p class="mt-1 text-sm text-gray-500">Leave empty to use default noreply@yourdomain.com</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white shadow sm:rounded-md p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Form Fields</h3>
                
                <div id="fields-container" class="space-y-4">
                    <?php if (!empty($form['fields'])): ?>
                        <?php foreach ($form['fields'] as $index => $field): ?>
                            <div class="field-row border rounded-md p-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Field Label</label>
                                        <input type="text" name="field_label[]" required
                                               value="<?php echo htmlspecialchars($field['label']); ?>"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Field Type</label>
                                        <select name="field_type[]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="text" <?php echo $field['type'] === 'text' ? 'selected' : ''; ?>>Text</option>
                                            <option value="textarea" <?php echo $field['type'] === 'textarea' ? 'selected' : ''; ?>>Textarea</option>
                                            <option value="email" <?php echo $field['type'] === 'email' ? 'selected' : ''; ?>>Email</option>
                                            <option value="select" <?php echo $field['type'] === 'select' ? 'selected' : ''; ?>>Select</option>
                                            <option value="checkbox" <?php echo $field['type'] === 'checkbox' ? 'selected' : ''; ?>>Checkbox</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="field_required[]" value="<?php echo $index; ?>"
                                               <?php echo !empty($field['required']) ? 'checked' : ''; ?>
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-600">Required field</span>
                                    </label>
                                </div>
                                <input type="hidden" name="field_id[]" value="<?php echo htmlspecialchars($field['id']); ?>">
                                <input type="hidden" name="field_options[]" value="<?php echo htmlspecialchars($field['options'] ?? ''); ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <button type="button" onclick="addField()" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Add Field
                </button>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <?php echo $form ? 'Update Form' : 'Create Form'; ?>
                </button>
            </div>
        </form>
    </div>
    
    <script>
    function addField() {
        const container = document.getElementById('fields-container');
        const index = container.children.length;
        
        const fieldHtml = `
            <div class="field-row border rounded-md p-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Field Label</label>
                        <input type="text" name="field_label[]" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Field Type</label>
                        <select name="field_type[]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="text">Text</option>
                            <option value="textarea">Textarea</option>
                            <option value="email">Email</option>
                            <option value="select">Select</option>
                            <option value="checkbox">Checkbox</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="field_required[]" value="${index}"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-600">Required field</span>
                    </label>
                </div>
                <input type="hidden" name="field_id[]" value="field_${index}">
                <input type="hidden" name="field_options[]" value="">
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', fieldHtml);
    }
    </script>
    <?php
    return ob_get_clean();
} 