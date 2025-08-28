<?php
// Authentication functions

// Check if session extension is loaded
if (!extension_loaded('session') || !function_exists('session_start')) {
    error_log("Warning: Session extension not loaded. Authentication functionality will be limited.");
    // Define a dummy session array to prevent errors
    if (!isset($_SESSION)) {
        $_SESSION = [];
    }
}

// CSRF Protection functions
// Development mode fallback for when sessions fail
function is_development_mode() {
    return getenv('FCMS_DEBUG') === 'true' || getenv('FCMS_DEVELOPMENT') === 'true';
}

function get_development_csrf_token() {
    // In development mode, if sessions fail, use a simple token based on time
    if (is_development_mode() && (!isset($_SESSION) || empty($_SESSION))) {
        // Generate a simple token for development
        $time = floor(time() / 300); // Token changes every 5 minutes
        $secret = 'dev_secret_key_' . (getenv('FCMS_DEV_SECRET') ?: 'default');
        return hash('sha256', $time . $secret);
    }
    return null;
}

function validate_development_csrf_token($token) {
    if (is_development_mode() && (!isset($_SESSION) || empty($_SESSION))) {
        $expected = get_development_csrf_token();
        return $token === $expected;
    }
    return false;
}

// Enhanced CSRF token generation with development fallback
function generate_csrf_token() {
    // Try normal session-based token first
    if (isset($_SESSION) && !empty($_SESSION)) {
        if (!isset($_SESSION['csrf_token'])) {
            // Use random_bytes() if available (PHP 7.0+), otherwise fallback to openssl_random_pseudo_bytes()
            if (function_exists('random_bytes')) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } elseif (function_exists('openssl_random_pseudo_bytes')) {
                $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
            } else {
                // Last resort fallback (less secure)
                $_SESSION['csrf_token'] = bin2hex(md5(uniqid(mt_rand(), true)));
            }
        }
        return $_SESSION['csrf_token'];
    }
    
    // Fallback to development mode token
    if (is_development_mode()) {
        return get_development_csrf_token();
    }
    
    // Last resort: generate a temporary token
    return hash('sha256', uniqid(mt_rand(), true));
}

// Enhanced CSRF token validation with development fallback
function validate_csrf_token() {
    error_log('CSRF Validation - Session token: ' . ($_SESSION['csrf_token'] ?? 'not set'));
    error_log('CSRF Validation - POST token: ' . ($_POST['csrf_token'] ?? 'not set'));
    error_log('CSRF Validation - POST data: ' . print_r($_POST, true));
    error_log('CSRF Validation - Session ID: ' . (function_exists('session_id') ? session_id() : 'function_not_available'));
    error_log('CSRF Validation - Session status: ' . (function_exists('session_status') ? session_status() : 'function_not_available'));
    
    // Try normal session-based validation first
    if (isset($_POST['csrf_token']) && isset($_SESSION['csrf_token'])) {
        $isValid = $_SESSION['csrf_token'] === $_POST['csrf_token'];
        error_log('CSRF Validation - Token comparison result: ' . ($isValid ? 'MATCH' : 'MISMATCH'));
        error_log('CSRF Validation - Session token length: ' . strlen($_SESSION['csrf_token']));
        error_log('CSRF Validation - POST token length: ' . strlen($_POST['csrf_token']));
        return $isValid;
    }
    
    // Fallback to development mode validation
    if (is_development_mode() && isset($_POST['csrf_token'])) {
        $devValid = validate_development_csrf_token($_POST['csrf_token']);
        error_log('CSRF Validation - Development mode validation: ' . ($devValid ? 'PASS' : 'FAIL'));
        return $devValid;
    }
    
    error_log('CSRF Validation - Missing token: POST=' . (isset($_POST['csrf_token']) ? 'yes' : 'no') . ', SESSION=' . (isset($_SESSION['csrf_token']) ? 'yes' : 'no'));
    return false;
}

function csrf_token_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generate_csrf_token()) . '">';
}

