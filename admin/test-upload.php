<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
    error_log('Test upload - Logo upload attempt.');
    error_log('Test upload - logo _FILES: ' . print_r($_FILES['logo'], true));
    
    if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
        ];
        $err = $_FILES['logo']['error'];
        $msg = $errorMessages[$err] ?? 'Unknown upload error.';
        $error = "Upload Error: $msg (Code: $err)";
        error_log('Test upload - Logo upload error: ' . $msg);
    } else {
        $uploadsDir = PROJECT_ROOT . '/uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        
        $file = $_FILES['logo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        
        if (!in_array($ext, $allowed)) {
            $error = 'Invalid file type for logo. Allowed: ' . implode(', ', $allowed);
        } else {
            $filename = 'test_logo_' . time() . '.' . $ext;
            $target = $uploadsDir . '/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $target)) {
                $success = "Logo uploaded successfully as: $filename";
            } else {
                $error = 'Failed to move uploaded logo file. Check permissions.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logo Upload Test - FearlessCMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Logo Upload Test</h1>
            
            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>
            
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Test Logo Upload</h2>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label for="logo" class="block text-sm font-medium text-gray-700 mb-2">Select Logo File:</label>
                        <input type="file" name="logo" id="logo" accept="image/*" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-sm text-gray-500 mt-1">Allowed formats: JPG, PNG, GIF, WebP, SVG</p>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 focus:ring-2 focus:ring-blue-500">
                        Upload Logo
                    </button>
                </form>
                
                <div class="mt-6 p-4 bg-gray-50 rounded">
                    <h3 class="font-medium text-gray-900 mb-2">Debug Information:</h3>
                    <div class="text-sm text-gray-600 space-y-1">
                        <p><strong>Upload Max Filesize:</strong> <?php echo ini_get('upload_max_filesize'); ?></p>
                        <p><strong>Post Max Size:</strong> <?php echo ini_get('post_max_size'); ?></p>
                        <p><strong>Max File Uploads:</strong> <?php echo ini_get('max_file_uploads'); ?></p>
                        <p><strong>Uploads Directory:</strong> <?php echo PROJECT_ROOT . '/uploads'; ?></p>
                        <p><strong>Directory Writable:</strong> <?php echo is_writable(PROJECT_ROOT . '/uploads') ? 'Yes' : 'No'; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 text-center">
                <a href="index.php?action=manage_themes" class="text-blue-500 hover:text-blue-700">← Back to Theme Management</a>
            </div>
        </div>
    </div>
</body>
</html>
