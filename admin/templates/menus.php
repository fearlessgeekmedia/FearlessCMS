<!-- Menu Management -->
<div class="space-y-8">
    <!-- Menu Selection -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Menu Selection</h3>
            <button onclick="showNewMenuModal()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Create New Menu
            </button>
        </div>
        <div class="flex space-x-2">
            <select id="menu-select" onchange="loadMenu(this.value)" class="flex-1 border rounded px-3 py-2">
                <option value="">Select a menu...</option>
                <?php echo $menu_options; ?>
            </select>
            <button onclick="deleteSelectedMenu()" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600" id="delete-menu-btn" style="display: none;">
                Delete Menu
            </button>
        </div>
    </div>

    <!-- Menu Editor -->
    <div id="menu-editor" class="bg-white shadow rounded-lg p-6" style="display: none;">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Menu Structure</h3>
            <div class="space-x-2">
                <button onclick="addMenuItem()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    Add Item
                </button>
                <button onclick="saveMenu()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Save Menu
                </button>
            </div>
        </div>
        
        <!-- Menu Class -->
        <div class="mb-4">
            <label class="block mb-1">Menu Class:</label>
            <input type="text" id="menu-class" class="w-full px-3 py-2 border rounded" placeholder="e.g., main-menu">
        </div>
        
        <div id="menu-items" class="space-y-2">
            <!-- Menu items will be loaded here -->
        </div>
    </div>

    <!-- Menu Preview -->
    <div id="menu-preview" class="bg-white shadow rounded-lg p-6" style="display: none;">
        <h3 class="text-lg font-medium mb-4">Menu Preview</h3>
        <div id="preview-content" class="border rounded p-4">
            <!-- Preview will be loaded here -->
        </div>
    </div>
</div>

<!-- New Menu Modal -->
<div id="new-menu-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Create New Menu</h3>
        <form id="new-menu-form" class="space-y-4">
            <div>
                <label class="block mb-1">Menu Name:</label>
                <input type="text" id="new-menu-name" class="w-full px-3 py-2 border rounded" required>
            </div>
            <div>
                <label class="block mb-1">Menu Class:</label>
                <input type="text" id="new-menu-class" class="w-full px-3 py-2 border rounded" placeholder="e.g., main-menu">
            </div>
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="hideNewMenuModal()" class="bg-gray-400 text-white px-4 py-2 rounded">Cancel</button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Menu Modal -->
<div id="delete-menu-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Delete Menu</h3>
        <p class="mb-4">Are you sure you want to delete this menu? This action cannot be undone.</p>
        <div class="flex justify-end space-x-2">
            <button type="button" onclick="hideDeleteMenuModal()" class="bg-gray-400 text-white px-4 py-2 rounded">Cancel</button>
            <button type="button" onclick="confirmDeleteMenu()" class="bg-red-500 text-white px-4 py-2 rounded">Delete</button>
        </div>
    </div>
</div>

<script>
// Load Sortable.js with fallback
(function loadSortable() {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
    script.onload = function() {
        console.log('Sortable.js loaded successfully from primary CDN');
    };
    script.onerror = function() {
        console.warn('Primary CDN failed, trying fallback...');
        const fallbackScript = document.createElement('script');
        fallbackScript.src = 'https://unpkg.com/sortablejs@1.15.0/Sortable.min.js';
        fallbackScript.onload = function() {
            console.log('Sortable.js loaded successfully from fallback CDN');
        };
        fallbackScript.onerror = function() {
            console.error('Both CDNs failed to load Sortable.js');
        };
        document.head.appendChild(fallbackScript);
    };
    document.head.appendChild(script);
})();
</script>
<script>
let currentMenu = null;
let menuData = { items: [] };
let sortableInstance = null;

// Check if Sortable is available
function isSortableAvailable() {
    return typeof Sortable !== 'undefined';
}

