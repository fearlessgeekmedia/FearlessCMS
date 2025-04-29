<?php
// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to change password';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match';
        } else {
            $users = json_decode(file_get_contents($usersFile), true);
            $userIndex = array_search($_SESSION['username'], array_column($users, 'username'));

            if ($userIndex !== false && password_verify($currentPassword, $users[$userIndex]['password'])) {
                $users[$userIndex]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                file_put_contents($usersFile, json_encode($users));
                $success = 'Password changed successfully';
            } else {
                $error = 'Current password is incorrect';
            }
        }
    }
}
?>
