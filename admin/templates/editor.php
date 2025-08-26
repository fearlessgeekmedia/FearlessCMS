<!-- Page Editor -->
<div class="space-y-8">
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="save_page">
        <input type="hidden" name="original_slug" value="<?= htmlspecialchars($page['slug'] ?? '') ?>">
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
            <input type="text" name="title" value="<?= htmlspecialchars($page['title'] ?? '') ?>" required
                   class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-green-500 focus:border-green-500">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
            <input type="text" name="slug" value="<?= htmlspecialchars($page['slug'] ?? '') ?>" required
                   class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-green-500 focus:border-green-500">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Parent Page</label>
            <select name="parent" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-green-500 focus:border-green-500">
                <?= generate_parent_options($pages, $page['parent'] ?? '', $page['slug'] ?? '') ?>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Template</label>
            <select name="template" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-green-500 focus:border-green-500">
                <option value="page.php" <?= ($page['template'] ?? '') === 'page.php' ? 'selected' : '' ?>>Default Page</option>
                <option value="home.php" <?= ($page['template'] ?? '') === 'home.php' ? 'selected' : '' ?>>Home Page</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
            <textarea name="content" rows="20" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-green-500 focus:border-green-500 font-mono"><?= $page['content'] ?? '' ?></textarea>
        </div>
        
        <div class="flex justify-end space-x-2">
            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Save Page</button>
            <?php if (isset($page['slug'])): ?>
            <button type="button" onclick="deletePage('<?= htmlspecialchars($page['slug']) ?>')" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Delete Page</button>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
function deletePage(slug) {
    if (confirm('Are you sure you want to delete this page?')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_content">
            <input type="hidden" name="path" value="${slug}">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script> 