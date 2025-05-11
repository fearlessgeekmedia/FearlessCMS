<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to perform this action';
    } else {
        switch ($_POST['action']) {
            case 'activate_plugin':
                if (!fcms_check_permission($_SESSION['username'], 'manage_plugins')) {
                    $error = 'You do not have permission to manage plugins';
                    break;
                }
                if (empty($_POST['plugin'])) {
                    $error = 'Plugin name is required';
                    break;
                }
                $plugin = $_POST['plugin'] ?? '';
                $activePluginsFile = CONFIG_DIR . '/active_plugins.json';
                $activePlugins = file_exists($activePluginsFile) ? json_decode(file_get_contents($activePluginsFile), true) : [];
                if (!in_array($plugin, $activePlugins)) {
                    $activePlugins[] = $plugin;
                    if (file_put_contents($activePluginsFile, json_encode($activePlugins, JSON_PRETTY_PRINT))) {
                        $success = 'Plugin activated successfully';
                    } else {
                        $error = 'Failed to activate plugin';
                    }
                }
                break;
                
            case 'deactivate_plugin':
                if (!fcms_check_permission($_SESSION['username'], 'manage_plugins')) {
                    $error = 'You do not have permission to manage plugins';
                    break;
                }
                if (empty($_POST['plugin'])) {
                    $error = 'Plugin name is required';
                    break;
                }
                $plugin = $_POST['plugin'] ?? '';
                $activePluginsFile = CONFIG_DIR . '/active_plugins.json';
                $activePlugins = file_exists($activePluginsFile) ? json_decode(file_get_contents($activePluginsFile), true) : [];
                $pluginIndex = array_search($plugin, $activePlugins);
                if ($pluginIndex !== false) {
                    array_splice($activePlugins, $pluginIndex, 1);
                    if (file_put_contents($activePluginsFile, json_encode($activePlugins, JSON_PRETTY_PRINT))) {
                        $success = 'Plugin deactivated successfully';
                    } else {
                        $error = 'Failed to deactivate plugin';
                    }
                }
                break;
        }
    }
} 