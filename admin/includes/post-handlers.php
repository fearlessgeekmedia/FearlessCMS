<?php
/**
 * Early POST Request Handlers for FearlessCMS Admin
 * These handlers are executed before the main admin UI logic
 * and usually terminate with exit;
 */

// Handle image uploads for Quill.js editor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_image') {
    require_once dirname(dirname(__DIR__)) . '/includes/config.php';
    require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
    require_once dirname(dirname(__DIR__)) . '/includes/session.php';
    require_once dirname(__DIR__) . '/quill-upload-handler.php';
    exit;
}

// Handle blog featured image uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_featured_image') {
    require_once dirname(dirname(__DIR__)) . '/includes/config.php';
    require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
    require_once dirname(dirname(__DIR__)) . '/includes/session.php';
    
    // Ensure no output before headers
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json');
    
    // Check if user is logged in
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }
    
    if (isset($_FILES['image'])) {
        $uploadDir = dirname(dirname(__DIR__)) . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $file = $_FILES['image'];
        
        // Basic validation
        $originalName = $file['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowed)]);
            exit;
        }
        
        // Check file size (5MB limit for featured images)
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'File too large. Maximum size: 5MB']);
            exit;
        }
        
        // Sanitize filename
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $filename = 'blog_featured_' . $safeName . '_' . time() . '.' . $ext;
        $target = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $target)) {
            chmod($target, 0644);
            $url = '/uploads/' . $filename;
            echo json_encode(['success' => true, 'url' => $url]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Upload failed']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No image file received']);
    }
    exit;
}

// Handle site export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export_site') {
    require_once dirname(dirname(__DIR__)) . '/includes/config.php';
    require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
    require_once dirname(dirname(__DIR__)) . '/includes/session.php';
    require_once dirname(dirname(__DIR__)) . '/includes/plugins.php';
    require_once dirname(__DIR__) . '/export-handler.php';
    exit;
}

// Handle theme activation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'activate_theme') {
    require_once dirname(dirname(__DIR__)) . '/includes/config.php';
    require_once dirname(dirname(__DIR__)) . '/includes/session.php';
    require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
    
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'You must be logged in';
    } elseif (!validate_csrf_token()) {
        $_SESSION['error'] = 'Invalid security token';
    } elseif (empty($_POST['theme'])) {
        $_SESSION['error'] = 'Theme name is required';
    } else {
        $configFile = CONFIG_DIR . '/config.json';
        $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
        $config['active_theme'] = $_POST['theme'];
        if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT))) {
            $_SESSION['success'] = 'Theme activated successfully';
        } else {
            $_SESSION['error'] = 'Failed to activate theme';
        }
    }
    header('Location: /admin?action=manage_themes');
    exit;
}

// Handle page creation (create_page action)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_page') {
    require_once dirname(dirname(__DIR__)) . '/includes/config.php';
    require_once dirname(dirname(__DIR__)) . '/includes/session.php';
    require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
    require_once dirname(__DIR__) . '/newpage-handler.php';
    exit;
}
