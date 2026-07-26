<?php

/**
 * FearlessCMS
 *
 * Copyright (C) 2026 FearlessCMS
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * As a special exception to the GNU General Public License version 2
 * ("GPL"), the copyright holder(s) of FearlessCMS give you permission
 * to link, load, or combine this software with independent modules
 * ("Themes" and "Plugins") that are not derived from or based on this
 * software, regardless of the license terms of those Themes or
 * Plugins, including proprietary or commercial licenses, and to
 * convey the resulting combination under terms of your choice,
 * provided that you also convey the corresponding source code of the
 * FearlessCMS portions under the terms of the GPL.
 *
 * This exception does not affect any of your obligations under the
 * GPL with respect to the FearlessCMS core software itself, and it
 * does not extend to modifications of FearlessCMS core files — only
 * to independent Theme and Plugin modules that interact with
 * FearlessCMS through its documented Theme/Plugin API.
 */

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

// Load session configuration
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
if (empty($_SESSION['username'])) {
    ob_end_clean();
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'You must be logged in to perform this action']);
    exit;
}

// Check for permission to manage menus
if (!fcms_check_permission($_SESSION['username'], 'manage_menus')) {
    ob_end_clean();
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'You do not have permission to manage menus']);
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

    if (empty($data['csrf_token']) || !isset($_SESSION['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid security token. Please refresh the page and try again.']);
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