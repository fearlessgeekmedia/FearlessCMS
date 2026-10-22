<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Set JSON response headers
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $json = file_get_contents('php://input');
    error_log("Received JSON data: " . $json);
    
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data: ' . json_last_error_msg()]);
        exit;
    }
    
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'You must be logged in to perform this action']);
        exit;
    }
    
    if (!isset($data['action'])) {
        echo json_encode(['success' => false, 'error' => 'No action specified']);
        exit;
    }
    
    switch ($data['action']) {
        case 'save_menu':
            if (!fcms_check_permission($_SESSION['username'], 'manage_menus')) {
                echo json_encode(['success' => false, 'error' => 'You do not have permission to manage menus']);
                exit;
            }
            
            if (empty($data['menu_id']) || empty($data['menu_data'])) {
                echo json_encode(['success' => false, 'error' => 'Menu ID and data are required']);
                exit;
            }
            
            $menuId = $data['menu_id'];
            $menuData = $data['menu_data'];
            
            if (!is_array($menuData)) {
                echo json_encode(['success' => false, 'error' => 'Invalid menu data format']);
                exit;
            }
            
            $menuFile = CONFIG_DIR . '/menus.json';
            $menus = file_exists($menuFile) ? json_decode(file_get_contents($menuFile), true) : [];
            $menus[$menuId] = $menuData;
            
            if (file_put_contents($menuFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to save menu']);
            }
            break;
            
        case 'create_menu':
            if (!fcms_check_permission($_SESSION['username'], 'manage_menus')) {
                echo json_encode(['success' => false, 'error' => 'You do not have permission to manage menus']);
                exit;
            }
            
            if (empty($data['name'])) {
                echo json_encode(['success' => false, 'error' => 'Menu name is required']);
                exit;
            }
            
            $menuId = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $data['name']));
            $menuFile = CONFIG_DIR . '/menus.json';
            $menus = file_exists($menuFile) ? json_decode(file_get_contents($menuFile), true) : [];
            
            if (isset($menus[$menuId])) {
                echo json_encode(['success' => false, 'error' => 'Menu with this name already exists']);
                exit;
            }
            
            $menus[$menuId] = [
                'label' => $data['name'],
                'menu_class' => $data['class'] ?? '',
                'items' => []
            ];
            
            if (file_put_contents($menuFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to create menu']);
            }
            break;
            
        case 'delete_menu':
            if (!fcms_check_permission($_SESSION['username'], 'manage_menus')) {
                echo json_encode(['success' => false, 'error' => 'You do not have permission to manage menus']);
                exit;
            }
            
            if (empty($data['menu_id'])) {
                echo json_encode(['success' => false, 'error' => 'Menu ID is required']);
                exit;
            }
            
            $menuId = $data['menu_id'];
            $menuFile = CONFIG_DIR . '/menus.json';
            $menus = file_exists($menuFile) ? json_decode(file_get_contents($menuFile), true) : [];
            
            if (isset($menus[$menuId])) {
                unset($menus[$menuId]);
                if (file_put_contents($menuFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to delete menu']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Menu not found']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
} 