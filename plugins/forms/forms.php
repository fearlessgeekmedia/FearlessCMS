<?php
/*
Plugin Name: Forms
Description: Adds form management capabilities to FearlessCMS
Version: 1.0
Author: Fearless Geek
*/

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define constants
define('FORMS_DIR', PLUGIN_DIR . '/forms');
define('FORMS_DATA_DIR', CONTENT_DIR . '/forms');
define('FORMS_SUBMISSIONS_DIR', CONTENT_DIR . '/form_submissions');
define('FORMS_LOG_FILE', CONTENT_DIR . '/forms/forms.log');

// Include utilities
require_once FORMS_DIR . '/includes/utilities.php';

// Initialize plugin
function formsPluginInit() {
    // Create necessary directories
    if (!file_exists(FORMS_DATA_DIR)) {
        mkdir(FORMS_DATA_DIR, 0755, true);
    }
    if (!file_exists(FORMS_SUBMISSIONS_DIR)) {
        mkdir(FORMS_SUBMISSIONS_DIR, 0755, true);
    }
    
    // Create log file if it doesn't exist
    if (!file_exists(FORMS_LOG_FILE)) {
        touch(FORMS_LOG_FILE);
        chmod(FORMS_LOG_FILE, 0666);
    }

    // Register admin section
    fcms_register_admin_section('forms', [
        'label' => 'Forms',
        'menu_order' => 50,
        'parent' => 'plugins',
        'render_callback' => 'forms_admin_page'
    ]);

    // Register hooks
    fcms_add_hook('route', 'forms_handle_submission');
    fcms_add_hook('content', 'forms_process_shortcode');
}

// Load plugin settings
function forms_get_settings() {
    $settings_file = FORMS_DATA_DIR . '/settings.json';
    if (file_exists($settings_file)) {
        return json_decode(file_get_contents($settings_file), true);
    }
    return [];
}

