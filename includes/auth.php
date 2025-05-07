<?php
// Authentication functions

function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function fcms_check_permission($username, $capability) {
    // First check if user is logged in
    if (!isLoggedIn()) {
        return false;
    }
    
    // Then check plugin permissions
    if (function_exists('fcms_check_plugin_permission')) {
        return fcms_check_plugin_permission($username, $capability);
    }
    
    // Default to true if no plugin permission system is available
    return true;
} 