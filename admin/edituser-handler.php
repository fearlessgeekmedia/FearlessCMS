<?php
// Handle editing user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to edit users';
    } else {
        $username = $_POST['username'] ?? '';
        $newUsername = trim($_POST['new_username'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $newRole = $_POST['user_role'] ?? '';
        
        if (empty($username)) {
            $error = 'Username is required';
        } else {
            $users = json_decode(file_get_contents($usersFile), true);
            $userIndex = -1;
            
            // Find the user
            foreach ($users as $index => $user) {
                if ($user['username'] === $username) {
                    $userIndex = $index;
                    break;
                }
            }
            
            if ($userIndex === -1) {
                $error = 'User not found';
            } else {
                // Don't allow changing admin user's role
                if ($username === 'admin') {
                    $newRole = 'administrator';
                }
                
                // Only allow administrators to change roles
                if (!empty($newRole) && $username !== 'admin') {
                    $users[$userIndex]['role'] = $newRole;
                }
                
                // Update username if provided
                if (!empty($newUsername) && $newUsername !== $username) {
                    // Check if new username already exists
                    foreach ($users as $user) {
                        if ($user['username'] === $newUsername) {
                            $error = 'Username already exists';
                            break;
                        }
                    }
                    if (!isset($error)) {
                        $users[$userIndex]['username'] = $newUsername;
                    }
                }
                
                // Update password if provided
                if (!empty($newPassword)) {
                    $users[$userIndex]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                }
                
                if (!isset($error)) {
                    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
                    $success = 'User updated successfully';
                }
            }
        }
    }
}
?>
