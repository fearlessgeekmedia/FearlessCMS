<?php
/**
 * Dedicated AJAX handler for menu operations
 * This avoids the output issues from the main admin index.php
 */

// Start output buffering immediately and clean any existing output
ob_start();
ob_clean();

// Basic configuration
define('PROJECT_ROOT', __DIR__);
define('CONFIG_DIR', PROJECT_ROOT . '/config');

// Simple session check without heavy dependencies
session_start();
if (empty($_SESSION['username'])) {
    ob_end_clean();
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'You must be logged in to perform this action']);
    exit;
}

// Handle load_menu action (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'load_menu') {
    // Clean any output and set JSON headers
    ob_end_clean();
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    if (!isset($_GET['menu_id'])) {
        echo json_encode(['success' => false, 'error' => 'Menu ID is required']);
        exit;
    }

    $menuId = $_GET['menu_id'];
    $menuFile = CONFIG_DIR . '/menus.json';

    if (!file_exists($menuFile)) {
        echo json_encode(['success' => false, 'error' => 'Menu file not found']);
        exit;
    }

    $menus = json_decode(file_get_contents($menuFile), true);
    if (!isset($menus[$menuId])) {
        echo json_encode(['success' => false, 'error' => 'Menu not found']);
        exit;
    }

    echo json_encode($menus[$menuId]);
    exit;
}

// Handle POST actions (save_menu, create_menu, delete_menu)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clean any output and set JSON headers
    ob_end_clean();
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['action'])) {
        echo json_encode(['success' => false, 'error' => 'No action specified']);
        exit;
    }
    
    $menuFile = CONFIG_DIR . '/menus.json';
    $menus = file_exists($menuFile) ? json_decode(file_get_contents($menuFile), true) : [];
    
    switch ($data['action']) {
        case 'save_menu':
            if (empty($data['menu_id']) || !isset($data['items'])) {
                echo json_encode(['success' => false, 'error' => 'Menu ID and items are required']);
                exit;
            }
            
            $menuId = $data['menu_id'];
            $menus[$menuId] = [
                'label' => $data['label'] ?? ucwords(str_replace('_', ' ', $menuId)),
                'menu_class' => $data['class'] ?? '',
                'items' => $data['items']
            ];
            
            if (file_put_contents($menuFile, json_encode($menus, JSON_PRETTY_PRINT))) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to save menu']);
            }
            break;
            
        case 'create_menu':
            if (empty($data['name'])) {
                echo json_encode(['success' => false, 'error' => 'Menu name is required']);
                exit;
            }
            
            $menuId = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $data['name']));
            
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
            if (empty($data['menu_id'])) {
                echo json_encode(['success' => false, 'error' => 'Menu ID is required']);
                exit;
            }
            
            $menuId = $data['menu_id'];
            
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
    exit;
}

// If we get here, it's an invalid request
ob_end_clean();
http_response_code(400);
header('Content-Type: application/json');
echo json_encode(['success' => false, 'error' => 'Invalid request method']);
exit; 