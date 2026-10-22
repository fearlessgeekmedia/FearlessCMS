<?php
// This template now delegates all file operations to the backend file manager function
// The backend function handles CSRF validation, file processing, and security

// Pagination settings
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$totalItems = count($files);
$totalPages = ceil($totalItems / $itemsPerPage);
$offset = ($currentPage - 1) * $itemsPerPage;

// Get current page items
$currentPageItems = array_slice($files, $offset, $itemsPerPage);
?>

<!-- Add CSS for file previews -->
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

/* Modal styles */
#renameModal {
    transition: opacity 0.2s ease;
}

#renameModal.hidden {
    opacity: 0;
    pointer-events: none;
}

#renameModal:not(.hidden) {
    opacity: 1;
    pointer-events: auto;
}

#renameModal .bg-white {
    transform: scale(0.95);
    transition: transform 0.2s ease;
}

#renameModal:not(.hidden) .bg-white {
    transform: scale(1);
}

/* Upload progress styles */
.upload-progress {
    display: none;
    margin-top: 1rem;
}

.upload-progress.show {
    display: block;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background-color: #f3f4f6;
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background-color: #3b82f6;
    transition: width 0.3s ease;
}

.upload-status {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
}
</style>

<h2 class="text-2xl font-bold mb-6 fira-code">File Manager</h2>

<!-- Display any messages from the backend -->
<?php if (!empty($error)): ?>
    <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="bg-green-100 text-green-700 p-4 rounded mb-4"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- Enhanced Upload Form with Multiple File Support -->
<form method="POST" enctype="multipart/form-data" class="mb-6 p-4 border rounded bg-gray-50" id="uploadForm">
    <input type="hidden" name="action" value="upload_file">
    <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
    
    <div class="mb-4">
        <label for="files" class="block text-sm font-medium text-gray-700 mb-2">Select Files:</label>
        <input type="file" name="files[]" id="files" multiple required 
               class="border rounded px-3 py-2 w-full" 
               accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.zip,.svg,.txt,.md">
        <p class="text-sm text-gray-500 mt-1">
            You can select multiple files. Allowed: JPG, PNG, GIF, WebP, PDF, ZIP, SVG, TXT, MD. Max 10MB per file.
        </p>
    </div>
    
    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
        Upload Files
    </button>
    
    <!-- Upload Progress -->
    <div class="upload-progress" id="uploadProgress">
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill" style="width: 0%"></div>
        </div>
        <div class="upload-status" id="uploadStatus">Ready to upload...</div>
    </div>
</form>

<!-- Bulk Actions Bar -->
<div id="bulkActionsBar" class="hidden mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <span id="selectedCount" class="text-sm font-medium text-gray-700">0 files selected</span>
            <button id="selectAllBtn" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Select All</button>
            <button id="deselectAllBtn" class="text-sm text-gray-600 hover:text-gray-800 font-medium">Deselect All</button>
        </div>
        <div class="flex items-center space-x-3">
            <button id="bulkDeleteBtn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                <svg class="-ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Delete Selected
            </button>
        </div>
    </div>
</div>

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

