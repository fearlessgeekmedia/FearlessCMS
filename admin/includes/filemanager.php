// Register file manager admin section
fcms_register_admin_section('files', [
    'label' => 'Files',
    'menu_order' => 50,
    'render_callback' => 'fcms_render_file_manager'
]);

function fcms_render_file_manager() {
    $uploadsDir = dirname(__DIR__) . '/uploads';
    $webUploadsDir = '/uploads';
    $allowedExts = ['jpg','jpeg','png','gif','webp','pdf','zip','svg','txt','md'];
    $maxFileSize = 10 * 1024 * 1024; // 10MB

    $error = '';
    $success = '';

    // Handle file upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_file') {
        // Validate CSRF token
        if (!function_exists('validate_csrf_token') || !validate_csrf_token()) {
            $error = 'Invalid security token. Please refresh the page and try again.';
        } elseif (!empty($_FILES['file']['name'])) {
            $file = $_FILES['file'];

            // Comprehensive file validation
            $originalName = $file['name'];
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $mimeType = $file['type'];
            $tmpName = $file['tmp_name'];

            // Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $error = 'Upload error occurred.';
            }
            // Validate file extension
            elseif (!in_array($ext, $allowedExts)) {
                $error = 'File type not allowed. Allowed types: ' . implode(', ', $allowedExts);
            }
            // Validate file size
            elseif ($file['size'] > $maxFileSize) {
                $error = 'File is too large. Maximum size: ' . round($maxFileSize/1024/1024) . 'MB';
            }
            // Validate MIME type for additional security
            elseif (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'text/plain', 'application/pdf'])) {
                $error = 'Invalid file type detected.';
            }
            // Check for executable content in filename
            elseif (preg_match('/\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$/i', $originalName)) {
                $error = 'Executable files are not allowed.';
            }
            else {
                // Sanitize filename - remove dangerous characters
                $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $safeName = preg_replace('/_{2,}/', '_', $safeName); // Remove multiple underscores

                // Ensure filename doesn't start with dot
                if (strpos($safeName, '.') === 0) {
                    $safeName = 'file' . $safeName;
                }

                // Add timestamp to prevent conflicts
                $pathInfo = pathinfo($safeName);
                $finalName = $pathInfo['filename'] . '_' . time() . '.' . $pathInfo['extension'];

                $target = $uploadsDir . '/' . $finalName;

                // Additional security: validate the actual file content
                if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $detectedMime = finfo_file($finfo, $tmpName);
                    finfo_close($finfo);

                    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'text/plain', 'application/pdf'];
                    if (!in_array($detectedMime, $allowedMimes)) {
                        $error = 'File content does not match allowed types.';
                    }
                }

                if (!isset($error) && move_uploaded_file($tmpName, $target)) {
                    // Set secure file permissions
                    chmod($target, 0644);
                    $success = 'File uploaded successfully as: ' . htmlspecialchars($finalName);
                } elseif (!isset($error)) {
                    $error = 'Failed to upload file.';
                }
            }
        } else {
            $error = 'No file selected.';
        }
    }

    // Handle file deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_file') {
        $filename = $_POST['filename'] ?? '';
        $filepath = realpath($uploadsDir . '/' . $filename);
        if ($filename && $filepath && strpos($filepath, realpath($uploadsDir)) === 0 && is_file($filepath)) {
            if (unlink($filepath)) {
                $success = 'File deleted.';
            } else {
                $error = 'Failed to delete file.';
            }
        } else {
            $error = 'Invalid file.';
        }
    }

    // List files
    $files = [];
    if (is_dir($uploadsDir)) {
        foreach (scandir($uploadsDir) as $f) {
            if ($f === '.' || $f === '..') continue;
            $full = $uploadsDir . '/' . $f;
            if (is_file($full)) {
                $files[] = [
                    'name' => $f,
                    'size' => filesize($full),
                    'type' => mime_content_type($full),
                    'url'  => $webUploadsDir . '/' . rawurlencode($f)
                ];
            }
        }
    }

    ob_start();
    ?>
    <h2 class="text-2xl font-bold mb-6 fira-code">File Manager</h2>
    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="mb-6 flex items-center gap-4">
        <input type="hidden" name="action" value="upload_file">
        <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
        <input type="file" name="file" required class="border rounded px-2 py-1" accept="<?= implode(',', array_map(function($ext) { return '.' . $ext; }, $allowedExts)) ?>">
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Upload</button>
        <span class="text-sm text-gray-500">Allowed: <?= implode(', ', $allowedExts) ?>, max <?= round($maxFileSize/1024/1024) ?>MB</span>
    </form>

    <table class="w-full border-collapse">
        <thead>
            <tr>
                <th class="border-b py-2 text-left">File</th>
                <th class="border-b py-2 text-left">Type</th>
                <th class="border-b py-2 text-left">Size</th>
                <th class="border-b py-2 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($files as $file): ?>
            <tr>
                <td class="py-2 border-b">
                    <a href="<?= htmlspecialchars($file['url']) ?>" target="_blank"><?= htmlspecialchars($file['name']) ?></a>
                </td>
                <td class="py-2 border-b"><?= htmlspecialchars($file['type']) ?></td>
                <td class="py-2 border-b"><?= number_format($file['size']/1024, 2) ?> KB</td>
                <td class="py-2 border-b">
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this file?')">
                        <input type="hidden" name="action" value="delete_file">
                        <input type="hidden" name="filename" value="<?= htmlspecialchars($file['name']) ?>">
                        <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                        <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Delete</button>
                    </form>
                    <a href="<?= htmlspecialchars($file['url']) ?>" download class="bg-gray-500 text-white px-3 py-1 rounded hover:bg-gray-600 ml-2">Download</a>
                    <?php if (strpos($file['type'], 'image/') === 0): ?>
                    <button onclick="selectFile('<?= htmlspecialchars($file['url']) ?>')" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 ml-2">Select</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <script>
    function selectFile(url) {
        if (window.opener) {
            window.opener.postMessage({
                type: 'file_selected',
                url: url
            }, '*');
            window.close();
        }
    }
    </script>
    <?php
    return ob_get_clean();
}
