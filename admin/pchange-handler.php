<?php
// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to change password';
    } elseif (!validate_csrf_token()) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } elseif (!check_operation_rate_limit('change_password', $_SESSION['username'])) {
        $error = 'Too many password change attempts. Please wait before trying again.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'All password fields are required';
        } elseif (!validate_password($newPassword)) {
            $error = 'New password must be at least 8 characters with letters and numbers';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match';
        } else {
            $users = json_decode(file_get_contents($usersFile), true);
            $userIndex = array_search($_SESSION['username'], array_column($users, 'username'));

            if ($userIndex !== false && password_verify($currentPassword, $users[$userIndex]['password'])) {
                $users[$userIndex]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
                $success = 'Password changed successfully';

                // Log security event
                error_log("SECURITY: Password changed for user '{$_SESSION['username']}' from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            } else {
                $error = 'Current password is incorrect';
            }
        }
    }
}
?>
