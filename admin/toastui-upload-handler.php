<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    echo json_encode(['success' => 0, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $uploadDir = dirname(__DIR__) . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $file = $_FILES['image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => 0, 'message' => 'Invalid file type']);
        exit;
    }
    $filename = uniqid('toastui_', true) . '.' . $ext;
    $target = $uploadDir . $filename;
    if (move_uploaded_file($file['tmp_name'], $target)) {
        $url = '/uploads/' . $filename;
        echo json_encode(['success' => 1, 'url' => $url]);
        exit;
    } else {
        echo json_encode(['success' => 0, 'message' => 'move_uploaded_file failed']);
        exit;
    }
}
echo json_encode(['success' => 0, 'message' => 'Upload failed']);