// Handle form submissions
function forms_handle_submission() {
    forms_log("Form submission handler called");
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        forms_log("Not a POST request");
        return;
    }
    
    $form_id = $_GET['form_id'] ?? null;
    if (!$form_id) {
        forms_log("No form ID provided");
        return;
    }
    
    forms_log("Processing form submission for form ID: " . $form_id);
    
    // Load form data
    $form_file = FORMS_DATA_DIR . '/' . $form_id . '.json';
    if (!file_exists($form_file)) {
        forms_log("Form file not found: " . $form_file);
        return;
    }
    
    $form = json_decode(file_get_contents($form_file), true);
    forms_log("Form data loaded: " . print_r($form, true));
    
    // Load settings
    $settings = forms_get_settings();
    forms_log("Plugin settings loaded: " . print_r($settings, true));
    
    // Build email message
    $emailMessage = "New form submission from " . $form['title'] . "\n\n";
    $emailMessage .= "Submitted on: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($form['fields'] as $field) {
        $field_id = $field['id'];
        $field_value = $_POST[$field_id] ?? '';
        $emailMessage .= $field['label'] . ": " . $field_value . "\n";
    }
    
    forms_log("Email message prepared: " . $emailMessage);
    
    // Send email if recipients are configured
    if (!empty($form['email_recipients'])) {
        forms_log("Email recipients found: " . print_r($form['email_recipients'], true));
        
        $subject = "New form submission: " . $form['title'];
        $from_email = $form['email_from'] ?? $settings['from_email'] ?? 'noreply@' . $_SERVER['HTTP_HOST'];
        $from_name = $settings['from_name'] ?? '';
        $reply_to = $_POST['field_1'] ?? 'noreply@' . $_SERVER['HTTP_HOST'];
        
        // Test SMTP connection
        forms_log("Attempting SMTP connection to {$settings['smtp_host']}:{$settings['smtp_port']}");
        
        $smtp = @fsockopen($settings['smtp_host'], $settings['smtp_port'], $errno, $errstr, 30);
        if (!$smtp) {
            forms_log("SMTP Connection failed: $errstr ($errno)");
            return;
        }
        forms_log("SMTP connection established");
        
        // Read server response
        $response = fgets($smtp, 515);
        forms_log("SMTP Server response: " . trim($response));
        
        // Send EHLO first
        fputs($smtp, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
        
        // Read multi-line EHLO response
        $ehlo_response = '';
        do {
            $response = fgets($smtp, 515);
            $ehlo_response .= $response;
            forms_log("SMTP EHLO response line: " . trim($response));
        } while (substr($response, 3, 1) === '-');
        
        if (strpos($ehlo_response, '250') === false) {
            forms_log("EHLO command failed: " . trim($ehlo_response));
            fclose($smtp);
            return;
        }
        
        // If encryption is required
        if ($settings['smtp_encryption'] === 'tls') {
            forms_log("Starting TLS encryption");
            fputs($smtp, "STARTTLS\r\n");
            
            // Read multi-line STARTTLS response
            $starttls_response = '';
            do {
                $response = fgets($smtp, 515);
                $starttls_response .= $response;
                forms_log("SMTP STARTTLS response line: " . trim($response));
            } while (substr($response, 3, 1) === '-');
            
            if (strpos($starttls_response, '220') === false) {
                forms_log("STARTTLS command failed: " . trim($starttls_response));
                fclose($smtp);
                return;
            }
            
            // Enable TLS on the existing connection
            forms_log("Enabling TLS on existing connection");
            if (!stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                forms_log("Failed to enable TLS encryption");
                fclose($smtp);
                return;
            }
            forms_log("TLS encryption enabled successfully");
            
            // Send EHLO again
            fputs($smtp, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
            
            // Read multi-line EHLO response after TLS
            $ehlo_response = '';
            do {
                $response = fgets($smtp, 515);
                $ehlo_response .= $response;
                forms_log("SMTP EHLO response after TLS: " . trim($response));
            } while (substr($response, 3, 1) === '-');
            
            if (strpos($ehlo_response, '250') === false) {
                forms_log("EHLO after TLS failed: " . trim($ehlo_response));
                fclose($smtp);
                return;
            }
        }
        
        // If authentication is required
        if (!empty($settings['smtp_username']) && !empty($settings['smtp_password'])) {
            forms_log("Starting SMTP authentication");
            fputs($smtp, "AUTH LOGIN\r\n");
            $response = fgets($smtp, 515);
            forms_log("SMTP AUTH response: " . trim($response));
            
            if (strpos($response, '334') === false) {
                forms_log("AUTH command failed: " . trim($response));
                fclose($smtp);
                return;
            }
            
            fputs($smtp, base64_encode($settings['smtp_username']) . "\r\n");
            $response = fgets($smtp, 515);
            forms_log("SMTP Username response: " . trim($response));
            
            if (strpos($response, '334') === false) {
                forms_log("Username authentication failed: " . trim($response));
                fclose($smtp);
                return;
            }
            
            fputs($smtp, base64_encode($settings['smtp_password']) . "\r\n");
            $response = fgets($smtp, 515);
            forms_log("SMTP Password response: " . trim($response));
            
            if (strpos($response, '235') === false) {
                forms_log("Password authentication failed: " . trim($response));
                fclose($smtp);
                return;
            }
            forms_log("SMTP Authentication successful");
        }
        
        // Send MAIL FROM
        fputs($smtp, "MAIL FROM:<{$settings['from_email']}>\r\n");
        $response = fgets($smtp, 515);
        forms_log("SMTP MAIL FROM response: " . trim($response));
        
        if (strpos($response, '250') === false) {
            forms_log("MAIL FROM command failed: " . trim($response));
            fclose($smtp);
            return;
        }
        
        // Send RCPT TO for each recipient
        foreach ($form['email_recipients'] as $recipient) {
            fputs($smtp, "RCPT TO:<{$recipient}>\r\n");
            $response = fgets($smtp, 515);
            forms_log("SMTP RCPT TO response for {$recipient}: " . trim($response));
            
            if (strpos($response, '250') === false) {
                forms_log("RCPT TO command failed for {$recipient}: " . trim($response));
                fclose($smtp);
                return;
            }
        }
        
        // Send DATA
        fputs($smtp, "DATA\r\n");
        $response = fgets($smtp, 515);
        forms_log("SMTP DATA response: " . trim($response));
        
        if (strpos($response, '354') === false) {
            forms_log("DATA command failed: " . trim($response));
            fclose($smtp);
            return;
        }
        
        // Send email headers and content
        $headers = "From: " . (!empty($settings['from_name']) ? "{$settings['from_name']} <{$settings['from_email']}>" : $settings['from_email']) . "\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        fputs($smtp, $headers . "\r\n" . $emailMessage . "\r\n.\r\n");
        $response = fgets($smtp, 515);
        forms_log("SMTP Message send response: " . trim($response));
        
        if (strpos($response, '250') === false) {
            forms_log("Message send failed: " . trim($response));
            fclose($smtp);
            return;
        }
        
        // Send QUIT
        fputs($smtp, "QUIT\r\n");
        fclose($smtp);
        forms_log("SMTP connection closed");
        
        // Store success message in session
        $_SESSION['form_success_message'] = $settings['success_message'] ?? 'Thank you for your submission!';
    } else {
        forms_log("No email recipients configured for this form");
        $_SESSION['form_error_message'] = $settings['error_message'] ?? 'There was an error submitting your form. Please try again.';
    }
    
    // Redirect back to the form page
    header('Location: ' . $_SERVER['HTTP_REFERER'] . '?submitted=1');
    exit;
}

// Process shortcodes in content
function forms_process_shortcode($content) {
    return preg_replace_callback('/\[form id="([^"]+)"\]/', function($matches) {
        $form_id = $matches[1];
        $form_file = FORMS_DATA_DIR . '/' . $form_id . '.json';
        
        if (!file_exists($form_file)) {
            return '<div class="alert alert-danger">Form not found</div>';
        }
        
        $form = json_decode(file_get_contents($form_file), true);
        if (!$form) {
            return '<div class="alert alert-danger">Invalid form data</div>';
        }
        
        $output = '<div class="form-container">';
        
        // Display success message if form was submitted
        if (isset($_GET['submitted']) && isset($_SESSION['form_success_message'])) {
            $output .= '<div class="mb-4 rounded-md bg-green-50 p-4">';
            $output .= '<div class="flex">';
            $output .= '<div class="flex-shrink-0">';
            $output .= '<svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">';
            $output .= '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>';
            $output .= '</svg>';
            $output .= '</div>';
            $output .= '<div class="ml-3">';
            $output .= '<p class="text-sm font-medium text-green-800">' . htmlspecialchars($_SESSION['form_success_message']) . '</p>';
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</div>';
            unset($_SESSION['form_success_message']);
        }
        
        // Display error message if there was an error
        if (isset($_SESSION['form_error_message'])) {
            $output .= '<div class="mb-4 rounded-md bg-red-50 p-4">';
            $output .= '<div class="flex">';
            $output .= '<div class="flex-shrink-0">';
            $output .= '<svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">';
            $output .= '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>';
            $output .= '</svg>';
            $output .= '</div>';
            $output .= '<div class="ml-3">';
            $output .= '<p class="text-sm font-medium text-red-800">' . htmlspecialchars($_SESSION['form_error_message']) . '</p>';
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</div>';
            unset($_SESSION['form_error_message']);
        }
        
        $output .= '<form method="POST" action="?action=submit_form&form_id=' . htmlspecialchars($form_id) . '" class="space-y-4">';
        
        foreach ($form['fields'] as $field) {
            $output .= '<div class="form-group">';
            $output .= '<label for="' . htmlspecialchars($field['id']) . '" class="block text-sm font-medium text-gray-700">' . htmlspecialchars($field['label']) . '</label>';
            
            switch ($field['type']) {
                case 'textarea':
                    $output .= '<textarea name="' . htmlspecialchars($field['id']) . '" id="' . htmlspecialchars($field['id']) . '"';
                    if (!empty($field['required'])) {
                        $output .= ' required';
                    }
                    $output .= ' class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>';
                    break;
                    
                case 'select':
                    $output .= '<select name="' . htmlspecialchars($field['id']) . '" id="' . htmlspecialchars($field['id']) . '"';
                    if (!empty($field['required'])) {
                        $output .= ' required';
                    }
                    $output .= ' class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">';
                    $options = explode("\n", $field['options']);
                    foreach ($options as $option) {
                        $option = trim($option);
                        if (!empty($option)) {
                            $output .= '<option value="' . htmlspecialchars($option) . '">' . htmlspecialchars($option) . '</option>';
                        }
                    }
                    $output .= '</select>';
                    break;
                    
                case 'checkbox':
                    $output .= '<input type="checkbox" name="' . htmlspecialchars($field['id']) . '" id="' . htmlspecialchars($field['id']) . '"';
                    if (!empty($field['required'])) {
                        $output .= ' required';
                    }
                    $output .= ' class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">';
                    break;
                    
                default:
                    $output .= '<input type="' . htmlspecialchars($field['type']) . '" name="' . htmlspecialchars($field['id']) . '" id="' . htmlspecialchars($field['id']) . '"';
                    if (!empty($field['required'])) {
                        $output .= ' required';
                    }
                    $output .= ' class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">';
            }
            
            $output .= '</div>';
        }
        
        $output .= '<div class="pt-4">';
        $output .= '<button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">';
        $output .= 'Submit';
        $output .= '</button>';
        $output .= '</div>';
        
        $output .= '</form>';
        $output .= '</div>';
        
        return $output;
    }, $content);
}

// Hook into content processing
add_filter('content', 'forms_process_shortcode');

// Admin page handler
function forms_admin_page() {
    $page = $_GET['subpage'] ?? 'list';
    $content = '';
    
    switch ($page) {
        case 'new':
        case 'edit':
            include FORMS_DIR . '/admin/new-form-page.php';
            $content = forms_new_form_page();
            break;
            
        case 'submissions':
            include FORMS_DIR . '/admin/submissions-page.php';
            $content = forms_submissions_page();
            break;
            
        case 'settings':
            include FORMS_DIR . '/admin/settings-page.php';
            $content = forms_settings_page();
            break;
            
        default:
            include FORMS_DIR . '/admin/admin-page.php';
            $content = forms_admin_list_page();
            break;
    }
    
    echo $content;
}

// Initialize the plugin
formsPluginInit(); 