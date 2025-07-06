<?php
if (!defined('PROJECT_ROOT')) {
    die('Direct access not permitted');
}

// Include utilities
require_once FORMS_DIR . '/includes/utilities.php';

function forms_settings_page() {
    // Handle test email
    if (isset($_POST['test_email'])) {
        forms_log("Test email requested");
        
        $settings = [
            'smtp_host' => $_POST['smtp_host'] ?? '',
            'smtp_port' => $_POST['smtp_port'] ?? '25',
            'smtp_username' => $_POST['smtp_username'] ?? '',
            'smtp_password' => $_POST['smtp_password'] ?? '',
            'smtp_encryption' => $_POST['smtp_encryption'] ?? 'none',
            'from_email' => $_POST['from_email'] ?? '',
            'from_name' => $_POST['from_name'] ?? ''
        ];
        
        forms_log("Test email settings: " . print_r($settings, true));
        
        $test_email = $_POST['test_email_address'] ?? '';
        if (empty($test_email)) {
            $error = "Please enter a test email address";
            forms_log("Test email failed: No email address provided");
        } else {
            forms_log("Attempting to send test email to: " . $test_email);
            
            // Test SMTP connection
            forms_log("Testing SMTP connection to {$settings['smtp_host']}:{$settings['smtp_port']}");
            
            $smtp = @fsockopen($settings['smtp_host'], $settings['smtp_port'], $errno, $errstr, 30);
            if (!$smtp) {
                $error = "SMTP Connection failed: $errstr ($errno)";
                forms_log("SMTP Connection failed: $errstr ($errno)");
            } else {
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
                    $error = "EHLO command failed: " . trim($ehlo_response);
                    forms_log($error);
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
                        $error = "STARTTLS command failed: " . trim($starttls_response);
                        forms_log($error);
                        fclose($smtp);
                        return;
                    }
                    
                    // Enable TLS on the existing connection
                    forms_log("Enabling TLS on existing connection");
                    if (!stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                        $error = "Failed to enable TLS encryption";
                        forms_log($error);
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
                        $error = "EHLO after TLS failed: " . trim($ehlo_response);
                        forms_log($error);
                        fclose($smtp);
                        return;
                    }
                } elseif ($settings['smtp_encryption'] === 'ssl') {
                    // For SSL, connect directly with SSL
                    fclose($smtp);
                    $smtp = @fsockopen('ssl://' . $settings['smtp_host'], $settings['smtp_port'], $errno, $errstr, 30);
                    if (!$smtp) {
                        $error = "Failed to establish SSL connection: $errstr ($errno)";
                        forms_log($error);
                        return;
                    }
                    forms_log("SSL connection established");
                    
                    // Read server response
                    $response = fgets($smtp, 515);
                    forms_log("SMTP Server response after SSL: " . trim($response));
                    
                    // Send EHLO
                    fputs($smtp, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
                    
                    // Read multi-line EHLO response after SSL
                    $ehlo_response = '';
                    do {
                        $response = fgets($smtp, 515);
                        $ehlo_response .= $response;
                        forms_log("SMTP EHLO response after SSL: " . trim($response));
                    } while (substr($response, 3, 1) === '-');
                    
                    if (strpos($ehlo_response, '250') === false) {
                        $error = "EHLO after SSL failed: " . trim($ehlo_response);
                        forms_log($error);
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
                        $error = "AUTH command failed: " . trim($response);
                        forms_log($error);
                        fclose($smtp);
                        return;
                    }
                    
                    fputs($smtp, base64_encode($settings['smtp_username']) . "\r\n");
                    $response = fgets($smtp, 515);
                    forms_log("SMTP Username response: " . trim($response));
                    
                    if (strpos($response, '334') === false) {
                        $error = "Username authentication failed: " . trim($response);
                        forms_log($error);
                        fclose($smtp);
                        return;
                    }
                    
                    fputs($smtp, base64_encode($settings['smtp_password']) . "\r\n");
                    $response = fgets($smtp, 515);
                    forms_log("SMTP Password response: " . trim($response));
                    
                    if (strpos($response, '235') === false) {
                        $error = "Password authentication failed: " . trim($response);
                        forms_log($error);
                        fclose($smtp);
                        return;
                    }
                    forms_log("SMTP Authentication successful");
                }
                
                // Send test email
                $subject = "Test Email from Forms Plugin";
                $message = "This is a test email from your Forms plugin configuration.\n\n";
                $message .= "If you received this email, your SMTP settings are working correctly.\n\n";
                $message .= "Settings used:\n";
                $message .= "SMTP Host: {$settings['smtp_host']}\n";
                $message .= "SMTP Port: {$settings['smtp_port']}\n";
                $message .= "Encryption: {$settings['smtp_encryption']}\n";
                
                // Send MAIL FROM
                fputs($smtp, "MAIL FROM:<{$settings['from_email']}>\r\n");
                $response = fgets($smtp, 515);
                forms_log("SMTP MAIL FROM response: " . trim($response));
                
                if (strpos($response, '250') === false) {
                    $error = "MAIL FROM command failed: " . trim($response);
                    forms_log($error);
                    fclose($smtp);
                    return;
                }
                
                // Send RCPT TO
                fputs($smtp, "RCPT TO:<{$test_email}>\r\n");
                $response = fgets($smtp, 515);
                forms_log("SMTP RCPT TO response: " . trim($response));
                
                if (strpos($response, '250') === false) {
                    $error = "RCPT TO command failed: " . trim($response);
                    forms_log($error);
                    fclose($smtp);
                    return;
                }
                
                // Send DATA
                fputs($smtp, "DATA\r\n");
                $response = fgets($smtp, 515);
                forms_log("SMTP DATA response: " . trim($response));
                
                if (strpos($response, '354') === false) {
                    $error = "DATA command failed: " . trim($response);
                    forms_log($error);
                    fclose($smtp);
                    return;
                }
                
                // Send email headers and content
                $headers = "From: " . (!empty($settings['from_name']) ? "{$settings['from_name']} <{$settings['from_email']}>" : $settings['from_email']) . "\r\n";
                $headers .= "Subject: {$subject}\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                
                fputs($smtp, $headers . "\r\n" . $message . "\r\n.\r\n");
                $response = fgets($smtp, 515);
                forms_log("SMTP Message send response: " . trim($response));
                
                if (strpos($response, '250') === false) {
                    $error = "Message send failed: " . trim($response);
                    forms_log($error);
                    fclose($smtp);
                    return;
                }
                
                // Send QUIT
                fputs($smtp, "QUIT\r\n");
                fclose($smtp);
                
                $success = "Test email sent successfully. Check your inbox and the forms.log file for details.";
                forms_log("Test email sent successfully to: " . $test_email);
            }
        }
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['test_email'])) {
        $settings = [
            'smtp_host' => $_POST['smtp_host'] ?? '',
            'smtp_port' => $_POST['smtp_port'] ?? '25',
            'smtp_username' => $_POST['smtp_username'] ?? '',
            'smtp_password' => $_POST['smtp_password'] ?? '',
            'smtp_encryption' => $_POST['smtp_encryption'] ?? 'none',
            'from_email' => $_POST['from_email'] ?? '',
            'from_name' => $_POST['from_name'] ?? '',
            'success_message' => $_POST['success_message'] ?? '',
            'error_message' => $_POST['error_message'] ?? ''
        ];
        
        // Save settings
        $settings_file = FORMS_DATA_DIR . '/settings.json';
        file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT));
        
        // Redirect to prevent form resubmission
        header('Location: ?action=forms&subpage=settings&saved=1');
        exit;
    }
    
    // Load current settings
    $settings_file = FORMS_DATA_DIR . '/settings.json';
    $settings = [];
    if (file_exists($settings_file)) {
        $settings = json_decode(file_get_contents($settings_file), true);
    }
    
    // Start output buffer
    ob_start();
    ?>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold">Form Settings</h2>
            <a href="?action=forms" class="text-blue-600 hover:text-blue-900">
                Back to Forms
            </a>
        </div>
        
        <?php if (isset($_GET['saved'])): ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">
                            Settings saved successfully.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">
                            <?php echo htmlspecialchars($error); ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">
                            <?php echo htmlspecialchars($success); ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-6">
            <div class="bg-white shadow sm:rounded-md p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">SMTP Settings</h3>
                
                <div class="space-y-4">
                    <div>
                        <label for="smtp_host" class="block text-sm font-medium text-gray-700">SMTP Host</label>
                        <input type="text" name="smtp_host" id="smtp_host" 
                               value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               placeholder="smtp.example.com">
                    </div>
                    
                    <div>
                        <label for="smtp_port" class="block text-sm font-medium text-gray-700">SMTP Port</label>
                        <input type="number" name="smtp_port" id="smtp_port" 
                               value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '25'); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="smtp_encryption" class="block text-sm font-medium text-gray-700">Encryption</label>
                        <select name="smtp_encryption" id="smtp_encryption" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="none" <?php echo ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                            <option value="tls" <?php echo ($settings['smtp_encryption'] ?? '') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                            <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="smtp_username" class="block text-sm font-medium text-gray-700">SMTP Username</label>
                        <input type="text" name="smtp_username" id="smtp_username" 
                               value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="smtp_password" class="block text-sm font-medium text-gray-700">SMTP Password</label>
                        <input type="password" name="smtp_password" id="smtp_password" 
                               value="<?php echo htmlspecialchars($settings['smtp_password'] ?? ''); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>
            
            <div class="bg-white shadow sm:rounded-md p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Default Email Settings</h3>
                
                <div class="space-y-4">
                    <div>
                        <label for="from_email" class="block text-sm font-medium text-gray-700">Default From Email</label>
                        <input type="email" name="from_email" id="from_email" 
                               value="<?php echo htmlspecialchars($settings['from_email'] ?? ''); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               placeholder="noreply@example.com">
                    </div>
                    
                    <div>
                        <label for="from_name" class="block text-sm font-medium text-gray-700">Default From Name</label>
                        <input type="text" name="from_name" id="from_name" 
                               value="<?php echo htmlspecialchars($settings['from_name'] ?? ''); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               placeholder="Your Site Name">
                    </div>
                </div>
            </div>
            
            <div class="bg-white shadow sm:rounded-md p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Form Messages</h3>
                
                <div class="space-y-4">
                    <div>
                        <label for="success_message" class="block text-sm font-medium text-gray-700">Success Message</label>
                        <textarea name="success_message" id="success_message" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                  placeholder="Thank you for your submission!"><?php echo htmlspecialchars($settings['success_message'] ?? 'Thank you for your submission!'); ?></textarea>
                        <p class="mt-1 text-sm text-gray-500">This message will be displayed after a successful form submission.</p>
                    </div>
                    
                    <div>
                        <label for="error_message" class="block text-sm font-medium text-gray-700">Error Message</label>
                        <textarea name="error_message" id="error_message" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                  placeholder="There was an error submitting your form. Please try again."><?php echo htmlspecialchars($settings['error_message'] ?? 'There was an error submitting your form. Please try again.'); ?></textarea>
                        <p class="mt-1 text-sm text-gray-500">This message will be displayed if there's an error submitting the form.</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white shadow sm:rounded-md p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Test Email Settings</h3>
                
                <div class="space-y-4">
                    <div>
                        <label for="test_email_address" class="block text-sm font-medium text-gray-700">Test Email Address</label>
                        <input type="email" name="test_email_address" id="test_email_address" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               placeholder="test@example.com">
                    </div>
                    
                    <div>
                        <button type="submit" name="test_email" value="1" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Send Test Email
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
} 