<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Check CMS mode restrictions
require_once dirname(__DIR__) . '/includes/CMSModeManager.php';
$cmsModeManager = new CMSModeManager();

if (!$cmsModeManager->canUploadContentImages()) {
    echo json_encode(['success' => false, 'error' => 'Image uploads are disabled in the current CMS mode']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadDir = dirname(__DIR__) . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type']);
        exit;
    }
    $filename = uniqid('toastui_', true) . '.' . $ext;
    $target = $uploadDir . $filename;
    if (move_uploaded_file($file['tmp_name'], $target)) {
        $url = '/uploads/' . $filename;
        echo json_encode(['success' => true, 'url' => $url]);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'move_uploaded_file failed']);
        exit;
    }
}
echo json_encode(['success' => false, 'error' => 'Upload failed']);

