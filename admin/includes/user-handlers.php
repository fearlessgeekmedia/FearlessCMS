<?php
/**
 * User Management Handlers for FearlessCMS Admin
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($postAction) && in_array($postAction, ['add_user', 'edit_user', 'delete_user'])) {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to perform this action';
    } else {
        $usersFile = CONFIG_DIR . '/users.json';
        
        switch ($postAction) {
            case 'add_user':
                if (!fcms_check_permission($_SESSION['username'], 'manage_users')) {
                    $error = 'You do not have permission to manage users';
                    break;
                }
                if (empty($_POST['new_username']) || empty($_POST['new_user_password'])) {
                    $error = 'Username and password are required';
                    break;
                }
                $username = $_POST['new_username'] ?? '';
                $password = $_POST['new_user_password'] ?? '';
                $role = $_POST['role'] ?? 'author';

                $users = json_decode(file_get_contents($usersFile), true);
                if (array_search($username, array_column($users, 'username')) !== false) {
                    $error = 'Username already exists';
                } else {
                    $users[] = [
                        'id' => uniqid(),
                        'username' => $username,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'role' => $role
                    ];
                    if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT))) {
                        $success = 'User added successfully';
                    } else {
                        $error = 'Failed to add user';
                    }
                }
                break;

            case 'edit_user':
                $users = json_decode(file_get_contents($usersFile), true);
                $currentUser = null;
                foreach ($users as $user) {
                    if ($user['username'] === $_SESSION['username']) {
                        $currentUser = $user;
                        break;
                    }
                }

                if (!$currentUser) {
                    foreach ($users as $user) {
                        if ($user['role'] === 'admin') {
                            $currentUser = $user;
                            $_SESSION['username'] = $user['username'];
                            break;
                        }
                    }
                }

                if (!$currentUser) {
                    $error = 'User not found in database';
                    $action = 'manage_users';
                    break;
                }

                if (!fcms_check_permission($_SESSION['username'], 'manage_users')) {
                    $error = 'You do not have permission to manage users';
                    $action = 'manage_users';
                    break;
                }
                if (empty($_POST['username'])) {
                    $error = 'Username is required';
                    break;
                }
                $username = $_POST['username'] ?? '';
                $newUsername = $_POST['new_username'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $newRole = $_POST['user_role'] ?? 'author';

                $userIndex = array_search($username, array_column($users, 'username'));

                if ($userIndex === false) {
                    $error = 'User not found';
                    break;
                } else {
                    $adminCount = count(array_filter($users, fn($u) => $u['role'] === 'administrator'));
                    if ($users[$userIndex]['role'] === 'administrator' && $adminCount <= 1 && $newRole !== 'administrator') {
                        $error = 'Cannot modify the last admin user';
                    } else {
                        if (!empty($newUsername)) {
                            $users[$userIndex]['username'] = $newUsername;
                            if ($_SESSION['username'] === $username) {
                                $_SESSION['username'] = $newUsername;
                            }
                        }
                        if (!empty($newPassword)) {
                            $users[$userIndex]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                        }
                        $users[$userIndex]['role'] = $newRole;

                        if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
                            $users[$userIndex]['permissions'] = $_POST['permissions'];
                        } else {
                            $users[$userIndex]['permissions'] = [];
                        }

                        if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT))) {
                            $success = 'User updated successfully';
                        } else {
                            $error = 'Failed to update user';
                        }
                    }
                }
                break;

            case 'delete_user':
                if (!fcms_check_permission($_SESSION['username'], 'manage_users')) {
                    $error = 'You do not have permission to manage users';
                    break;
                }
                if (empty($_POST['username'])) {
                    $error = 'Username is required';
                    break;
                }
                $username = $_POST['username'] ?? '';

                $users = json_decode(file_get_contents($usersFile), true);
                $userIndex = array_search($username, array_column($users, 'username'));

                if ($userIndex === false) {
                    $error = 'User not found';
                    break;
                } else {
                    $adminCount = count(array_filter($users, fn($u) => $u['role'] === 'administrator'));
                    if ($users[$userIndex]['role'] === 'administrator' && $adminCount <= 1) {
                        $error = 'Cannot delete the last admin user';
                    } else {
                        array_splice($users, $userIndex, 1);
                        if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT))) {
                            $success = 'User deleted successfully';
                        } else {
                            $error = 'Failed to delete user';
                        }
                    }
                }
                break;
        }
    }
    $action = 'manage_users';
}
