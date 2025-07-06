<?php
// Load users from config file
$usersFile = CONFIG_DIR . '/users.json';
$users = [];
if (file_exists($usersFile)) {
    $users = json_decode(file_get_contents($usersFile), true) ?? [];
}

// Debug output
error_log("DEBUG: users.php template loaded - Found " . count($users) . " users");

if (!isset($_GET['edit'])): ?>
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
        <div>
            <label class="block mb-2">Permissions:</label>
            <div class="space-y-2">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="permissions[]" value="manage_content" class="form-checkbox">
                    <span class="ml-2">Manage Content</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="permissions[]" value="manage_plugins" class="form-checkbox">
                    <span class="ml-2">Manage Plugins</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="permissions[]" value="manage_themes" class="form-checkbox">
                    <span class="ml-2">Manage Themes</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="permissions[]" value="manage_menus" class="form-checkbox">
                    <span class="ml-2">Manage Menus</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="permissions[]" value="manage_settings" class="form-checkbox">
                    <span class="ml-2">Manage Settings</span>
                </label>
            </div>
        </div>
        <div>
            <label class="block mb-2">Role:</label>
            <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded">
                <?php
                $rolesFile = PROJECT_ROOT . '/config/roles.json';
                if (file_exists($rolesFile)) {
                    $roles = json_decode(file_get_contents($rolesFile), true);
                    foreach ($roles as $roleKey => $role) {
                        echo '<option value="' . htmlspecialchars($roleKey) . '">' . htmlspecialchars($role['label']) . '</option>';
                    }
                }
                ?>
            </select>
        </div>
        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Add User</button>
    </form>
</div>
<?php endif; ?>

<?php if (isset($_GET['edit'])): ?>
    <div class="mb-8">
        <h3 class="text-lg font-medium mb-4">Edit User</h3>
        <?php
        // Debug output
        $currentUser = null;
        foreach ($users as $user) {
            if ($user['username'] === $_GET['edit']) {
                $currentUser = $user;
                break;
            }
        }
        error_log("Edit form - Current user data: " . print_r($currentUser, true));
        ?>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="username" value="<?= htmlspecialchars($_GET['edit']) ?>">
            <div>
                <label class="block mb-2">Username:</label>
                <input type="text" value="<?= htmlspecialchars($_GET['edit']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-100" readonly>
            </div>
            <div>
                <label class="block mb-2">New Username (leave blank to keep current):</label>
                <input type="text" name="new_username" class="w-full px-3 py-2 border border-gray-300 rounded" placeholder="Leave blank to keep current">
            </div>
            <div>
                <label class="block mb-2">New Password (leave blank to keep current):</label>
                <input type="password" name="new_password" class="w-full px-3 py-2 border border-gray-300 rounded">
            </div>
            <div>
                <label class="block mb-2">Permissions:</label>
                <div class="space-y-2">
                    <?php
                    $userPermissions = $currentUser['permissions'] ?? [];
                    error_log("Edit form - User permissions: " . print_r($userPermissions, true));
                    $allPermissions = ['manage_content', 'manage_plugins', 'manage_themes', 'manage_menus', 'manage_settings'];
                    foreach ($allPermissions as $perm):
                    ?>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="permissions[]" value="<?= $perm ?>" class="form-checkbox" <?= in_array($perm, $userPermissions) ? 'checked' : '' ?>>
                        <span class="ml-2"><?= ucwords(str_replace('_', ' ', $perm)) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <label class="block mb-2">Role:</label>
                <select name="user_role" class="w-full px-3 py-2 border border-gray-300 rounded">
                    <?php
                    $rolesFile = PROJECT_ROOT . '/config/roles.json';
                    if (file_exists($rolesFile)) {
                        $roles = json_decode(file_get_contents($rolesFile), true);
                        foreach ($roles as $roleKey => $role) {
                            $selected = (isset($currentUser['role']) && $currentUser['role'] === $roleKey) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($roleKey) . '" ' . $selected . '>' . htmlspecialchars($role['label']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Save Changes</button>
            <a href="?action=users" class="inline-block ml-2 text-gray-600 hover:text-gray-800">Cancel</a>
        </form>
    </div>
<?php endif; ?>

<h3 class="text-lg font-medium mb-4">Existing Users</h3>
<table class="w-full">
    <thead>
        <tr>
            <th class="text-left py-2 px-4 border-b">Username</th>
            <th class="text-left py-2 px-4 border-b">Permissions</th>
            <th class="text-left py-2 px-4 border-b">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td class="py-2 px-4 border-b"><?= htmlspecialchars($user['username']) ?></td>
                <td class="py-2 px-4 border-b">
                    <?php
                    $permissions = $user['permissions'] ?? [];
                    echo implode(', ', array_map(function($p) {
                        return ucwords(str_replace('_', ' ', $p));
                    }, $permissions));
                    ?>
                </td>
                <td class="py-2 px-4 border-b">
                    <a href="?action=users&edit=<?= urlencode($user['username']) ?>" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 mr-2">Edit</a>
                    <form method="POST" style="display:inline" onsubmit="console.log('Delete form submitted for user: <?= htmlspecialchars($user['username']) ?>');">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($user['username']) ?>">
                        <button type="submit" onclick="return confirm('Are you sure you want to delete this user?')" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
