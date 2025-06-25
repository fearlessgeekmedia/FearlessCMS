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
        if (!empty($_FILES['file']['name'])) {
            $file = $_FILES['file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExts)) {
                $error = 'File type not allowed.';
            } elseif ($file['size'] > $maxFileSize) {
                $error = 'File is too large.';
            } else {
                $target = $uploadsDir . '/' . basename($file['name']);
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $success = 'File uploaded successfully.';
                } else {
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
        <input type="file" name="file" required class="border rounded px-2 py-1">
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

