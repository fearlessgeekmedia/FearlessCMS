# Add CSS for file previews
<style>
.file-preview {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 4px;
    margin-right: 1rem;
    transition: transform 0.2s ease;
}

.file-preview:hover {
    transform: scale(1.05);
}

.file-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    border-radius: 8px;
    margin-right: 1rem;
}

.file-icon svg {
    width: 24px;
    height: 24px;
    color: #6b7280;
}

.file-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1.5rem;
    padding: 1rem 0;
}

.file-grid.hidden {
    display: none;
}

.file-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
    transition: all 0.2s ease;
}

.file-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.file-info {
    margin-top: 0.5rem;
}

.file-name {
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 0.25rem;
    word-break: break-all;
}

.file-meta {
    font-size: 0.875rem;
    color: #6b7280;
}

.file-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.75rem;
}

.file-actions button,
.file-actions a {
    flex: 1;
    text-align: center;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.view-toggle {
    margin-bottom: 1rem;
}

.view-toggle button {
    padding: 0.5rem 1rem;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    color: #4b5563;
    transition: all 0.2s ease;
}

.view-toggle button.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
}

.view-toggle button:first-child {
    border-radius: 4px 0 0 4px;
}

.view-toggle button:last-child {
    border-radius: 0 4px 4px 0;
}
</style>

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

<div class="view-toggle flex gap-2">
    <button onclick="toggleView('grid')" class="active" id="grid-view-btn">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block" viewBox="0 0 20 20" fill="currentColor">
            <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
        </svg>
        Grid
    </button>
    <button onclick="toggleView('list')" id="list-view-btn">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
        </svg>
        List
    </button>
</div>

<div id="grid-view" class="file-grid">
    <?php foreach ($files as $file): ?>
        <div class="file-card">
            <?php if (in_array($file['ext'], ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                <img src="<?= htmlspecialchars($file['url']) ?>" alt="<?= htmlspecialchars($file['name']) ?>" class="file-preview">
            <?php else: ?>
                <div class="file-icon">
                    <?php
                    $icon = match($file['ext']) {
                        'pdf' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg>',
                        'zip' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20 6h-8l-2-2H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm0 12H4V8h16v10z"/></svg>',
                        'txt', 'md' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/></svg>',
                        'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M21 3H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H3V5h18v14z"/></svg>',
                        default => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M6 2c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6H6zm7 7V3.5L18.5 9H13z"/></svg>'
                    };
                    echo $icon;
                    ?>
                </div>
            <?php endif; ?>
            <div class="file-info">
                <div class="file-name"><?= htmlspecialchars($file['name']) ?></div>
                <div class="file-meta">
                    <?= number_format($file['size']/1024, 2) ?> KB
                </div>
            </div>
            <div class="file-actions">
                <a href="<?= htmlspecialchars($file['url']) ?>" target="_blank" class="bg-blue-500 text-white hover:bg-blue-600">View</a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this file?')">
                    <input type="hidden" name="action" value="delete_file">
                    <input type="hidden" name="filename" value="<?= htmlspecialchars($file['name']) ?>">
                    <button type="submit" class="bg-red-500 text-white hover:bg-red-600 w-full">Delete</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div id="list-view" class="hidden">
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
                    <div class="flex items-center">
                        <?php if (in_array($file['ext'], ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                            <img src="<?= htmlspecialchars($file['url']) ?>" alt="<?= htmlspecialchars($file['name']) ?>" class="file-preview">
                        <?php else: ?>
                            <div class="file-icon">
                                <?php
                                $icon = match($file['ext']) {
                                    'pdf' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg>',
                                    'zip' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20 6h-8l-2-2H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm0 12H4V8h16v10z"/></svg>',
                                    'txt', 'md' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/></svg>',
                                    'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M21 3H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H3V5h18v14z"/></svg>',
                                    default => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M6 2c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6H6zm7 7V3.5L18.5 9H13z"/></svg>'
                                };
                                echo $icon;
                                ?>
                            </div>
                        <?php endif; ?>
                        <a href="<?= htmlspecialchars($file['url']) ?>" target="_blank" class="ml-2"><?= htmlspecialchars($file['name']) ?></a>
                    </div>
                </td>
                <td class="py-2 border-b"><?= htmlspecialchars($file['type']) ?></td>
                <td class="py-2 border-b"><?= number_format($file['size']/1024, 2) ?> KB</td>
                <td class="py-2 border-b">
                    <div class="flex gap-2">
                        <a href="<?= htmlspecialchars($file['url']) ?>" target="_blank" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">View</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this file?')">
                            <input type="hidden" name="action" value="delete_file">
                            <input type="hidden" name="filename" value="<?= htmlspecialchars($file['name']) ?>">
                            <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function toggleView(view) {
    const gridView = document.getElementById('grid-view');
    const listView = document.getElementById('list-view');
    const gridBtn = document.getElementById('grid-view-btn');
    const listBtn = document.getElementById('list-view-btn');
    
    if (view === 'grid') {
        gridView.classList.remove('hidden');
        listView.classList.add('hidden');
        gridBtn.classList.add('active');
        listBtn.classList.remove('active');
    } else {
        gridView.classList.add('hidden');
        listView.classList.remove('hidden');
        gridBtn.classList.remove('active');
        listBtn.classList.add('active');
    }
}

// Initialize view state
document.addEventListener('DOMContentLoaded', function() {
    // Store view preference
    const savedView = localStorage.getItem('fileManagerView') || 'grid';
    toggleView(savedView);
    
    // Update view toggle buttons to save preference
    document.getElementById('grid-view-btn').addEventListener('click', () => {
        localStorage.setItem('fileManagerView', 'grid');
    });
    document.getElementById('list-view-btn').addEventListener('click', () => {
        localStorage.setItem('fileManagerView', 'list');
    });
});
</script>