// Initialize Sortable with error handling
function initSortable() {
    const container = document.getElementById('menu-items');
    if (!container) {
        console.warn('Menu items container not found for Sortable initialization');
        return;
    }
    
    // Check if Sortable is available
    if (!isSortableAvailable()) {
        console.warn('Sortable library not loaded, retrying in 500ms...');
        // Retry after a delay
        setTimeout(() => {
            if (isSortableAvailable()) {
                initSortable();
            } else {
                console.warn('Sortable library still not loaded, drag-and-drop functionality disabled');
                // Show a user-friendly message
                const warningDiv = document.createElement('div');
                warningDiv.className = 'text-yellow-600 text-sm p-2 bg-yellow-50 border border-yellow-200 rounded mb-2';
                warningDiv.innerHTML = '⚠️ Drag-and-drop reordering is temporarily unavailable. Please refresh the page to try again.';
                container.parentNode.insertBefore(warningDiv, container);
            }
        }, 500);
        return;
    }
    
    try {
        // Destroy existing instance if it exists
        if (sortableInstance) {
            sortableInstance.destroy();
        }
        
        sortableInstance = new Sortable(container, {
            animation: 150,
            handle: '.cursor-move',
            onEnd: function(evt) {
                const items = Array.from(container.children).map(div => {
                    const id = div.getAttribute('data-id');
                    return menuData.items.find(item => item.id === id);
                }).filter(Boolean);
                menuData.items = items;
                updatePreview();
            }
        });
        
        console.log('Sortable initialized successfully');
    } catch (error) {
        console.error('Error initializing Sortable:', error);
        // Show user-friendly error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'text-red-600 text-sm p-2 bg-red-50 border border-red-200 rounded mb-2';
        errorDiv.innerHTML = '❌ Failed to initialize drag-and-drop functionality. Menu editing will still work.';
        container.parentNode.insertBefore(errorDiv, container);
    }
}

function showNewMenuModal() {
    document.getElementById('new-menu-modal').classList.remove('hidden');
}

function hideNewMenuModal() {
    document.getElementById('new-menu-modal').classList.add('hidden');
}

