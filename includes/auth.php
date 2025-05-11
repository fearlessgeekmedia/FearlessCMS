<?php
// Authentication functions

function isLoggedIn() {
    $logged_in = isset($_SESSION['username']);
    error_log("Checking login status: " . ($logged_in ? "Logged in as " . $_SESSION['username'] : "Not logged in"));
    return $logged_in;
}

function login($username, $password) {
    error_log("Login attempt for user: " . $username);
    
    // Clear any existing session data
    session_unset();
    session_destroy();
    session_start();
    
    $users_file = ADMIN_CONFIG_DIR . '/users.json';
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
    
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            error_log("Found user: " . print_r($user, true));
            if (password_verify($password, $user['password'])) {
                error_log("Password verified for user: " . $username);
                
                // Set session variables
                $_SESSION['username'] = $username;
                
                // Set permissions
                if (isset($user['permissions']) && is_array($user['permissions'])) {
                    $_SESSION['permissions'] = $user['permissions'];
                    error_log("Set permissions for user: " . print_r($user['permissions'], true));
                } else {
                    error_log("No permissions found for user, setting default");
                    $_SESSION['permissions'] = ['manage_content'];
                }
                
                error_log("Final session state: " . print_r($_SESSION, true));
                return true;
            }
            error_log("Password verification failed for user: " . $username);
            return false;
        }
    }
    
    error_log("User not found: " . $username);
    return false;
}

function logout() {
    error_log("Logging out user: " . ($_SESSION['username'] ?? 'unknown'));
    session_destroy();
    session_start();
}

function fcms_check_permission($username, $permission) {
    if (empty($username)) {
        error_log("Permission check failed: username is empty");
        return false;
    }
    
    $usersFile = ADMIN_CONFIG_DIR . '/users.json';
    if (!file_exists($usersFile)) {
        error_log("Permission check failed: users file not found at " . $usersFile);
        return false;
    }
    
    $users = json_decode(file_get_contents($usersFile), true);
    if (!$users) {
        error_log("Permission check failed: could not decode users file");
        return false;
    }
    
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            // Check if user has a role defined
            if (isset($user['role'])) {
                $rolesFile = PROJECT_ROOT . '/config/roles.json';
                if (file_exists($rolesFile)) {
                    $roles = json_decode(file_get_contents($rolesFile), true);
                    if (isset($roles[$user['role']]) && in_array($permission, $roles[$user['role']]['permissions'])) {
                        return true;
                    }
                }
            }
            // Fallback to direct permissions
            return isset($user['permissions']) && in_array($permission, $user['permissions']);
        }
    }
    
    error_log("Permission check failed: user not found");
    return false;
}

function createDefaultAdminUser() {
    $users_file = ADMIN_CONFIG_DIR . '/users.json';
    if (!file_exists($users_file)) {
        error_log("Creating default admin user");
        $default_password = 'changeme123'; // Default password
        $users = [
            'admin' => [
                'password' => password_hash($default_password, PASSWORD_DEFAULT),
                'permissions' => ['manage_content', 'manage_plugins', 'manage_themes', 'manage_menus', 'manage_settings']
            ]
        ];
        
        if (!is_dir(ADMIN_CONFIG_DIR)) {
            mkdir(ADMIN_CONFIG_DIR, 0755, true);
        }
        
        if (file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT))) {
            error_log("Default admin user created successfully");
    return true;
        } else {
            error_log("Failed to create default admin user");
            return false;
        }
    }
    return false;
}

// Call this function when the file is included
createDefaultAdminUser(); 