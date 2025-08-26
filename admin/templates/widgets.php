<?php
// Initialize widget management variables
$widgetsFile = ADMIN_CONFIG_DIR . '/widgets.json';
$widgets = file_exists($widgetsFile) ? json_decode(file_get_contents($widgetsFile), true) : [];

// Set current sidebar with default fallback
$current_sidebar = $_GET['sidebar'] ?? '';
if (empty($current_sidebar) && !empty($widgets)) {
    // Default to the first available sidebar
    $current_sidebar = array_keys($widgets)[0];
}

// Build sidebar selection dropdown
$sidebar_selection = '<select id="sidebar-select" class="w-full px-3 py-2 border border-gray-300 rounded" onchange="window.location.href=\'?action=manage_widgets&sidebar=\' + this.value">';
$sidebar_selection .= '<option value="">Select a sidebar...</option>';
foreach ($widgets as $sidebarId => $sidebar) {
    $selected = ($sidebarId === $current_sidebar) ? 'selected' : '';
    $sidebar_selection .= '<option value="' . htmlspecialchars($sidebarId) . '" ' . $selected . '>' . htmlspecialchars($sidebar['name'] ?? $sidebarId) . '</option>';
}
$sidebar_selection .= '</select>';

// Get widgets for current sidebar
$sidebarWidgets = [];
error_log("Widgets template - Current sidebar: " . $current_sidebar);
error_log("Widgets template - Available sidebars: " . print_r(array_keys($widgets), true));

if (!empty($current_sidebar) && isset($widgets[$current_sidebar])) {
    $sidebarWidgets = $widgets[$current_sidebar]['widgets'] ?? [];
    error_log("Widgets template - Found " . count($sidebarWidgets) . " widgets in sidebar");
    
    // Normalize widget structure - ensure it's always an array
    if (!is_array($sidebarWidgets)) {
        $sidebarWidgets = [];
    }
    
    // If widgets are indexed by keys instead of being a sequential array, convert them
    if (!empty($sidebarWidgets) && array_keys($sidebarWidgets) !== range(0, count($sidebarWidgets) - 1)) {
        $normalizedWidgets = [];
        foreach ($sidebarWidgets as $widget) {
            if (is_array($widget) && isset($widget['id'])) {
                $normalizedWidgets[] = $widget;
            }
        }
        $sidebarWidgets = $normalizedWidgets;
    }
}

// Build widget list for current sidebar
$widget_list = '';
error_log("Widgets template - Current sidebar: " . $current_sidebar);
error_log("Widgets template - Available widgets: " . print_r($widgets, true));

if (!empty($current_sidebar) && isset($widgets[$current_sidebar])) {
    $sidebarWidgets = $widgets[$current_sidebar]['widgets'] ?? [];
    error_log("Widgets template - Sidebar widgets: " . print_r($sidebarWidgets, true));
    
    if (empty($sidebarWidgets)) {
        $widget_list = '<p class="text-gray-500 text-center py-8">No widgets in this sidebar yet. Add one above!</p>';
    } else {
        foreach ($sidebarWidgets as $index => $widget) {
            error_log("Widgets template - Processing widget: " . print_r($widget, true));
            $widget_list .= '<div class="widget-item" data-id="' . htmlspecialchars($widget['id'] ?? $index) . '" data-content="' . htmlspecialchars($widget['content'] ?? '') . '" data-classes="' . htmlspecialchars($widget['classes'] ?? '') . '">';
            $widget_list .= '<div class="widget-header">';
            $widget_list .= '<div class="widget-drag-handle">';
            $widget_list .= '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">';
            $widget_list .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>';
            $widget_list .= '</svg>';
            $widget_list .= '</div>';
            $widget_list .= '<h4 class="widget-title">' . htmlspecialchars($widget['title'] ?? 'Untitled Widget') . '</h4>';
            $widget_list .= '<span class="text-sm text-gray-500">' . htmlspecialchars($widget['type'] ?? 'text') . '</span>';
            $widget_list .= '</div>';
            $widget_list .= '<div class="widget-content">' . htmlspecialchars(substr($widget['content'] ?? '', 0, 100)) . (strlen($widget['content'] ?? '') > 100 ? '...' : '') . '</div>';
            $widget_list .= '<div class="widget-actions">';
            $widget_list .= '<button class="edit-widget" data-id="' . htmlspecialchars($widget['id'] ?? $index) . '">Edit</button>';
            $widget_list .= '<button class="delete-widget" data-id="' . htmlspecialchars($widget['id'] ?? $index) . '">Delete</button>';
            $widget_list .= '</div>';
            $widget_list .= '</div>';
        }
    }
}
?>

