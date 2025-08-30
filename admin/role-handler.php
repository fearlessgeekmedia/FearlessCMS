<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/plugins/user-roles/user-roles.php';

// Check if user is logged in
// Session should already be started by session.php
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get current user
$currentUser = getCurrentUser();

// Handle role management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_roles':
            if (user_has_capability($currentUser['username'], 'manage_roles')) {
                $roles = json_decode($_POST['roles'] ?? '{}', true);
                if (is_array($roles)) {
                    file_put_contents(USER_ROLES_CONFIG_FILE, json_encode($roles, JSON_PRETTY_PRINT));
                    $_SESSION['success'] = 'Roles updated successfully';
                } else {
                    $_SESSION['error'] = 'Invalid roles data';
                }
            } else {
                $_SESSION['error'] = 'You do not have permission to manage roles';
            }
            break;
    }
    
    // Redirect back to role management page
    header('Location: ?action=manage_roles');
    exit;
} 