// Input validation and sanitization functions
function validate_username($username) {
    // Username must be 3-50 characters, alphanumeric plus underscore and dash
    if (!preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $username)) {
        return false;
    }
    return true;
}

function validate_password($password) {
    // Password must be at least 8 characters with at least one letter and one number
    if (strlen($password) < 8) {
        return false;
    }
    if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return false;
    }
    return true;
}

function sanitize_input($input, $type = 'string') {
    $input = trim($input);
    switch ($type) {
        case 'username':
            return preg_replace('/[^a-zA-Z0-9_-]/', '', $input);
        case 'filename':
            return preg_replace('/[^a-zA-Z0-9_.-]/', '', $input);
        case 'path':
            return preg_replace('/[^a-zA-Z0-9_.\/-]/', '', $input);
        case 'string':
        default:
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
}

// Secure path validation functions
function validate_file_path($path, $allowed_base_dir) {
    // Remove any null bytes
    $path = str_replace("\0", '', $path);

    // Normalize path separators
    $path = str_replace('\\', '/', $path);

    // Remove directory traversal attempts
    if (strpos($path, '../') !== false || strpos($path, './') === 0) {
        return false;
    }

    // Ensure path doesn't start with / (absolute path)
    if (strpos($path, '/') === 0) {
        return false;
    }

    // Build the full path
    $full_path = $allowed_base_dir . '/' . $path;
    $base_path = realpath($allowed_base_dir);

    // Simple validation: ensure no path traversal in the constructed path
    $normalized_full = str_replace('//', '/', $full_path);
    if (strpos($normalized_full, $base_path) !== 0) {
        return false;
    }

    return $full_path;
}

function validate_content_path($path) {
    global $CONTENT_DIR;
    if (!defined('CONTENT_DIR')) {
        $CONTENT_DIR = dirname(dirname(__FILE__)) . '/content';
    }
    return validate_file_path($path, $CONTENT_DIR);
}

function validate_upload_path($path) {
    $upload_dir = dirname(dirname(__FILE__)) . '/uploads';
    return validate_file_path($path, $upload_dir);
}

// Security headers function
function set_security_headers() {
    // Flush output buffer before setting headers
    if (function_exists('fcms_flush_output')) {
        fcms_flush_output();
    }

    // Only set headers if they haven't been sent yet
    if (!headers_sent()) {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');

        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');

        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');

        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Content Security Policy (restrictive but functional)
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://uicdn.toast.com https://cdn.quilljs.com; " .
           "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://fonts.googleapis.com https://uicdn.toast.com https://cdn.quilljs.com; " .
           "font-src 'self' https://fonts.gstatic.com; " .
           "img-src 'self' data: https:; " .
           "connect-src 'self'; " .
           "object-src 'none'; " .
           "base-uri 'self'";
    header('Content-Security-Policy: ' . $csp);

    // Feature policy (restrict dangerous features)
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    }
}

// Rate limiting for login attempts (DISABLED)
function check_login_rate_limit(string $username, int $max_attempts = 5, int $time_window = 900): bool {
    // Rate limiting temporarily disabled - always allow login attempts
    return true;
    
    // Original code commented out:
    /*
    $key = "login_rate_limit_" . md5($username);
    $now = time();

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'reset_time' => $now + $time_window];
    }

    if ($now > $_SESSION[$key]['reset_time']) {
        $_SESSION[$key] = ['count' => 0, 'reset_time' => $now + $time_window];
    }

    if ($_SESSION[$key]['count'] >= $max_attempts) {
        return false;
    }

    $_SESSION[$key]['count']++;
    return true;
    */
}

// Generic rate limiting for sensitive operations
function check_operation_rate_limit(string $operation, string $identifier = '', int $max_attempts = 3, int $time_window = 300): bool {
    $key = "op_rate_limit_" . md5($operation . '_' . $identifier);
    $now = time();

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'reset_time' => $now + $time_window];
    }

    if ($now > $_SESSION[$key]['reset_time']) {
        $_SESSION[$key] = ['count' => 0, 'reset_time' => $now + $time_window];
    }

    if ($_SESSION[$key]['count'] >= $max_attempts) {
        return false;
    }

    $_SESSION[$key]['count']++;
    return true;
}

