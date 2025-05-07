<?php
// Remove plugin registration
function fcms_render_widget_manager() {
    error_log('Starting widget manager render function');
    $adminWidgetsFile = ADMIN_CONFIG_DIR . '/widgets.json';
    error_log('Admin widgets file path: ' . $adminWidgetsFile);
    
    // Load widgets from admin config file
    $widgets = file_exists($adminWidgetsFile) ? json_decode(file_get_contents($adminWidgetsFile), true) ?? [] : [];
    
    // Ensure widgets have the correct structure
    foreach ($widgets as $id => &$sidebar) {
        if (!isset($sidebar['id'])) {
            $sidebar['id'] = $id;
        }
        if (!isset($sidebar['widgets'])) {
            $sidebar['widgets'] = [];
        }
        if (!isset($sidebar['classes'])) {
            $sidebar['classes'] = 'sidebar-' . $id;
        }
    }

    $currentSidebar = $_GET['sidebar'] ?? array_key_first($widgets);
    error_log('Current sidebar: ' . $currentSidebar);
    if (!isset($widgets[$currentSidebar])) {
        $currentSidebar = null;
        error_log('Current sidebar not found, defaulting to null');
    }

    // Generate sidebar selection HTML
    ob_start();
    ?>
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Select Sidebar</label>
        <div class="flex gap-2">
            <select id="sidebar-select" class="flex-1 border rounded px-2 py-1" onchange="window.location.href='?action=manage_widgets&sidebar=' + this.value">
                <?php if (empty($widgets)): ?>
                    <option value="">No sidebars available</option>
                <?php else: ?>
                    <?php 
                    // Sort sidebars by ID for consistent display
                    ksort($widgets);
                    foreach ($widgets as $id => $sidebar): 
                    ?>
                    <option value="<?= htmlspecialchars($id) ?>" <?= $id === $currentSidebar ? 'selected' : '' ?>>
                        <?= htmlspecialchars($id) ?>
                    </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <?php if ($currentSidebar): ?>
                <button onclick="deleteSidebar('<?= htmlspecialchars($currentSidebar) ?>')" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Delete Sidebar</button>
            <?php endif; ?>
        </div>
    </div>
    <?php
    $sidebarSelection = ob_get_clean();

    // Generate widget list HTML
    ob_start();
    if ($currentSidebar && isset($widgets[$currentSidebar])) {
        if (!empty($widgets[$currentSidebar]['widgets'])) {
            foreach ($widgets[$currentSidebar]['widgets'] as $widget) {
                ?>
                <div class="border rounded-lg p-4 mb-4" data-widget-id="<?= htmlspecialchars($widget['id']) ?>">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h4 class="text-lg font-medium"><?= htmlspecialchars($widget['title']) ?></h4>
                            <p class="text-sm text-gray-600">Type: <?= htmlspecialchars($widget['type']) ?></p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="editWidget('<?= htmlspecialchars($widget['id']) ?>')" class="text-blue-500 hover:text-blue-700">Edit</button>
                            <button onclick="deleteWidget('<?= htmlspecialchars($widget['id']) ?>', '<?= htmlspecialchars($currentSidebar) ?>')" class="text-red-500 hover:text-red-700">Delete</button>
                        </div>
                    </div>
                    <div class="prose max-w-none">
                        <?= $widget['type'] === 'markdown' ? parseMarkdown($widget['content']) : $widget['content'] ?>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<div class="text-gray-500 italic">No widgets in this sidebar yet.</div>';
        }
    } else {
        echo '<div class="text-gray-500 italic">Please create a sidebar first.</div>';
    }
    $widgetList = ob_get_clean();

    // Return the content in the format expected by the template
    return [
        'sidebar_selection' => $sidebarSelection,
        'widget_list' => $widgetList,
        'current_sidebar' => $currentSidebar ?? ''
    ];
} 