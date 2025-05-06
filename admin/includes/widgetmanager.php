<?php
// Remove plugin registration
function fcms_render_widget_manager() {
    error_log('Starting widget manager render function');
    $adminWidgetsFile = ADMIN_CONFIG_DIR . '/widgets.json';
    error_log('Admin widgets file path: ' . $adminWidgetsFile);
    
    // Load widgets from admin config file
    $widgets = file_exists($adminWidgetsFile) ? json_decode(file_get_contents($adminWidgetsFile), true) ?? [] : [];
    
    // If no widgets exist, create default structure
    if (empty($widgets)) {
        error_log('No widgets found, creating default structure');
        $widgets = [
            'left-sidebar' => [
                'name' => 'Left Sidebar',
                'widgets' => []
            ]
        ];
        file_put_contents($adminWidgetsFile, json_encode($widgets, JSON_PRETTY_PRINT));
    }
    
    // Ensure widgets have the correct structure
    foreach ($widgets as $id => &$sidebar) {
        if (!isset($sidebar['name'])) {
            $sidebar['name'] = ucwords(str_replace(['-', '_'], ' ', $id));
        }
        if (!isset($sidebar['widgets'])) {
            $sidebar['widgets'] = [];
        }
        if (!isset($sidebar['id'])) {
            $sidebar['id'] = $id;
        }
    }

    $currentSidebar = $_GET['sidebar'] ?? array_key_first($widgets);
    error_log('Current sidebar: ' . $currentSidebar);
    if (!isset($widgets[$currentSidebar])) {
        $currentSidebar = array_key_first($widgets);
        error_log('Current sidebar not found, defaulting to: ' . $currentSidebar);
    }

    // Generate sidebar selection HTML
    ob_start();
    ?>
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Select Sidebar</label>
        <div class="flex gap-2">
            <select id="sidebar-select" class="flex-1 border rounded px-2 py-1" onchange="window.location.href='?action=manage_widgets&sidebar=' + this.value">
                <?php 
                // Sort sidebars by name and ID for consistent display
                uasort($widgets, function($a, $b) {
                    $nameCompare = strcasecmp($a['name'], $b['name']);
                    if ($nameCompare === 0) {
                        return strcasecmp($a['id'] ?? '', $b['id'] ?? '');
                    }
                    return $nameCompare;
                });
                foreach ($widgets as $id => $sidebar): 
                ?>
                <option value="<?= htmlspecialchars($id) ?>" <?= $id === $currentSidebar ? 'selected' : '' ?>>
                    <?= htmlspecialchars($sidebar['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button onclick="deleteSidebar('<?= htmlspecialchars($currentSidebar) ?>')" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Delete Sidebar</button>
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
    }
    $widgetList = ob_get_clean();

    // Return the content in the format expected by the template
    return [
        'sidebar_selection' => $sidebarSelection,
        'widget_list' => $widgetList,
        'current_sidebar' => $currentSidebar
    ];
} 