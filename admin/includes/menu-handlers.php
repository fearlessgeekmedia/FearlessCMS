<?php
/**
 * AJAX Menu Handlers for FearlessCMS Admin
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($action) && $action === 'manage_menus') {
    // Clear any output
    if (ob_get_level()) {
        ob_clean();
    }
    header('Content-Type: application/json');

    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'You must be logged in to perform this action']);
        exit;
    }

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
            exit;

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
            exit;

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
            exit;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            exit;
    }
}
