<?php
/**
 * Session initialization - must be included before any session starts
 * Simplified version to eliminate potential fatal errors
 * Backward compatible with PHP versions before 5.4.0
 */

// Start output buffering to prevent any accidental output from causing header issues
if (!ob_get_level()) {
    ob_start();
}

// Check if session extension is loaded
if (!extension_loaded('session')) {
    error_log("Warning: PHP session extension not loaded. Session functionality will be disabled.");
    // Define a dummy session array to prevent errors
    if (!isset($_SESSION)) {
        $_SESSION = [];
    }
    return;
}

// Check if session functions are available
if (!function_exists('session_start')) {
    error_log("Warning: session_start() function not available. Session functionality will be disabled.");
    // Define a dummy session array to prevent errors
    if (!isset($_SESSION)) {
        $_SESSION = [];
    }
    return;
}

// Configure session settings BEFORE any session starts
// Check if session_status() function exists (PHP 5.4.0+)
if (function_exists('session_status')) {
    $sessionActive = session_status() === PHP_SESSION_ACTIVE;
} else {
    // Fallback for older PHP versions - check if session is already started
    $sessionActive = isset($_SESSION) || (function_exists('session_id') && session_id() !== '');
}

if (!$sessionActive) {
    // Set session save path with secure permissions
    $sessionDir = dirname(dirname(__FILE__)) . '/sessions';
    if (!is_dir($sessionDir)) {
        mkdir($sessionDir, 0700, true);
    }

    // Ensure session directory has proper permissions
    chmod($sessionDir, 0700);

    // Set session configuration - use dynamic path
    $sessionDir = dirname(dirname(__FILE__)) . '/sessions';
    ini_set('session.save_path', $sessionDir);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
    ini_set('session.cookie_samesite', 'Lax'); // More permissive for development
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes
    ini_set('session.cookie_lifetime', 0); // Session cookie
    ini_set('session.cookie_path', '/');
    ini_set('session.use_strict_mode', 1); // Prevent session fixation
    ini_set('session.hash_function', 'sha256'); // Stronger hash
    ini_set('session.hash_bits_per_character', 6); // More entropy

    // Start session with error suppression
    @session_start();

    // Regenerate session ID periodically for security
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
        if (function_exists('session_regenerate_id') && (!isset($GLOBALS['fcms_redirecting']) || $GLOBALS['fcms_redirecting'] !== true)) {
            session_regenerate_id(true);
        }
        $_SESSION['last_regeneration'] = time();
    }
}

// Function to safely end output buffering when headers need to be sent
function fcms_flush_output() {
    if (ob_get_level()) {
        ob_end_flush();
    }
}

function fcms_redirect($url) {
    $GLOBALS['fcms_redirecting'] = true;
    header("Location: " . $url);
    exit;
}
