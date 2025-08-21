<?php
/**
 * Example Plugin: SQLite Usage Demo
 * This demonstrates how to use the SQLite connector in your own plugins
 */

// Example plugin initialization
function sqlite_example_init() {
    // Register admin section
    fcms_register_admin_section('sqlite_example', [
        'label' => 'SQLite Example',
        'menu_order' => 37,
        'parent' => 'manage_plugins',
        'render_callback' => 'sqlite_example_admin_page'
    ]);
    
    // Create tables on plugin activation
    sqlite_example_create_tables();
}

// Create necessary database tables
function sqlite_example_create_tables() {
    $pdo = fcms_do_hook('database_connect');
    if (!$pdo) {
        error_log("SQLite Example: Could not connect to database to create tables.");
        return false;
    }

    $queries = [
        "CREATE TABLE IF NOT EXISTS `example_notes` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `title` TEXT NOT NULL,
            `content` TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS `example_categories` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `name` TEXT NOT NULL UNIQUE,
            `description` TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        )"
    ];

    foreach ($queries as $query) {
        try {
            $pdo->exec($query);
        } catch (PDOException $e) {
            error_log("SQLite Example: Failed to execute query: " . $e->getMessage());
        }
    }
}

// Add a new note
function sqlite_example_add_note($title, $content) {
    $stmt = fcms_do_hook('database_query', 
        'INSERT INTO example_notes (title, content) VALUES (?, ?)', 
        [$title, $content]
    );
    return $stmt !== false;
}

// Get all notes
function sqlite_example_get_notes() {
    $stmt = fcms_do_hook('database_query', 
        'SELECT * FROM example_notes ORDER BY created_at DESC'
    );
    return $stmt ? $stmt->fetchAll() : [];
}

// Get a specific note
function sqlite_example_get_note($id) {
    $stmt = fcms_do_hook('database_query', 
        'SELECT * FROM example_notes WHERE id = ?', 
        [$id]
    );
    return $stmt ? $stmt->fetch() : null;
}

// Update a note
function sqlite_example_update_note($id, $title, $content) {
    $stmt = fcms_do_hook('database_query', 
        'UPDATE example_notes SET title = ?, content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', 
        [$title, $content, $id]
    );
    return $stmt !== false;
}

// Delete a note
function sqlite_example_delete_note($id) {
    $stmt = fcms_do_hook('database_query', 
        'DELETE FROM example_notes WHERE id = ?', 
        [$id]
    );
    return $stmt !== false;
}

// Admin page callback
function sqlite_example_admin_page() {
    $success_message = '';
    $error_message = '';
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_note':
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['content'] ?? '');
                
                if (!empty($title)) {
                    if (sqlite_example_add_note($title, $content)) {
                        $success_message = 'Note added successfully!';
                    } else {
                        $error_message = 'Failed to add note.';
                    }
                } else {
                    $error_message = 'Title is required.';
                }
                break;
                
            case 'delete_note':
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    if (sqlite_example_delete_note($id)) {
                        $success_message = 'Note deleted successfully!';
                    } else {
                        $error_message = 'Failed to delete note.';
                    }
                }
                break;
        }
    }
    
    // Get all notes for display
    $notes = sqlite_example_get_notes();
    
    // Start output buffer
    ob_start();
    ?>
    
    <?php if ($success_message): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>
    
    <h2 class="text-2xl font-bold mb-6">SQLite Example Plugin</h2>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Add Note Form -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold mb-4">Add New Note</h3>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_note">
                
                <div>
                    <label class="block font-medium mb-1">Title</label>
                    <input type="text" name="title" required
                           class="w-full border rounded px-3 py-2"
                           placeholder="Enter note title">
                </div>
                
                <div>
                    <label class="block font-medium mb-1">Content</label>
                    <textarea name="content" rows="4"
                              class="w-full border rounded px-3 py-2"
                              placeholder="Enter note content"></textarea>
                </div>
                
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                    Add Note
                </button>
            </form>
        </div>
        
        <!-- Notes List -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold mb-4">Notes (<?= count($notes) ?>)</h3>
            
            <?php if (empty($notes)): ?>
                <p class="text-gray-500">No notes yet. Add your first note!</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($notes as $note): ?>
                        <div class="border rounded p-3">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h4 class="font-medium"><?= htmlspecialchars($note['title']) ?></h4>
                                    <?php if (!empty($note['content'])): ?>
                                        <p class="text-sm text-gray-600 mt-1">
                                            <?= htmlspecialchars(substr($note['content'], 0, 100)) ?>
                                            <?= strlen($note['content']) > 100 ? '...' : '' ?>
                                        </p>
                                    <?php endif; ?>
                                    <p class="text-xs text-gray-500 mt-2">
                                        Created: <?= htmlspecialchars($note['created_at']) ?>
                                    </p>
                                </div>
                                <form method="POST" class="ml-2">
                                    <input type="hidden" name="action" value="delete_note">
                                    <input type="hidden" name="id" value="<?= $note['id'] ?>">
                                    <button type="submit" 
                                            onclick="return confirm('Are you sure you want to delete this note?')"
                                            class="text-red-500 hover:text-red-700 text-sm">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="mt-6 bg-gray-50 p-4 rounded">
        <h3 class="font-semibold mb-2">How This Works</h3>
        <p class="text-sm text-gray-600">
            This example demonstrates how to use the SQLite connector in your plugins. 
            It shows database operations like INSERT, SELECT, UPDATE, and DELETE using 
            the hook system. The plugin creates its own tables and manages data independently.
        </p>
    </div>
    
    <?php
    return ob_get_clean();
}

// Initialize the example plugin
sqlite_example_init();
?> 