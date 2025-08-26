<?php
/**
 * Session initialization - must be included before any session starts
 * Fixed version that properly configures sessions with fallback handling
 */

// CRITICAL: Disable implicit_flush to allow session configuration
if (ini_get('implicit_flush')) {
    ini_set('implicit_flush', 0);
}

// Set flag to prevent plugin loading during session initialization
$GLOBALS['FCMS_SESSION_INITIALIZING'] = true;

// CRITICAL: Configure sessions BEFORE any output or headers
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

// Check if session is already active
$sessionActive = false;
if (function_exists('session_status')) {
    $sessionActive = session_status() === PHP_SESSION_ACTIVE;
} else {
    // Fallback for older PHP versions
    $sessionActive = isset($_SESSION) || (function_exists('session_id') && session_id() !== '');
}

if (!$sessionActive) {
    // CRITICAL: Set session configuration BEFORE any session starts
    // Use absolute path for session save directory
    $sessionDir = dirname(dirname(__FILE__)) . '/sessions';
    
    // Ensure session directory exists with proper permissions
    if (!is_dir($sessionDir)) {
        mkdir($sessionDir, 0700, true);
    }
    
    // Set proper permissions
    chmod($sessionDir, 0700);
    
    // Check if session save path is already configured
    $currentSavePath = ini_get('session.save_path');
    $sessionSavePath = false; // Initialize variable
    
    if (empty($currentSavePath)) {
        // Only try to set the path if it's not already configured
        // Try to configure session settings - these MUST be set before session_start()
        // Use error suppression to handle cases where ini_set fails
        
        // Method 1: Try ini_set
        $sessionSavePath = @ini_set('session.save_path', $sessionDir);
        if (getenv('FCMS_DEBUG') === 'true') {
            error_log("Session save path set via ini_set: " . ($sessionSavePath !== false ? 'SUCCESS' : 'FAILED'));
        }
        
        // Method 2: If ini_set failed, try putenv
        if ($sessionSavePath === false) {
            $putenvResult = @putenv("session.save_path=" . $sessionDir);
            if (getenv('FCMS_DEBUG') === 'true') {
                error_log("Session save path set via putenv: " . ($putenvResult ? 'SUCCESS' : 'FAILED'));
            }
        }
        
        // Method 3: If both failed, try session_save_path function
        if ($sessionSavePath === false && function_exists('session_save_path')) {
            $sessionSavePath = @session_save_path($sessionDir);
            if (getenv('FCMS_DEBUG') === 'true') {
                error_log("Session save path set via session_save_path: " . ($sessionSavePath !== false ? 'SUCCESS' : 'FAILED'));
            }
        }
        
        // Method 4: Last resort - try to create a .htaccess file in the sessions directory
        if ($sessionSavePath === false) {
            $htaccessFile = $sessionDir . '/.htaccess';
            if (!file_exists($htaccessFile)) {
                $htaccessContent = "php_value session.save_path " . $sessionDir . "\n";
                @file_put_contents($htaccessFile, $htaccessContent);
                if (getenv('FCMS_DEBUG') === 'true') {
                    error_log("Created .htaccess file in sessions directory");
                }
            }
        }
    } else {
        if (getenv('FCMS_DEBUG') === 'true') {
            error_log("Session save path already configured: " . $currentSavePath);
        }
    }
    
    // Set other session settings
    $sessionCookieHttpOnly = @ini_set('session.cookie_httponly', 1);
    $sessionUseOnlyCookies = @ini_set('session.use_only_cookies', 1);
    
    // Determine if we're on HTTPS
    $isHttps = false;
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $isHttps = true;
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        $isHttps = true;
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        $isHttps = true;
    }
    
    // Set secure cookie only if actually on HTTPS
    $sessionCookieSecure = @ini_set('session.cookie_secure', $isHttps ? 1 : 0);
    
    // Development-friendly cookie settings
    $sessionCookieSameSite = @ini_set('session.cookie_samesite', 'Lax');
    $sessionGcMaxLifetime = @ini_set('session.gc_maxlifetime', 1800); // 30 minutes
    $sessionCookieLifetime = @ini_set('session.cookie_lifetime', 0); // Session cookie
    $sessionCookiePath = @ini_set('session.cookie_path', '/');
    $sessionUseStrictMode = @ini_set('session.use_strict_mode', 0); // Disabled for development
    $sessionHashFunction = @ini_set('session.hash_function', 'sha1');
    $sessionHashBitsPerCharacter = @ini_set('session.hash_bits_per_character', 4);
    
    // Ensure no domain restriction
    $sessionCookieDomain = @ini_set('session.cookie_domain', '');
    
    // Debug logging
    if (getenv('FCMS_DEBUG') === 'true') {
        error_log("Session configuration attempt - Save path: " . ($sessionSavePath !== false ? 'SUCCESS' : 'FAILED'));
        error_log("Session configuration attempt - Cookie secure: " . ($sessionCookieSecure !== false ? 'SUCCESS' : 'FAILED'));
        error_log("Session configuration attempt - Cookie path: " . ($sessionCookiePath !== false ? 'SUCCESS' : 'FAILED'));
        error_log("Final session save path: " . ini_get('session.save_path'));
    }
    
    // Start the session - use error suppression to handle any remaining issues
    $sessionStarted = @session_start();
    
    if ($sessionStarted) {
        // Debug: Log successful session start
        if (getenv('FCMS_DEBUG') === 'true') {
            error_log("Session started successfully - ID: " . session_id());
            error_log("Session save path: " . ini_get('session.save_path'));
        }
        
        // Initialize session data if needed
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        }
        
        // Regenerate session ID periodically for security
        if (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            if (function_exists('session_regenerate_id')) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
        
        // Clear the session initialization flag to allow plugins to load
        $GLOBALS['FCMS_SESSION_INITIALIZING'] = false;
    } else {
        error_log("Failed to start session - this may cause authentication issues");
        
        // Fallback: create a dummy session array to prevent fatal errors
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
        
        // Clear the session initialization flag even if session failed
        $GLOBALS['FCMS_SESSION_INITIALIZING'] = false;
    }
}

// Start output buffering AFTER session configuration
if (!ob_get_level()) {
    ob_start();
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
