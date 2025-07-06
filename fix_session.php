<?php
session_start();

echo "Current session username: " . ($_SESSION['username'] ?? 'none') . "\n";

// Check if the current session username exists in users.json
require_once 'includes/config.php';
$usersFile = CONFIG_DIR . '/users.json';
$users = json_decode(file_get_contents($usersFile), true);

$currentUsername = $_SESSION['username'] ?? '';
$usernameExists = false;
$correctUsername = '';

foreach ($users as $user) {
    if ($user['username'] === $currentUsername) {
        $usernameExists = true;
        break;
    }
    // Also check for admin user
    if ($user['role'] === 'admin') {
        $correctUsername = $user['username'];
    }
}

if (!$usernameExists && !empty($correctUsername)) {
    echo "Session username '$currentUsername' not found in users.json\n";
    echo "Updating session to use correct admin username: '$correctUsername'\n";
    $_SESSION['username'] = $correctUsername;
    echo "Session updated successfully!\n";
} else {
    echo "Session username is correct or no admin user found\n";
}

echo "Final session username: " . ($_SESSION['username'] ?? 'none') . "\n"; 