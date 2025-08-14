<?php

// Handle deleting user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to delete users';
    } elseif (!validate_csrf_token()) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } elseif (!check_operation_rate_limit('delete_user', $_SESSION['username'])) {
        $error = 'Too many user deletion attempts. Please wait before trying again.';
    } else {
        $username = sanitize_input($_POST['username'] ?? '', 'username');

        // Additional validation
        if (empty($username) || !validate_username($username)) {
            $error = 'Invalid username provided';
        } elseif ($username === $_SESSION['username']) {
            $error = 'Cannot delete your own account';
        } else {

            $users = json_decode(file_get_contents($usersFile), true);
            $userIndex = array_search($username, array_column($users, 'username'));

            if ($userIndex === false) {
                $error = 'User not found';
            } else {
                // Don't allow deleting the last admin user
                $adminCount = count(array_filter($users, fn($u) => ($u['role'] ?? '') === 'administrator' || $u['username'] === 'admin'));
                if (($users[$userIndex]['role'] ?? '') === 'administrator' && $adminCount <= 1) {
                    $error = 'Cannot delete the last administrator user';
                } else {
                    array_splice($users, $userIndex, 1);
                    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
                    $success = 'User deleted successfully';

                    // Log security event
                    error_log("SECURITY: User '{$username}' deleted by '{$_SESSION['username']}' from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                }
            }
        }
    }
}
?>