function isLoggedIn() {
    $logged_in = !empty($_SESSION['username']);
    error_log("Checking login status: " . ($logged_in ? "Logged in as " . $_SESSION['username'] : "Not logged in"));
    return $logged_in;
}

function login($username, $password) {
    error_log("Login attempt for user: " . $username);

    // Validate and sanitize inputs
    if (!validate_username($username)) {
        error_log("Invalid username format: " . $username);
        return false;
    }

    $username = sanitize_input($username, 'username');

    // Check rate limiting
    if (!check_login_rate_limit($username)) {
        error_log("Rate limit exceeded for user: " . $username);
        return false;
    }

    $users_file = CONFIG_DIR . '/users.json';
    error_log("Looking for users file at: " . $users_file);

    if (!file_exists($users_file)) {
        error_log("Users file not found at: " . $users_file);
        return false;
    }

    $users = json_decode(file_get_contents($users_file), true);
    error_log("Loaded users data: " . print_r($users, true));

    if (!$users) {
        error_log("Failed to decode users.json");
        return false;
    }

    // Find user in the numeric array
    $user = null;
    foreach ($users as $u) {
        if ($u['username'] === $username) {
            $user = $u;
            break;
        }
    }

    if ($user) {
        error_log("Found user: " . print_r($user, true));
        if (password_verify($password, $user['password'])) {
            error_log("Password verified for user: " . $username);

            // Regenerate session ID to prevent session fixation attacks
            if (function_exists('session_regenerate_id') && !headers_sent()) {
                session_regenerate_id(false);
            }

            // Set session variables
            $_SESSION['username'] = $username;

            // Set permissions based on role
            if (isset($user['role'])) {
                $rolesFile = CONFIG_DIR . '/roles.json';
                if (file_exists($rolesFile)) {
                    $roles = json_decode(file_get_contents($rolesFile), true);
                    if (isset($roles[$user['role']])) {
                        $_SESSION['permissions'] = $roles[$user['role']]['capabilities'];
                        error_log("Set permissions for user: " . print_r($roles[$user['role']]['capabilities'], true));
                    }
                }
            }

            return true;
        }
        error_log("Password verification failed for user: " . $username);
        return false;
    }

    error_log("User not found: " . $username);
    return false;
}

function logout() {
    error_log("Logging out user: " . ($_SESSION['username'] ?? 'unknown'));
    if (function_exists('session_destroy')) {
        session_destroy();
    }
    // Don't start a new session after logout
}

function fcms_check_permission($username, $permission) {
    if (empty($username)) {
        error_log("Permission check failed: username is empty");
        return false;
    }

    $usersFile = CONFIG_DIR . '/users.json';
    if (!file_exists($usersFile)) {
        error_log("Permission check failed: users file not found at " . $usersFile);
        return false;
    }

    $users = json_decode(file_get_contents($usersFile), true);
    if (!$users) {
        error_log("Permission check failed: could not decode users file");
        return false;
    }

    // Find user in the numeric array
    $user = null;
    foreach ($users as $u) {
        if ($u['username'] === $username) {
            $user = $u;
            break;
        }
    }

    if ($user) {
        // Check if user has a role defined
        if (isset($user['role'])) {
            $rolesFile = CONFIG_DIR . '/roles.json';
            if (file_exists($rolesFile)) {
                $roles = json_decode(file_get_contents($rolesFile), true);
                if (isset($roles[$user['role']]) && in_array($permission, $roles[$user['role']]['capabilities'])) {
                    return true;
                }
            }
        }
        // Fallback to direct permissions
        return isset($user['permissions']) && in_array($permission, $user['permissions']);
    }

    error_log("Permission check failed: user not found");
    return false;
}

function createDefaultAdminUser() {
    // Default admin user creation removed for security
    // Use install.php to create the initial admin user
    return false;
}