<!-- File Grid View -->
<div id="grid-view" class="file-grid">
    <?php foreach ($currentPageItems as $file): ?>
        <div class="file-card">
            <div class="flex items-start mb-2">
                <input type="checkbox" name="selected_files[]" value="<?= htmlspecialchars($file['name']) ?>" class="item-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mr-2 mt-1">
                <?php 
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                    <img src="<?= htmlspecialchars($file['url']) ?>" alt="<?= htmlspecialchars($file['name']) ?>" class="file-preview">
                <?php else: ?>
                    <div class="file-icon">
                        <?php
                        $icon = match($ext) {
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
            </div>
            <div class="file-info">
                <div class="file-name"><?= htmlspecialchars($file['name']) ?></div>
                <div class="file-meta">
                    <?= number_format($file['size']/1024, 2) ?> KB
                </div>
            </div>
            <div class="file-actions">
                <a href="<?= htmlspecialchars($file['url']) ?>" target="_blank" class="bg-blue-500 text-white hover:bg-blue-600">View</a>
                <button onclick="showRenameForm('<?= htmlspecialchars($file['name']) ?>')" class="bg-yellow-500 text-white hover:bg-yellow-600 w-full">Rename</button>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this file?')">
                    <input type="hidden" name="action" value="delete_file">
                    <input type="hidden" name="filename" value="<?= htmlspecialchars($file['name']) ?>">
                    <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                    <button type="submit" class="bg-red-500 text-white hover:bg-red-600 w-full">Delete</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- File List View -->
<div id="list-view" class="hidden">
    <table class="w-full border-collapse">
        <thead>
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <input type="checkbox" id="selectAllCheckbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                </th>
                <th class="border-b py-2 text-left">File</th>
                <th class="border-b py-2 text-left">Type</th>
                <th class="border-b py-2 text-left">Size</th>
                <th class="border-b py-2 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($currentPageItems as $file): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="checkbox" name="selected_files[]" value="<?= htmlspecialchars($file['name']) ?>" class="item-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                </td>
                <td class="py-2 border-b">
                    <div class="flex items-center">
                        <?php 
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                            <img src="<?= htmlspecialchars($file['url']) ?>" alt="<?= htmlspecialchars($file['name']) ?>" class="file-preview">
                        <?php else: ?>
                            <div class="file-icon">
                                <?php
                                $icon = match($ext) {
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
                        <button onclick="showRenameForm('<?= htmlspecialchars($file['name']) ?>')" class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">Rename</button>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this file?')">
                            <input type="hidden" name="action" value="delete_file">
                            <input type="hidden" name="filename" value="<?= htmlspecialchars($file['name']) ?>">
                            <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                            <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="mt-6 flex items-center justify-between">
    <div class="flex-1 flex justify-between sm:hidden">
        <?php if ($currentPage > 1): ?>
            <a href="?action=files&page=<?php echo $currentPage - 1; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Previous
            </a>
        <?php endif; ?>
        <?php if ($currentPage < $totalPages): ?>
            <a href="?action=files&page=<?php echo $currentPage + 1; ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Next
            </a>
        <?php endif; ?>
    </div>
    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-gray-700">
                Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $itemsPerPage, $totalItems); ?></span> of <span class="font-medium"><?php echo $totalItems; ?></span> results
            </p>
        </div>
        <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?action=files&page=<?php echo $currentPage - 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Previous</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                <?php endif; ?>
                
                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                
                if ($startPage > 1): ?>
                    <a href="?action=files&page=1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>
                    <?php if ($startPage > 2): ?>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a href="?action=files&page=<?php echo $i; ?>" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $currentPage ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                    <?php endif; ?>
                    <a href="?action=files&page=<?php echo $totalPages; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"><?php echo $totalPages; ?></a>
                <?php endif; ?>
                
                <?php if ($currentPage < $totalPages): ?>
                    <a href="?action=files&page=<?php echo $currentPage + 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Next</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Bulk Delete Confirmation Modal -->
<div id="bulkDeleteModal" class="fixed z-20 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                    <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Bulk Delete Files</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500">Are you sure you want to delete <span id="bulkDeleteCount" class="font-medium">0</span> selected files? This action cannot be undone.</p>
                    </div>
                </div>
            </div>
            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                <form id="bulkDeleteForm" method="POST" class="inline-block" data-no-ajax="true">
                    <input type="hidden" name="action" value="bulk_delete_files">
                    <input type="hidden" name="selected_files" id="bulkDeleteFiles">
                    <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">Delete Selected</button>
                </form>
                <button type="button" onclick="closeBulkDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Rename Modal -->
<div id="renameModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 class="text-lg font-semibold mb-4">Rename File</h3>
            <form method="POST" id="renameForm">
                <input type="hidden" name="action" value="rename_file">
                <input type="hidden" name="old_filename" id="oldFilename">
                <div class="mb-4">
                    <label for="new_filename" class="block text-sm font-medium text-gray-700 mb-2">New Filename:</label>
                    <input type="text" name="new_filename" id="newFilename" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                <div class="flex gap-3">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Rename</button>
                    <button type="button" onclick="hideRenameForm()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Bulk operations functionality
let selectedFiles = new Set();

function updateBulkActionsBar() {
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    const selectedCount = document.getElementById('selectedCount');
    const bulkDeleteCount = document.getElementById('bulkDeleteCount');
    
    if (selectedFiles.size > 0) {
        bulkActionsBar.classList.remove('hidden');
        selectedCount.textContent = `${selectedFiles.size} file${selectedFiles.size !== 1 ? 's' : ''} selected`;
        bulkDeleteCount.textContent = selectedFiles.size;
    } else {
        bulkActionsBar.classList.add('hidden');
    }
}

function toggleFileSelection(filename) {
    if (selectedFiles.has(filename)) {
        selectedFiles.delete(filename);
    } else {
        selectedFiles.add(filename);
    }
    updateBulkActionsBar();
}

function selectAllFiles() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
        selectedFiles.add(checkbox.value);
    });
    updateBulkActionsBar();
}

function deselectAllFiles() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        selectedFiles.delete(checkbox.value);
    });
    updateBulkActionsBar();
}

function showBulkDeleteModal() {
    if (selectedFiles.size === 0) return;
    
    const bulkDeleteFiles = document.getElementById('bulkDeleteFiles');
    bulkDeleteFiles.value = Array.from(selectedFiles).join(',');
    
    document.getElementById('bulkDeleteModal').classList.remove('hidden');
}

function closeBulkDeleteModal() {
    document.getElementById('bulkDeleteModal').classList.add('hidden');
}

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

// Initialize view state and bulk operations
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
    
    // Select all checkbox
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            if (this.checked) {
                selectAllFiles();
            } else {
                deselectAllFiles();
            }
        });
    }
    
    // Individual file checkboxes
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            toggleFileSelection(this.value);
        });
    });
    
    // Bulk action buttons
    document.getElementById('selectAllBtn').addEventListener('click', selectAllFiles);
    document.getElementById('deselectAllBtn').addEventListener('click', deselectAllFiles);
    document.getElementById('bulkDeleteBtn').addEventListener('click', showBulkDeleteModal);
    
    // Handle file selection for multiple files
    const fileInput = document.getElementById('files');
    fileInput.addEventListener('change', function() {
        const files = this.files;
        if (files.length > 1) {
            document.getElementById('uploadStatus').textContent = `${files.length} files selected for upload`;
        } else if (files.length === 1) {
            document.getElementById('uploadStatus').textContent = `1 file selected: ${files[0].name}`;
        } else {
            document.getElementById('uploadStatus').textContent = 'Ready to upload...';
        }
    });
});

// Rename file functions
function showRenameForm(filename) {
    document.getElementById('oldFilename').value = filename;
    document.getElementById('newFilename').value = filename;
    document.getElementById('renameModal').classList.remove('hidden');
    document.getElementById('newFilename').focus();
    document.getElementById('newFilename').select();
}

function hideRenameForm() {
    document.getElementById('renameModal').classList.add('hidden');
    document.getElementById('oldFilename').value = '';
    document.getElementById('newFilename').value = '';
}

// Close modal when clicking outside
document.getElementById('renameModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideRenameForm();
    }
});

// Handle escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideRenameForm();
    }
});
</script>
