<?php
/**
 * Session initialization - must be included before any session starts
 * Simplified version to eliminate potential fatal errors
 */

// Start output buffering to prevent any accidental output from causing header issues
if (!ob_get_level()) {
    ob_start();
}

// Configure session settings BEFORE any session starts
if (session_status() === PHP_SESSION_NONE) {
    // Set session save path with secure permissions
    $sessionDir = dirname(dirname(__FILE__)) . '/sessions';
    if (!is_dir($sessionDir)) {
        mkdir($sessionDir, 0700, true);
    }

    // Ensure session directory has proper permissions
    chmod($sessionDir, 0700);

    // Only set session configuration if headers haven't been sent
    if (!headers_sent()) {
        ini_set('session.save_path', $sessionDir);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', 1800); // 30 minutes
        ini_set('session.cookie_lifetime', 0); // Session cookie
        ini_set('session.cookie_path', '/');
        ini_set('session.use_strict_mode', 1); // Prevent session fixation
        ini_set('session.hash_function', 'sha256'); // Stronger hash
        ini_set('session.hash_bits_per_character', 6); // More entropy
    }

    // Start session with error suppression
    @session_start();

    // Regenerate session ID periodically for security
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Function to safely end output buffering when headers need to be sent
function fcms_flush_output() {
    if (ob_get_level()) {
        ob_end_flush();
    }
}