<!-- Add Sortable.js in the head section -->
<script src="/admin/serve-js.php"></script>

<div class="space-y-6">
    <!-- Error Message -->
    <div id="error-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"></div>
    
    <!-- Success Message -->
    <div id="success-message" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"></div>

    <!-- Sidebar Management -->
    <div class="bg-white p-4 rounded-lg shadow">
        <h3 class="text-lg font-medium mb-4">Sidebar Management</h3>
        
        <!-- Create New Sidebar -->
        <form id="create-sidebar-form" class="mb-4" data-ajax="true">
            <input type="hidden" name="action" value="add_sidebar">
            <div class="flex gap-4">
                <input type="text" name="id" placeholder="Sidebar Name" class="flex-1 px-3 py-2 border rounded" required>
                <input type="text" name="classes" placeholder="CSS Classes (optional)" class="flex-1 px-3 py-2 border rounded">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Create Sidebar</button>
            </div>
        </form>

        <!-- Select Sidebar -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Sidebar</label>
            <?php echo $sidebar_selection; ?>
        </div>
    </div>

    <!-- Widget Management -->
    <div id="widget-management" class="bg-white p-4 rounded-lg shadow <?php echo empty($current_sidebar) ? 'hidden' : ''; ?>">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Widgets in <?php echo htmlspecialchars($current_sidebar); ?></h3>
            <button id="delete-sidebar" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Delete Sidebar</button>
        </div>

        <!-- Add Widget Form -->
        <form id="add-widget-form" class="space-y-4">
            <input type="hidden" name="action" value="save_widget">
            <input type="hidden" name="sidebar" value="<?php echo htmlspecialchars($current_sidebar); ?>">
            <input type="text" name="title" placeholder="Widget Title" class="px-3 py-2 border rounded" required>
            <select name="type" class="px-3 py-2 border rounded" required>
                <option value="">Select Widget Type</option>
                <option value="text">Text Widget</option>
                <option value="html">HTML Widget</option>
                <option value="image">Image Widget</option>
                <option value="link">Link Widget</option>
                <option value="list">List Widget</option>
            </select>
            <textarea name="content" placeholder="Widget Content" class="w-full px-3 py-2 border rounded" rows="4" required></textarea>
            <div class="flex gap-2">
                <input type="text" name="classes" placeholder="CSS Classes (optional)" class="w-full px-3 py-2 border rounded">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Save Widget</button>
            </div>
        </form>

        <!-- Widget List -->
        <div class="mb-4">
            <div class="flex items-center gap-2 text-gray-600 mb-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                </svg>
                <span>Drag and drop widgets to reorder them</span>
            </div>
        </div>
        <div id="widget-list" class="space-y-4">
            <?php echo $widget_list; ?>
        </div>
        
        <!-- Save Sidebar Button -->
        <div class="mt-6">
            <button id="save-sidebar-btn" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                Save Sidebar Changes
            </button>
        </div>
    </div>
</div>

<style>
.widget-item {
    @apply bg-white border border-gray-200 rounded-lg p-4 shadow-sm;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
    position: relative;
    z-index: 1;
    transform-origin: center;
}

.widget-item:hover {
    @apply shadow-md;
    transform: translateY(-1px);
}

.widget-item .widget-header {
    @apply flex items-center justify-between mb-4;
}

.widget-item .widget-title {
    @apply text-lg font-medium text-gray-800;
}

.widget-item .widget-drag-handle {
    @apply p-2 text-gray-400 hover:text-gray-600 cursor-move rounded-full;
    transition: all 0.2s ease;
    touch-action: none;
    -webkit-user-drag: element;
}

.widget-item .widget-drag-handle:hover {
    @apply text-gray-600 bg-gray-100;
}

.widget-item .widget-drag-handle svg {
    transition: transform 0.2s ease;
}

.widget-item .widget-drag-handle:hover svg {
    transform: scale(1.1);
}

.widget-item .widget-content {
    @apply text-gray-600;
}

.widget-item .widget-actions {
    @apply mt-4 flex gap-2;
}

.widget-item .widget-actions button {
    @apply px-3 py-1 rounded text-sm transition-all duration-200;
}

.widget-item .widget-actions .edit-widget {
    @apply bg-blue-500 text-white hover:bg-blue-600 hover:shadow-md;
}

