<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to perform this action';
    } else {
        switch ($_POST['action']) {
            case 'add_user':
                if (!fcms_check_permission($_SESSION['username'], 'manage_users')) {
                    $error = 'You do not have permission to manage users';
                    break;
                }
                if (empty($_POST['username']) || empty($_POST['password'])) {
                    $error = 'Username and password are required';
                    break;
                }
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
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
                    if (saveUsers($users)) {
                        $success = 'User added successfully';
                    } else {
                        $error = 'Failed to add user';
                    }
                }
                break;
                
            case 'edit_user':
                if (!fcms_check_permission($_SESSION['username'], 'manage_users')) {
                    $error = 'You do not have permission to manage users';
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
                
                $users = json_decode(file_get_contents($usersFile), true);
                $userIndex = array_search($username, array_column($users, 'username'));
                
                if ($userIndex === false) {
                    $error = 'User not found';
                } else {
                    // Don't allow editing the last admin user
                    $adminCount = count(array_filter($users, fn($u) => $u['role'] === 'administrator'));
                    if ($users[$userIndex]['role'] === 'administrator' && $adminCount <= 1 && $newRole !== 'administrator') {
                        $error = 'Cannot modify the last admin user';
                    } else {
                        if (!empty($newUsername)) {
                            $users[$userIndex]['username'] = $newUsername;
                        }
                        if (!empty($newPassword)) {
                            $users[$userIndex]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                        }
                        $users[$userIndex]['role'] = $newRole;
                        
                        if (saveUsers($users)) {
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
                } else {
                    // Don't allow deleting the last admin user
                    $adminCount = count(array_filter($users, fn($u) => $u['role'] === 'administrator'));
                    if ($users[$userIndex]['role'] === 'administrator' && $adminCount <= 1) {
                        $error = 'Cannot delete the last admin user';
                    } else {
                        array_splice($users, $userIndex, 1);
                        if (saveUsers($users)) {
                            $success = 'User deleted successfully';
                        } else {
                            $error = 'Failed to delete user';
                        }
                    }
                }
                break;
        }
    }
} 