<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to perform this action';
    } else {
        switch ($_POST['action']) {
            case 'save_menu':
                if (!fcms_check_permission($_SESSION['username'], 'manage_menus')) {
                    $error = 'You do not have permission to manage menus';
                    break;
                }
                if (empty($_POST['menu_id']) || empty($_POST['menu_data'])) {
                    $error = 'Menu ID and data are required';
                    break;
                }
                $menuId = $_POST['menu_id'] ?? '';
                $menuData = $_POST['menu_data'] ?? '';
                
                $menuFile = CONFIG_DIR . '/menus.json';
                $menus = file_exists($menuFile) ? json_decode(file_get_contents($menuFile), true) : [];
                $menus[$menuId] = json_decode($menuData, true);
                if (file_put_contents($menuFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                    $success = 'Menu saved successfully';
                } else {
                    $error = 'Failed to save menu';
                }
                break;
                
            case 'delete_menu':
                if (!fcms_check_permission($_SESSION['username'], 'manage_menus')) {
                    $error = 'You do not have permission to manage menus';
                    break;
                }
                if (empty($_POST['menu_id'])) {
                    $error = 'Menu ID is required';
                    break;
                }
                $menuId = $_POST['menu_id'] ?? '';
                
                $menuFile = CONFIG_DIR . '/menus.json';
                $menus = file_exists($menuFile) ? json_decode(file_get_contents($menuFile), true) : [];
                if (isset($menus[$menuId])) {
                    unset($menus[$menuId]);
                    if (file_put_contents($menuFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                        $success = 'Menu deleted successfully';
                    } else {
                        $error = 'Failed to delete menu';
                    }
                }
                break;
        }
    }
} 