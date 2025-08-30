<?php
require_once dirname(__DIR__) . '/includes/config.php';

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
    exit;
}

// Validate required fields
if (empty($data['content']) || empty($data['title'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Create preview directory if it doesn't exist
$previewDir = CONTENT_DIR . '/_preview';
if (!is_dir($previewDir)) {
    mkdir($previewDir, 0755, true);
}

// Generate a unique filename
$filename = uniqid('preview_') . '.md';
$previewFile = $previewDir . '/' . $filename;

// Create metadata
$metadata = [
    'title' => $data['title'],
            'template' => $data['template'] ?? 'page-with-sidebar'
];

// Format content with metadata
$contentWithMetadata = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->' . "\n\n" . $data['content'];

// Save the preview file
if (file_put_contents($previewFile, $contentWithMetadata)) {
    echo json_encode(['success' => true, 'path' => $filename]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save preview file']);
} 