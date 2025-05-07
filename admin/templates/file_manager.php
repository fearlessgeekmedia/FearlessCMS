<h2 class="text-2xl font-bold mb-6 fira-code">File Manager</h2>
<?php if (!empty($error)): ?>
    <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
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
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
