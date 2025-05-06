<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to perform this action';
    } else {
        switch ($_POST['action']) {
            case 'activate_theme':
                if (!fcms_check_permission($_SESSION['username'], 'manage_themes')) {
                    $error = 'You do not have permission to manage themes';
                    break;
                }
                if (empty($_POST['theme'])) {
                    $error = 'Theme name is required';
                    break;
                }
                $theme = $_POST['theme'] ?? '';
                $configFile = CONFIG_DIR . '/config.json';
                $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
                $config['active_theme'] = $theme;
                if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT))) {
                    $success = 'Theme activated successfully';
                } else {
                    $error = 'Failed to activate theme';
                }
                break;
        }
    }
} 