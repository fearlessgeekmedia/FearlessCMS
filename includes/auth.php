<?php
// Authentication functions

function isLoggedIn() {
    $logged_in = !empty($_SESSION['username']);
    error_log("Checking login status: " . ($logged_in ? "Logged in as " . $_SESSION['username'] : "Not logged in"));
    return $logged_in;
}

function login($username, $password) {
    error_log("Login attempt for user: " . $username);
    
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
            
            // Set session variables
            $_SESSION['username'] = $username;
            
            // Set permissions based on role
            if (isset($user['role'])) {
                $rolesFile = CONFIG_DIR . '/roles.json';
                if (file_exists($rolesFile)) {
                    $roles = json_decode(file_get_contents($rolesFile), true);
                    if (isset($roles[$user['role']])) {
                        $_SESSION['permissions'] = $roles[$user['role']]['capabilities'];
                        error_log("Set permissions for user: " . print_r($roles[$user['role']]['permissions'], true));
                    }
                }
            }
            
            error_log("Final session state: " . print_r($_SESSION, true));
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
    session_destroy();
    session_start();
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
    $users_file = CONFIG_DIR . '/users.json';
    if (!file_exists($users_file)) {
        error_log("Creating default admin user");
        $default_password = 'changeme123'; // Default password
        $users = [
            [
                'id' => 'admin',
                'username' => 'admin',
                'password' => password_hash($default_password, PASSWORD_DEFAULT),
                'role' => 'admin'
            ]
        ];
        
        if (!is_dir(CONFIG_DIR)) {
            mkdir(CONFIG_DIR, 0755, true);
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