function loadMenu(menuId) {
    if (!menuId) {
        document.getElementById('menu-editor').style.display = 'none';
        document.getElementById('menu-preview').style.display = 'none';
        document.getElementById('delete-menu-btn').style.display = 'none';
        return;
    }

    currentMenu = menuId;
    document.getElementById('menu-editor').style.display = 'block';
    document.getElementById('menu-preview').style.display = 'block';
    document.getElementById('delete-menu-btn').style.display = 'block';

    // Show loading state
    const menuItemsContainer = document.getElementById('menu-items');
    menuItemsContainer.innerHTML = '<div class="text-center py-4 text-gray-500">Loading menu...</div>';

    // Load menu data from server
    fetch(`../menu-ajax-handler.php?action=load_menu&menu_id=${menuId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text(); // Get response as text first for debugging
        })
        .then(text => {
            console.log('Raw response:', text); // Debug: log the raw response
            
            // Try to parse as JSON
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response text:', text);
                throw new Error('Invalid JSON response from server');
            }
            
            // Check if the response indicates an error
            if (data.success === false) {
                throw new Error(data.error || 'Unknown error from server');
            }
            
            menuData = data;
            // Ensure items array exists and has IDs
            if (!menuData.items) {
                menuData.items = [];
            }
            // Add IDs to items if they don't have them and ensure they're strings
            menuData.items = menuData.items.map((item, index) => ({
                ...item,
                id: item.id ? String(item.id) : `item_${index}`
            }));
            document.getElementById('menu-class').value = menuData.menu_class || '';
            renderMenuItems();
            updatePreview();
            
            // Initialize Sortable with a small delay to ensure the library is fully loaded
            setTimeout(() => {
                initSortable();
            }, 100);
            
            console.log('Menu loaded successfully:', menuData);
        })
        .catch(error => {
            console.error('Error loading menu:', error);
            
            // Show user-friendly error message
            const menuItemsContainer = document.getElementById('menu-items');
            menuItemsContainer.innerHTML = `
                <div class="text-center py-4">
                    <div class="text-red-600 mb-2">Failed to load menu</div>
                    <div class="text-sm text-gray-500">${error.message}</div>
                    <button onclick="loadMenu('${menuId}')" class="mt-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Retry
                    </button>
                </div>
            `;
            
            // Don't show alert - it's annoying and the error is already displayed
            // alert('Failed to load menu');
        });
}

function addMenuItem() {
    if (!currentMenu) return;
    
    const item = {
        id: `item_${Date.now()}`,
        label: 'New Item',
        url: '#',
        class: '',
        target: '',
        children: []
    };
    
    menuData.items = menuData.items || [];
    menuData.items.push(item);
    renderMenuItems();
    updatePreview();
    initSortable();
}

function removeMenuItem(itemId) {
    console.log('Removing item:', itemId);
    console.log('Current menuData:', menuData);
    
    if (!currentMenu || !menuData.items) {
        console.log('No current menu or items array');
        return;
    }
    
    console.log('Before filter - items:', menuData.items);
    menuData.items = menuData.items.filter(item => {
        console.log('Checking item:', item);
        // Convert both IDs to strings for comparison
        return String(item.id) !== String(itemId);
    });
    console.log('After filter - items:', menuData.items);
    
    renderMenuItems();
    updatePreview();
}

function renderMenuItems() {
    const container = document.getElementById('menu-items');
    container.innerHTML = '';
    
    if (!menuData.items) return;
    
    menuData.items.forEach(item => {
        if (!item) return;
        const div = document.createElement('div');
        div.className = 'border rounded p-4 bg-gray-50';
        div.setAttribute('data-id', item.id);
        div.innerHTML = `
            <div class="flex items-center space-x-2 mb-2">
                <div class="cursor-move text-gray-400">⋮⋮</div>
                <input type="text" value="${item.label || ''}" onchange="updateMenuItem('${item.id}', 'label', this.value)" class="flex-1 px-2 py-1 border rounded" placeholder="Label">
                <input type="text" value="${item.url || ''}" onchange="updateMenuItem('${item.id}', 'url', this.value)" class="flex-1 px-2 py-1 border rounded" placeholder="URL">
                <button onclick="removeMenuItem('${item.id}')" class="text-red-500 hover:text-red-600">×</button>
            </div>
            <div class="flex items-center space-x-2">
                <input type="text" value="${item.class || ''}" onchange="updateMenuItem('${item.id}', 'class', this.value)" class="flex-1 px-2 py-1 border rounded" placeholder="CSS Class">
                <select onchange="updateMenuItem('${item.id}', 'target', this.value)" class="px-2 py-1 border rounded">
                    <option value="" ${item.target === '' ? 'selected' : ''}>Same Window</option>
                    <option value="_blank" ${item.target === '_blank' ? 'selected' : ''}>New Window</option>
                </select>
            </div>
            <div class="mt-2">
                <button onclick="addSubMenuItem('${item.id}')" class="text-sm text-blue-500 hover:text-blue-600">+ Add Sub-item</button>
                <div class="ml-4 mt-2 space-y-2" id="sub-items-${item.id}">
                    ${renderSubItems(item)}
                </div>
            </div>
        `;
        container.appendChild(div);
    });
}

function renderSubItems(item) {
    if (!item.children || !item.children.length) return '';
    
    return item.children.map(subItem => `
        <div class="border rounded p-2 bg-white">
            <div class="flex items-center space-x-2">
                <input type="text" value="${subItem.label || ''}" onchange="updateSubMenuItem('${item.id}', '${subItem.id}', 'label', this.value)" class="flex-1 px-2 py-1 border rounded" placeholder="Label">
                <input type="text" value="${subItem.url || ''}" onchange="updateSubMenuItem('${item.id}', '${subItem.id}', 'url', this.value)" class="flex-1 px-2 py-1 border rounded" placeholder="URL">
                <button onclick="removeSubMenuItem('${item.id}', '${subItem.id}')" class="text-red-500 hover:text-red-600">×</button>
            </div>
        </div>
    `).join('');
}

function addSubMenuItem(parentId) {
    const parent = menuData.items.find(item => item.id === parentId);
    if (!parent) return;
    
    if (!parent.children) {
        parent.children = [];
    }
    
    const subItem = {
        id: `sub_${Date.now()}`,
        label: 'New Sub-item',
        url: '#',
        class: '',
        target: ''
    };
    
    parent.children.push(subItem);
    renderMenuItems();
    updatePreview();
}

function removeSubMenuItem(parentId, subItemId) {
    const parent = menuData.items.find(item => item.id === parentId);
    if (!parent || !parent.children) return;
    
    parent.children = parent.children.filter(item => item.id !== subItemId);
    renderMenuItems();
    updatePreview();
}

function updateSubMenuItem(parentId, subItemId, field, value) {
    const parent = menuData.items.find(item => item.id === parentId);
    if (!parent || !parent.children) return;
    
    const subItem = parent.children.find(item => item.id === subItemId);
    if (subItem) {
        subItem[field] = value;
        updatePreview();
    }
}

function updateMenuItem(itemId, field, value) {
    const item = menuData.items.find(i => i.id === itemId);
    if (item) {
        item[field] = value;
        updatePreview();
    }
}

function updatePreview() {
    const preview = document.getElementById('preview-content');
    if (!menuData.items || menuData.items.length === 0) {
        preview.innerHTML = '<p class="text-gray-500">No menu items</p>';
        return;
    }
    
    const menuClass = document.getElementById('menu-class').value;
    let html = `<ul class="${menuClass} space-y-2">`;
    menuData.items.forEach(item => {
        if (!item) return;
        const itemClass = item.class ? ` class="${item.class}"` : '';
        const target = item.target ? ` target="${item.target}"` : '';
        html += `
            <li>
                <a href="${item.url || '#'}"${itemClass}${target} class="text-blue-500 hover:text-blue-600">${item.label || 'Unnamed Item'}</a>
                ${renderSubItemsPreview(item)}
            </li>
        `;
    });
    html += '</ul>';
    preview.innerHTML = html;
}

function renderSubItemsPreview(item) {
    if (!item.children || !item.children.length) return '';
    
    let html = '<ul class="ml-4 mt-1 space-y-1">';
    item.children.forEach(subItem => {
        const itemClass = subItem.class ? ` class="${subItem.class}"` : '';
        const target = subItem.target ? ` target="${subItem.target}"` : '';
        html += `
            <li>
                <a href="${subItem.url || '#'}"${itemClass}${target} class="text-blue-500 hover:text-blue-600">${subItem.label || 'Unnamed Sub-item'}</a>
            </li>
        `;
    });
    html += '</ul>';
    return html;
}

function saveMenu() {
    if (!currentMenu) return;
    
    const menuClass = document.getElementById('menu-class').value;
    const menuDataToSave = {
        action: 'save_menu',
        menu_id: currentMenu,
        label: currentMenu,
        class: menuClass,
        items: menuData.items
    };
    
    console.log('Sending menu data:', menuDataToSave);
    
    fetch('../menu-ajax-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(menuDataToSave)
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.text().then(text => {
            console.log('Response text:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse JSON:', e);
                throw new Error('Invalid JSON response from server');
            }
        });
    })
    .then(data => {
        if (data.success) {
            alert('Menu saved successfully!');
        } else {
            alert('Error saving menu: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error saving menu:', error);
        alert('Failed to save menu. Please try again.');
    });
}

// Handle new menu form submission
document.getElementById('new-menu-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const name = document.getElementById('new-menu-name').value;
    const menuClass = document.getElementById('new-menu-class').value;
    
    fetch('../menu-ajax-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
            action: 'create_menu',
            name,
            class: menuClass
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            hideNewMenuModal();
            // Reload the page to show the new menu
            window.location.reload();
        } else {
            alert('Error creating menu: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error creating menu:', error);
        alert('Failed to create menu. Please try again.');
    });
});

function showDeleteMenuModal() {
    document.getElementById('delete-menu-modal').classList.remove('hidden');
}

function hideDeleteMenuModal() {
    document.getElementById('delete-menu-modal').classList.add('hidden');
}

function deleteSelectedMenu() {
    if (!currentMenu) return;
    showDeleteMenuModal();
}

function confirmDeleteMenu() {
    if (!currentMenu) return;
    
    fetch('../menu-ajax-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'delete_menu',
            menu_id: currentMenu
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideDeleteMenuModal();
            // Reset UI
            currentMenu = null;
            menuData = {};
            document.getElementById('menu-select').value = '';
            document.getElementById('menu-editor').style.display = 'none';
            document.getElementById('menu-preview').style.display = 'none';
            document.getElementById('delete-menu-btn').style.display = 'none';
            // Reload the page to update the menu list
            window.location.reload();
        } else {
            alert('Error deleting menu: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error deleting menu:', error);
        alert('Failed to delete menu. Please try again.');
    });
}
</script>
