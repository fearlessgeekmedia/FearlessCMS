<?php
if (!defined('PROJECT_ROOT')) {
    die('Direct access not permitted');
}

// Helper function for logging
function forms_log($message) {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    error_log($log_message, 3, FORMS_LOG_FILE);
} 