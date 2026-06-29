<?php
/**
 * Consolidated AJAX Menu Handler for FearlessCMS Admin
 * Handles load_menu (GET) and save_menu, create_menu, delete_menu (POST)
 */

ob_start();
ob_clean();

define('PROJECT_ROOT', __DIR__);
define('CONFIG_DIR', PROJECT_ROOT . '/config');

require_once PROJECT_ROOT . '/includes/session.php';
require_once PROJECT_ROOT . '/includes/auth.php';

if (empty($_SESSION['username'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'You must be logged in to perform this action']);
    exit;
}

if (!fcms_check_permission($_SESSION['username'], 'manage_menus')) {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'You do not have permission to manage menus']);
    exit;
}

$menuFile = CONFIG_DIR . '/menus.json';
$menus = file_exists($menuFile) ? json_decode(file_get_contents($menuFile), true) : [];

// Handle load_menu action (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'load_menu') {
    ob_end_clean();
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');

    if (!isset($_GET['menu_id'])) {
        echo json_encode(['success' => false, 'error' => 'Menu ID is required']);
        exit;
    }

    $menuId = $_GET['menu_id'];

    if (!isset($menus[$menuId])) {
        echo json_encode(['success' => false, 'error' => 'Menu not found']);
        exit;
    }

    echo json_encode($menus[$menuId]);
    exit;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_end_clean();
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['action'])) {
        echo json_encode(['success' => false, 'error' => 'No action specified']);
        exit;
    }

    switch ($data['action']) {
        case 'save_menu':
            if (empty($data['menu_id'])) {
                echo json_encode(['success' => false, 'error' => 'Menu ID is required']);
                exit;
            }

            $menuId = $data['menu_id'];
            $items = $data['items'] ?? [];

            if (!is_array($items)) {
                echo json_encode(['success' => false, 'error' => 'Invalid items format']);
                exit;
            }

            $menus[$menuId] = [
                'label' => $data['label'] ?? ucwords(str_replace('_', ' ', $menuId)),
                'menu_class' => $data['class'] ?? '',
                'items' => $items
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

ob_end_clean();
header('Content-Type: application/json');
http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Invalid request method']);
exit;
