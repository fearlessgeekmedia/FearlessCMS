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

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
let currentMenu = null;
let menuData = { items: [] };

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

    // Load menu data from server
    fetch(`?action=load_menu&menu_id=${menuId}`)
        .then(response => response.json())
        .then(data => {
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
            initSortable();
        })
        .catch(error => {
            console.error('Error loading menu:', error);
            alert('Failed to load menu');
        });
}

function addMenuItem() {
    if (!currentMenu) return;
    
    const item = {
        id: `item_${Date.now()}`,
        label: 'New Item',
        url: '#',
        class: '',
        target: ''
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
    console.log('Rendering menu items');
    console.log('Current menuData:', menuData);
    
    const container = document.getElementById('menu-items');
    container.innerHTML = '';
    
    if (!menuData.items || menuData.items.length === 0) {
        container.innerHTML = '<p class="text-gray-500">No menu items. Click "Add Item" to create one.</p>';
        return;
    }
    
    menuData.items.forEach(item => {
        console.log('Rendering item:', item);
        const div = document.createElement('div');
        div.className = 'flex flex-col space-y-2 p-2 border rounded bg-white cursor-move';
        div.setAttribute('data-id', item.id);
        div.innerHTML = `
            <div class="flex items-center space-x-2">
                <div class="cursor-move text-gray-400 px-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                    </svg>
                </div>
                <input type="text" value="${item.label || ''}" onchange="updateMenuItem('${item.id}', 'label', this.value)" class="flex-1 px-2 py-1 border rounded" placeholder="Label">
                <input type="text" value="${item.url || ''}" onchange="updateMenuItem('${item.id}', 'url', this.value)" class="flex-1 px-2 py-1 border rounded" placeholder="URL">
                <button type="button" onclick="removeMenuItem('${item.id}')" class="text-red-500 hover:text-red-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
            <div class="flex items-center space-x-2">
                <input type="text" value="${item.class || ''}" onchange="updateMenuItem('${item.id}', 'class', this.value)" class="flex-1 px-2 py-1 border rounded" placeholder="CSS Class">
                <select onchange="updateMenuItem('${item.id}', 'target', this.value)" class="px-2 py-1 border rounded">
                    <option value="" ${item.target === '' ? 'selected' : ''}>Same Window</option>
                    <option value="_blank" ${item.target === '_blank' ? 'selected' : ''}>New Window</option>
                </select>
            </div>
        `;
        container.appendChild(div);
    });
}

function initSortable() {
    const container = document.getElementById('menu-items');
    new Sortable(container, {
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
            </li>
        `;
    });
    html += '</ul>';
    preview.innerHTML = html;
}

function saveMenu() {
    if (!currentMenu) return;
    
    const menuClass = document.getElementById('menu-class').value;
    
    fetch('?action=manage_menus', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'save_menu',
            menu_id: currentMenu,
            label: currentMenu,
            class: menuClass,
            items: menuData.items
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
    
    fetch('?action=manage_menus', {
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
</script>
