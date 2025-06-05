// Get form data
$form_id = $_POST['form_id'] ?? null;
$form_title = $_POST['form_title'] ?? '';
$email_recipients = $_POST['email_recipients'] ?? '';
$email_from = $_POST['email_from'] ?? '';

// Process email recipients
$email_recipients = array_map('trim', explode(',', $email_recipients));
$email_recipients = array_filter($email_recipients, function($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
});

// Create or update form
$form = [
    'id' => $form_id ?? 'form_' . uniqid(),
    'title' => $form_title,
    'email_recipients' => $email_recipients,
    'email_from' => $email_from,
    'fields' => []
];

// Process fields
$field_types = $_POST['field_type'] ?? [];
$field_labels = $_POST['field_label'] ?? [];
$field_required = $_POST['field_required'] ?? [];
$field_options = $_POST['field_options'] ?? [];

foreach ($field_types as $index => $type) {
    if (empty($field_labels[$index])) continue;
    
    $field = [
        'id' => 'field_' . uniqid(),
        'type' => $type,
        'label' => $field_labels[$index],
        'required' => isset($field_required[$index])
    ];
    
    if ($type === 'select' && !empty($field_options[$index])) {
        $field['options'] = $field_options[$index];
    }
    
    $form['fields'][] = $field;
}

// Save form
$form_file = FORMS_DATA_DIR . '/' . $form['id'] . '.json';
file_put_contents($form_file, json_encode($form, JSON_PRETTY_PRINT));

// Redirect back to forms list
header('Location: ?page=forms');
exit; 