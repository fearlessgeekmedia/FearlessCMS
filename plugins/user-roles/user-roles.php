<?php
/**
 * Plugin Name: User Roles
 * Description: Adds role-based access control to FearlessCMS
 * Version: 1.0
 * Author: FearlessCMS
 */

// Define plugin constants
define('USER_ROLES_PLUGIN_DIR', dirname(__FILE__));
define('USER_ROLES_CONFIG_FILE', dirname(dirname(__DIR__)) . '/config/roles.json');

// Default roles and capabilities
$default_roles = [
    'administrator' => [
        'name' => 'Administrator',
        'capabilities' => [
            'manage_users' => true,
            'manage_roles' => true,
            'manage_themes' => true,
            'manage_plugins' => true,
            'manage_menus' => true,
            'manage_widgets' => true,
            'manage_content' => true,
            'manage_files' => true,
            'manage_settings' => true
        ]
    ],
    'editor' => [
        'name' => 'Editor',
        'capabilities' => [
            'manage_users' => false,
            'manage_roles' => false,
            'manage_themes' => false,
            'manage_plugins' => false,
            'manage_menus' => false,
            'manage_widgets' => false,
            'manage_content' => true,
            'manage_files' => true,
            'manage_settings' => false
        ]
    ],
    'author' => [
        'name' => 'Author',
        'capabilities' => [
            'manage_users' => false,
            'manage_roles' => false,
            'manage_themes' => false,
            'manage_plugins' => false,
            'manage_menus' => false,
            'manage_widgets' => false,
            'manage_content' => true,
            'manage_files' => false,
            'manage_settings' => false
        ]
    ]
];

// Initialize roles if they don't exist
if (!file_exists(USER_ROLES_CONFIG_FILE)) {
    file_put_contents(USER_ROLES_CONFIG_FILE, json_encode($default_roles, JSON_PRETTY_PRINT));
}

// Helper functions
function get_roles() {
    if (!file_exists(USER_ROLES_CONFIG_FILE)) {
        return [];
    }
    return json_decode(file_get_contents(USER_ROLES_CONFIG_FILE), true);
}

function get_role($role_id) {
    $roles = get_roles();
    return $roles[$role_id] ?? null;
}

function user_has_capability($username, $capability) {
    $users = json_decode(file_get_contents(dirname(dirname(__DIR__)) . '/admin/config/users.json'), true);
    $user = null;
    foreach ($users as $u) {
        if ($u['username'] === $username) {
            $user = $u;
            break;
        }
    }
    
    if (!$user) return false;
    
    // If user is admin (username is 'admin'), grant all capabilities
    if ($user['username'] === 'admin') {
        return true;
    }
    
    // Get user's role, default to 'author' if not set
    $user_role = $user['role'] ?? 'author';
    
    $role = get_role($user_role);
    if (!$role) return false;
    
    return $role['capabilities'][$capability] ?? false;
}

// Register admin section
fcms_register_admin_section('manage_roles', [
    'label' => 'Roles',
    'menu_order' => 60,
    'render_callback' => 'render_roles_management'
]);

