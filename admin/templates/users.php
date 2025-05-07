<div class="mb-8">
    <h3 class="text-lg font-medium mb-4">Add New User</h3>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="add_user">
        <div>
            <input type="text" name="new_username" required class="w-full px-3 py-2 border border-gray-300 rounded" placeholder="Username">
        </div>
        <div>
            <input type="password" name="new_user_password" required class="w-full px-3 py-2 border border-gray-300 rounded" placeholder="Password">
        </div>
        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Add User</button>
    </form>
</div>
<h3 class="text-lg font-medium mb-4">Existing Users</h3>
<table class="w-full">
    <thead>
        <tr>
            <th class="text-left py-2 px-4 border-b">Username</th>
            <th class="text-left py-2 px-4 border-b">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($user['username']) ?>">
                        <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
