<div class="space-y-8">
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Menu Selection</h3>
            <button onclick="showNewMenuModal()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Create New Menu
            </button>
        </div>
        <select id="menu-select" onchange="loadMenu(this.value)" class="w-full border rounded px-3 py-2">
            <?php echo $menu_options; ?>
        </select>
    </div>
    <!-- ...rest of your menu editor JS and HTML... -->
</div>