.widget-item .widget-actions .delete-widget {
    @apply bg-red-500 text-white hover:bg-red-600 hover:shadow-md;
}

.sortable-ghost {
    @apply opacity-50 bg-gray-100;
    position: relative;
    z-index: 2;
    transform: scale(0.95);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.sortable-chosen {
    @apply shadow-lg;
    position: relative;
    z-index: 3;
    transform: scale(1.02);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.sortable-drag {
    @apply shadow-xl;
    position: relative;
    z-index: 4;
    transform: scale(1.05) rotate(1deg);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

#widget-list {
    min-height: 50px;
    position: relative;
}

/* Add a subtle animation when widgets are reordered */
@keyframes highlight {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

.widget-item.highlight {
    animation: highlight 0.5s ease;
}

/* Add a nice transition for the drag handle icon */
.widget-drag-handle svg {
    width: 24px;
    height: 24px;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

/* Add a subtle hover effect to the entire widget */
.widget-item {
    border: 1px solid #e5e7eb;
    background: linear-gradient(to bottom, #ffffff, #fafafa);
}

.widget-item:hover {
    border-color: #d1d5db;
    background: linear-gradient(to bottom, #ffffff, #f5f5f5);
}

/* Add a nice transition for the buttons */
.widget-actions button {
    position: relative;
    overflow: hidden;
}

.widget-actions button::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.3s, height 0.3s;
}

.widget-actions button:hover::after {
    width: 200%;
    height: 200%;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarSelect = document.getElementById('sidebar-select');
    const widgetManagement = document.getElementById('widget-management');
    const deleteSidebarBtn = document.getElementById('delete-sidebar');
    const addWidgetForm = document.getElementById('add-widget-form');
    const widgetList = document.getElementById('widget-list');
    const errorMessage = document.getElementById('error-message');

    // Function to show error message
    function showError(message, element = null) {
        if (element) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'inline-block ml-4 text-red-700';
            errorDiv.textContent = message;
            element.parentNode.insertBefore(errorDiv, element.nextSibling);
            setTimeout(() => errorDiv.remove(), 5000);
        } else if (errorMessage) {
            errorMessage.textContent = message;
            errorMessage.classList.remove('hidden');
            errorMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            alert('Error: ' + message);
        }
    }

    function showSuccess(message, element = null) {
        if (element) {
            const successDiv = document.createElement('div');
            successDiv.className = 'inline-block ml-4 text-green-700';
            successDiv.textContent = message;
            element.parentNode.insertBefore(successDiv, element.nextSibling);
            setTimeout(() => successDiv.remove(), 5000);
        } else {
            const successDiv = document.getElementById('success-message');
            if (successDiv) {
                successDiv.textContent = message;
                successDiv.classList.remove('hidden');
                setTimeout(() => {
                    successDiv.classList.add('hidden');
                }, 5000);
            } else {
                alert('Success: ' + message);
            }
        }
    }

    // Initialize Sortable
    if (widgetList) {
        new Sortable(widgetList, {
            animation: 300,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            handle: '.widget-drag-handle',
            forceFallback: false,
            fallbackOnBody: true,
            scroll: true,
            scrollSensitivity: 30,
            scrollSpeed: 10,
            onStart: function(evt) {
                document.body.style.cursor = 'grabbing';
            },
            onEnd: function(evt) {
                document.body.style.cursor = '';
                
                // Add highlight animation to the moved item
                const item = evt.item;
                item.classList.add('highlight');
                setTimeout(() => item.classList.remove('highlight'), 500);

                // Get the new order of widget IDs
                const widgets = Array.from(widgetList.children).map(el => el.dataset.id);

                // Send the new order to the backend
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        action: 'update_widget_order',
                        sidebar: sidebarSelect.value,
                        widgets: widgets
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        showError(data.error || 'Failed to update widget order');
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('An error occurred while updating widget order: ' + error.message);
                    window.location.reload();
                });
            }
        });
    }

    // Handle sidebar selection
    sidebarSelect.addEventListener('change', function() {
        if (this.value) {
            window.location.href = `?action=manage_widgets&sidebar=${encodeURIComponent(this.value)}`;
        } else {
            widgetManagement.classList.add('hidden');
        }
    });

    // Handle sidebar deletion
    deleteSidebarBtn.addEventListener('click', function() {
        if (confirm('Are you sure you want to delete this sidebar? All widgets will be lost.')) {
            const formData = new FormData();
            formData.append('action', 'delete_sidebar');
            formData.append('id', sidebarSelect.value);

            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '?action=manage_widgets';
                } else {
                    showError(data.error || 'Failed to delete sidebar');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('An error occurred while deleting the sidebar: ' + error.message);
            });
        }
    });

    // Handle widget deletion
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-widget')) {
            if (confirm('Are you sure you want to delete this widget?')) {
                const widgetId = e.target.dataset.id;
                const sidebarId = sidebarSelect.value;

                console.log('Deleting widget. ID:', widgetId, 'Sidebar ID:', sidebarId);

                const formData = new FormData();
                formData.append('action', 'delete_widget');
                formData.append('sidebar', sidebarId);
                formData.append('id', widgetId);

                // Debug logging
                console.log('Deleting widget:', {
                    widgetId: widgetId,
                    sidebarId: sidebarId,
                    action: 'delete_widget'
                });

                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Delete response:', data);
                    if (data.success) {
                        e.target.closest('.widget-item').remove();
                    } else {
                        showError(data.error || 'Failed to delete widget');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('An error occurred while deleting the widget: ' + error.message);
                });
            }
        }
    });

    // Handle widget editing
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-widget')) {
            const widgetItem = e.target.closest('.widget-item');
            const widgetId = e.target.dataset.id;
            const title = widgetItem.querySelector('.widget-title').textContent;
            const type = widgetItem.querySelector('.text-sm.text-gray-500').textContent;
            
            // Populate the form
            addWidgetForm.querySelector('[name="title"]').value = title;
            addWidgetForm.querySelector('[name="type"]').value = type;
            const tempTextarea = document.createElement('textarea');
            tempTextarea.innerHTML = widgetItem.dataset.content || '';
            addWidgetForm.querySelector('[name="content"]').value = tempTextarea.value;
            addWidgetForm.querySelector('[name="classes"]').value = widgetItem.dataset.classes || '';
            
            // Add widget ID for update
            let idInput = addWidgetForm.querySelector('[name="id"]');
            if (!idInput) {
                idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                addWidgetForm.appendChild(idInput);
            }
            idInput.value = widgetId;
            
            // Change button text
            addWidgetForm.querySelector('button[type="submit"]').textContent = 'Update Widget';
            
            // Scroll to form
            addWidgetForm.scrollIntoView({ behavior: 'smooth' });
        }
    });

    // Function to reset form for adding new widgets
    function resetForm() {
        addWidgetForm.reset();
        addWidgetForm.querySelector('[name="action"]').value = 'save_widget';
        addWidgetForm.querySelector('button[type="submit"]').textContent = 'Save Widget';
        
        // Remove widget ID if it exists
        const idInput = addWidgetForm.querySelector('[name="id"]');
        if (idInput) {
            idInput.remove();
        }
    }

    // Handle form submission
    addWidgetForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        // Debug logging
        console.log('Form submission data:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }
        
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Save response:', data);
            if (data.success) {
                showSuccess('Widget saved successfully!', e.submitter);
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showError(data.error || 'Failed to save widget', e.submitter);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('An error occurred while saving the widget: ' + error.message);
        });
    });

    // Add a button to reset form for adding new widgets
    const resetButton = document.createElement('button');
    resetButton.type = 'button';
    resetButton.textContent = 'Add New Widget';
    resetButton.className = 'bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 ml-2';
    resetButton.onclick = resetForm;
    addWidgetForm.querySelector('button[type="submit"]').parentNode.appendChild(resetButton);

    // Handle save sidebar button
    document.getElementById('save-sidebar-btn').addEventListener('click', function() {
        const formData = new FormData();
        formData.append('action', 'save_sidebar');
        formData.append('sidebar', sidebarSelect.value);
        
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess('Sidebar changes saved successfully!', this);
            } else {
                showError(data.error || 'Failed to save sidebar', this);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('An error occurred while saving the sidebar: ' + error.message);
        });
    });

    // Handle create sidebar form submission
    document.getElementById('create-sidebar-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess('Sidebar created successfully!', e.submitter);
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showError(data.error || 'Failed to create sidebar', e.submitter);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('An error occurred while creating the sidebar: ' + error.message);
        });
    });

    function showSuccess(message) {
        const successDiv = document.getElementById('success-message');
        if (successDiv) {
            successDiv.textContent = message;
            successDiv.classList.remove('hidden');
            setTimeout(() => {
                successDiv.classList.add('hidden');
            }, 5000);
        } else {
            alert('Success: ' + message);
        }
    }
});
</script>
