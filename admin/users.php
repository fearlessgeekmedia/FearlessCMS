<?php
// admin/users.php - User management page
session_start();

// Configuration
define('ADMIN_CONFIG_DIR', __DIR__ . '/config');
define('ADMIN_TEMPLATE_DIR', __DIR__ . '/templates');

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$usersFile = ADMIN_CONFIG_DIR . '/users.json';
$users = json_decode(file_get_contents($usersFile), true);
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new user
    if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        $newUsername = trim($_POST['new_username']);
        $newPassword = trim($_POST['new_password']);
        
        // Validation
        if (empty($newUsername) || empty($newPassword)) {
            $error = 'Username and password are required';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Password must be at least 8 characters';
        } else {
            // Check if username already exists
            foreach ($users as $user) {
                if ($user['username'] === $newUsername) {
                    $error = 'Username already exists';
                    break;
                }
            }
            
            // Add the new user
            if (empty($error)) {
                $users[] = [
                    'username' => $newUsername,
                    'password' => password_hash($newPassword, PASSWORD_DEFAULT)
                ];
                file_put_contents($usersFile, json_encode($users));
                $message = "User '$newUsername' added successfully";
            }
        }
    }
    
    // Change password
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $username = $_POST['username'];
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Validation
        if (empty($newPassword) || strlen($newPassword) < 8) {
            $error = 'New password must be at least 8 characters';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match';
        } else {
            $userFound = false;
            
            // Find and update user
            foreach ($users as &$user) {
                if ($user['username'] === $username) {
                    $userFound = true;
                    
                    // Verify current password
                    if (password_verify($currentPassword, $user['password'])) {
                        $user['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                        file_put_contents($usersFile, json_encode($users));
                        $message = 'Password changed successfully';
                    } else {
                        $error = 'Current password is incorrect';
                    }
                    break;
                }
            }
            
            if (!$userFound) {
                $error = 'User not found';
            }
        }
    }

    // Delete user
    if (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        $usernameToDelete = $_POST['username_to_delete'];
        
        // Prevent deleting the last admin
        if (count($users) <= 1) {
            $error = 'Cannot delete the last administrator account';
        } 
        // Prevent self-deletion
        elseif ($usernameToDelete === $_SESSION['username']) {
            $error = 'You cannot delete your own account while logged in';
        } else {
            $newUsers = array_filter($users, function($user) use ($usernameToDelete) {
                return $user['username'] !== $usernameToDelete;
            });
            
            if (count($newUsers) < count($users)) {
                file_put_contents($usersFile, json_encode(array_values($newUsers)));
                $message = "User '$usernameToDelete' deleted successfully";
                $users = $newUsers;
            } else {
                $error = 'User not found';
            }
        }
    }
}

// Load template
$template = file_get_contents(ADMIN_TEMPLATE_DIR . '/users.html');

// Generate users list for display
$usersList = '';
foreach ($users as $user) {
    $usersList .= "<tr class='border-b hover:bg-gray-50'>
        <td class='py-3 px-4'>" . htmlspecialchars($user['username']) . "</td>
        <td class='py-3 px-4 text-right'>
            <button class='change-password-btn bg-blue-600 text-white py-1 px-3 rounded text-sm' 
                data-username='" . htmlspecialchars($user['username']) . "'>Change Password</button>
            <button class='delete-user-btn bg-red-600 text-white py-1 px-3 rounded text-sm ml-2' 
                data-username='" . htmlspecialchars($user['username']) . "'>Delete</button>
        </td>
    </tr>";
}

// Replace placeholders in template
$template = str_replace('{{users_list}}', $usersList, $template);
$template = str_replace('{{message}}', !empty($message) ? "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4'>{$message}</div>" : '', $template);
$template = str_replace('{{error}}', !empty($error) ? "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4'>{$error}</div>" : '', $template);
$template = str_replace('{{username}}', htmlspecialchars($_SESSION['username']), $template);

// Output the page
echo $template;
?>