function render_roles_management() {
    if (!user_has_capability($_SESSION['username'], 'manage_roles')) {
        return '<div class="bg-red-100 text-red-700 p-4 rounded">You do not have permission to manage roles.</div>';
    }

    $roles = get_roles();
    if (!is_array($roles)) {
        $roles = $default_roles; // Use default roles if current roles are invalid
        file_put_contents(USER_ROLES_CONFIG_FILE, json_encode($default_roles, JSON_PRETTY_PRINT));
    }

    $html = '<div class="mb-8">';
    $html .= '<h2 class="text-2xl font-bold mb-6 fira-code">Role Management</h2>';
    
    // Display existing roles
    foreach ($roles as $role_id => $role) {
        if (!is_array($role) || !isset($role['name']) || !isset($role['capabilities'])) {
            continue; // Skip invalid role entries
        }

        $html .= '<div class="border rounded-lg p-4 mb-4">';
        $html .= '<h3 class="text-lg font-medium mb-2">' . htmlspecialchars($role['name']) . '</h3>';
        $html .= '<p class="text-sm text-gray-600 mb-2">Role ID: ' . htmlspecialchars($role_id) . '</p>';
        $html .= '<div class="mb-4">';
        $html .= '<h4 class="font-medium mb-2">Capabilities:</h4>';
        $html .= '<ul class="list-disc list-inside">';
        foreach ($role['capabilities'] as $cap => $enabled) {
            if ($enabled) {
                $html .= '<li class="text-sm text-gray-600">' . htmlspecialchars($cap) . '</li>';
            }
        }
        $html .= '</ul>';
        $html .= '</div>';
        $html .= '<button onclick="editRole(\'' . htmlspecialchars($role_id) . '\')" class="bg-blue-500 text-white px-3 py-1 rounded text-sm">Edit Role</button>';
        if ($role_id !== 'administrator') {
            $html .= ' <button onclick="deleteRole(\'' . htmlspecialchars($role_id) . '\')" class="bg-red-500 text-white px-3 py-1 rounded text-sm">Delete Role</button>';
        }
        $html .= '</div>';
    }
    
    // Add new role button
    $html .= '<button onclick="showNewRoleModal()" class="bg-green-500 text-white px-4 py-2 rounded">Add New Role</button>';
    
    // Role editor modal
    $html .= '
    <div id="roleModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h3 class="text-xl font-bold mb-4">Edit Role</h3>
            <form id="role-form" class="space-y-4" onsubmit="return false;">
                <input type="hidden" id="role-id" value="">
                <div>
                    <label class="block mb-1">Role Name:</label>
                    <input type="text" id="role-name" class="w-full px-3 py-2 border border-gray-300 rounded" required>
                </div>
                <div>
                    <label class="block mb-1">Capabilities:</label>
                    <div class="space-y-2">';
    
    $all_capabilities = [
        'manage_users' => 'Manage Users',
        'manage_roles' => 'Manage Roles',
        'manage_themes' => 'Manage Themes',
        'manage_plugins' => 'Manage Plugins',
        'manage_menus' => 'Manage Menus',
        'manage_widgets' => 'Manage Widgets',
        'manage_content' => 'Manage Content',
        'manage_files' => 'Manage Files',
        'manage_settings' => 'Manage Settings'
    ];
    
    foreach ($all_capabilities as $cap => $label) {
        $html .= '<label class="flex items-center">
            <input type="checkbox" name="capabilities[]" value="' . htmlspecialchars($cap) . '" class="mr-2">
            ' . htmlspecialchars($label) . '
        </label>';
    }
    
    $html .= '
                    </div>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeRoleModal()" class="bg-gray-400 text-white px-4 py-2 rounded">Cancel</button>
                    <button type="button" onclick="saveRole()" class="bg-blue-500 text-white px-4 py-2 rounded">Save</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    let roles = ' . json_encode($roles) . ';
    
    function showNewRoleModal() {
        document.getElementById("role-id").value = "";
        document.getElementById("role-name").value = "";
        document.querySelectorAll("input[name=\'capabilities[]\']").forEach(cb => cb.checked = false);
        document.getElementById("roleModal").classList.remove("hidden");
    }
    
    function editRole(roleId) {
        const role = roles[roleId];
        document.getElementById("role-id").value = roleId;
        document.getElementById("role-name").value = role.name;
        document.querySelectorAll("input[name=\'capabilities[]\']").forEach(cb => {
            cb.checked = role.capabilities[cb.value] || false;
        });
        document.getElementById("roleModal").classList.remove("hidden");
    }
    
    function closeRoleModal() {
        document.getElementById("roleModal").classList.add("hidden");
    }
    
    function saveRole() {
        const roleId = document.getElementById("role-id").value;
        const roleName = document.getElementById("role-name").value;
        const capabilities = {};
        document.querySelectorAll("input[name=\'capabilities[]\']").forEach(cb => {
            capabilities[cb.value] = cb.checked;
        });
        
        if (!roleId) {
            // New role
            const newRoleId = roleName.toLowerCase().replace(/[^a-z0-9]/g, "_");
            roles[newRoleId] = {
                name: roleName,
                capabilities: capabilities
            };
        } else {
            // Edit existing role
            roles[roleId].name = roleName;
            roles[roleId].capabilities = capabilities;
        }
        
        fetch("", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "action=save_roles&roles=" + encodeURIComponent(JSON.stringify(roles))
        }).then(() => location.reload());
    }
    
    function deleteRole(roleId) {
        if (confirm("Are you sure you want to delete this role?")) {
            delete roles[roleId];
            fetch("", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "action=save_roles&roles=" + encodeURIComponent(JSON.stringify(roles))
            }).then(() => location.reload());
        }
    }
    </script>';
    
    $html .= '</div>';
    return $html;
}

// Handle role management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_roles') {
    if (!user_has_capability($_SESSION['username'], 'manage_roles')) {
        $error = 'You do not have permission to manage roles';
    } else {
        $roles = json_decode($_POST['roles'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            file_put_contents(USER_ROLES_CONFIG_FILE, json_encode($roles, JSON_PRETTY_PRINT));
            $success = 'Roles updated successfully';
        } else {
            $error = 'Invalid roles data';
        }
    }
}

// Register permission hooks
fcms_register_permission_hook('check_permission', function($username, $action, $context) {
    // Admin user has all permissions
    if ($username === 'admin') {
        return true;
    }
    
    // Get user's role
    $users = json_decode(file_get_contents(ADMIN_CONFIG_DIR . '/users.json'), true);
    $user = array_filter($users, function($u) use ($username) {
        return $u['username'] === $username;
    });
    $user = reset($user);
    $role = $user['role'] ?? 'author';
    
    // Get role capabilities
    $rolesFile = CONFIG_DIR . '/roles.json';
    $roles = file_exists($rolesFile) ? json_decode(file_get_contents($rolesFile), true) : [];
    $capabilities = $roles[$role]['capabilities'] ?? [];
    
    // Check if the role has the required capability
    return $capabilities[$action] ?? false;
});

fcms_register_permission_hook('filter_admin_sections', function($username, $sections) {
    // Admin user can see all sections
    if ($username === 'admin') {
        return $sections;
    }
    
    // Get user's role
    $users = json_decode(file_get_contents(ADMIN_CONFIG_DIR . '/users.json'), true);
    $user = array_filter($users, function($u) use ($username) {
        return $u['username'] === $username;
    });
    $user = reset($user);
    $role = $user['role'] ?? 'author';
    
    // Get role capabilities
    $rolesFile = CONFIG_DIR . '/roles.json';
    $roles = file_exists($rolesFile) ? json_decode(file_get_contents($rolesFile), true) : [];
    $capabilities = $roles[$role]['capabilities'] ?? [];
    
    // Filter sections based on capabilities
    $filtered_sections = [];
    foreach ($sections as $id => $section) {
        $required_capability = $section['required_capability'] ?? null;
        if ($required_capability === null || ($capabilities[$required_capability] ?? false)) {
            $filtered_sections[$id] = $section;
        }
    }
    
    return $filtered_sections;
}); 