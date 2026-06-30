<?php
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/config.php';

ob_start();

// Authentication check - must be first
if (!isLoggedIn()) {
    $configFile = CONFIG_DIR . '/config.json';
    $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
    $adminPath = $config['admin_path'] ?? 'admin';
    
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
if (!$data || empty($data['content']) || empty($data['title'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => !$data ? 'Invalid JSON data' : 'Missing required fields']);
    exit;
}

// Validate CSRF token - check JSON body
$csrfValid = false;
if (isset($data['csrf_token']) && isset($_SESSION['csrf_token'])) {
    $csrfValid = $_SESSION['csrf_token'] === $data['csrf_token'];
}
// Only fallback to dev mode if session doesn't have a token
if (!$csrfValid && is_development_mode() && !isset($_SESSION['csrf_token']) && isset($data['csrf_token'])) {
    $csrfValid = validate_development_csrf_token($data['csrf_token']);
}

if (!$csrfValid) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid security token']);
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
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'path' => $filename]);
    exit;
} else {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Failed to save preview file']);
    exit;
}