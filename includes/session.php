<?php
/**
 * Session initialization - must be included before any session starts
 */

// Configure session settings BEFORE any session starts
if (session_status() === PHP_SESSION_NONE) {
    // Set session save path
    $sessionDir = dirname(dirname(__FILE__)) . '/sessions';
    if (!is_dir($sessionDir)) {
        mkdir($sessionDir, 0755, true);
    }
    ini_set('session.save_path', $sessionDir);
    
    // Configure session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', 3600); // 1 hour
    ini_set('session.cookie_lifetime', 0); // Session cookie
    ini_set('session.cookie_path', '/'); // Ensure cookie is available for all paths
    
    // Start session
    session_start();
}
?> 