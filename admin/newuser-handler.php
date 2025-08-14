<?php
// Handle adding new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to add users';
    } elseif (!validate_csrf_token()) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } elseif (!check_operation_rate_limit('create_user', $_SESSION['username'])) {
        $error = 'Too many user creation attempts. Please wait before trying again.';
    } else {
        $newUsername = sanitize_input($_POST['new_username'] ?? '', 'username');
        $newUserPassword = $_POST['new_user_password'] ?? '';

        if (empty($newUsername) || empty($newUserPassword)) {
            $error = 'Username and password are required';
        } elseif (!validate_username($newUsername)) {
            $error = 'Username must be 3-50 characters, letters, numbers, underscore and dash only';
        } elseif (!validate_password($newUserPassword)) {
            $error = 'Password must be at least 8 characters with letters and numbers';
        } else {
            $users = json_decode(file_get_contents($usersFile), true);

            // Check if username already exists
            if (array_search($newUsername, array_column($users, 'username')) !== false) {
                $error = 'Username already exists';
            } else {
                $users[] = [
                    'username' => $newUsername,
                    'password' => password_hash($newUserPassword, PASSWORD_DEFAULT),
                    'role' => 'editor' // Default role for new users
                ];
                file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
                $success = 'User added successfully';

                // Log security event
                error_log("SECURITY: User '{$newUsername}' created by '{$_SESSION['username']}' from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            }
        }
    }
}
?>
