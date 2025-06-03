<?php
// Handle adding new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to add users';
    } else {
        $newUsername = $_POST['new_username'] ?? '';
        $newUserPassword = $_POST['new_user_password'] ?? '';

        if (empty($newUsername) || empty($newUserPassword)) {
            $error = 'Username and password are required';
        } else {
            $users = json_decode(file_get_contents($usersFile), true);

            // Check if username already exists
            if (array_search($newUsername, array_column($users, 'username')) !== false) {
                $error = 'Username already exists';
            } else {
                $users[] = [
                    'username' => $newUsername,
                    'password' => password_hash($newUserPassword, PASSWORD_DEFAULT)
                ];
                file_put_contents($usersFile, json_encode($users));
                $success = 'User added successfully';
            }
        }
    }
}
?>
