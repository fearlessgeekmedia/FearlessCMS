<?php
/*
Plugin Name: E-commerce
Description: A basic e-commerce plugin for FearlessCMS.
Version: 1.0.0
Author: Fearless Geek
Dependencies: mariadb-connector
*/

// Define constants
define('ECOMMERCE_PLUGIN_DIR', PLUGIN_DIR . '/ecommerce');
define('ECOMMERCE_TEMPLATES_DIR', ECOMMERCE_PLUGIN_DIR . '/templates');

// Initialize plugin
function ecommerce_init() {
    // Ensure the database tables exist
    ecommerce_create_tables();

    // Register admin sections
    fcms_register_admin_section('ecommerce_products', [
        'label' => 'Products',
        'menu_order' => 40,
        'parent' => 'manage_plugins',
        'render_callback' => 'ecommerce_admin_products_page'
    ]);

    fcms_register_admin_section('ecommerce_orders', [
        'label' => 'Orders',
        'menu_order' => 45,
        'parent' => 'manage_plugins',
        'render_callback' => 'ecommerce_admin_orders_page'
    ]);

    // Register hooks (e.g., for front-end display, payment gateways, etc.)
    // For simplicity, we'll keep these minimal for now
}

// Function to create necessary database tables
function ecommerce_create_tables() {
    $pdo = fcms_do_hook('database_connect');
    if (!$pdo) {
        error_log("E-commerce Plugin: Could not connect to database to create tables.");
        return false;
    }

    $queries = [
        "CREATE TABLE IF NOT EXISTS `ecommerce_products` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `price` DECIMAL(10, 2) NOT NULL,
            `stock` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS `ecommerce_orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT,
            `total_amount` DECIMAL(10, 2) NOT NULL,
            `status` VARCHAR(50) DEFAULT 'pending',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS `ecommerce_order_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT NOT NULL,
            `product_id` INT NOT NULL,
            `quantity` INT NOT NULL,
            `price_at_purchase` DECIMAL(10, 2) NOT NULL,
            FOREIGN KEY (`order_id`) REFERENCES `ecommerce_orders`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`product_id`) REFERENCES `ecommerce_products`(`id`) ON DELETE RESTRICT
        )"
    ];

    foreach ($queries as $query) {
        $stmt = fcms_do_hook('database_query', $query);
        if ($stmt === false) {
            error_log("E-commerce Plugin: Failed to execute query: " . $query);
        }
    }
}

// Product Management Functions

function ecommerce_get_products() {
    $stmt = fcms_do_hook('database_query', "SELECT * FROM ecommerce_products ORDER BY created_at DESC");
    return $stmt ? $stmt->fetchAll() : [];
}

function ecommerce_get_product($id) {
    $stmt = fcms_do_hook('database_query', "SELECT * FROM ecommerce_products WHERE id = ?", [$id]);
    return $stmt ? $stmt->fetch() : null;
}

function ecommerce_add_product($name, $description, $price, $stock) {
    $query = "INSERT INTO ecommerce_products (name, description, price, stock) VALUES (?, ?, ?, ?)";
    $stmt = fcms_do_hook('database_query', $query, [$name, $description, $price, $stock]);
    return $stmt !== false;
}

function ecommerce_update_product($id, $name, $description, $price, $stock) {
    $query = "UPDATE ecommerce_products SET name = ?, description = ?, price = ?, stock = ? WHERE id = ?";
    $stmt = fcms_do_hook('database_query', $query, [$name, $description, $price, $stock, $id]);
    return $stmt !== false;
}

function ecommerce_delete_product($id) {
    $query = "DELETE FROM ecommerce_products WHERE id = ?";
    $stmt = fcms_do_hook('database_query', $query, [$id]);
    return $stmt !== false;
}

// Order Management Functions

function ecommerce_get_orders() {
    $stmt = fcms_do_hook('database_query', "SELECT * FROM ecommerce_orders ORDER BY created_at DESC");
    return $stmt ? $stmt->fetchAll() : [];
}

function ecommerce_get_order($id) {
    $order_query = "SELECT * FROM ecommerce_orders WHERE id = ?";
    $order_stmt = fcms_do_hook('database_query', $order_query, [$id]);
    $order = $order_stmt ? $order_stmt->fetch() : null;

    if ($order) {
        $items_query = "SELECT * FROM ecommerce_order_items WHERE order_id = ?";
        $items_stmt = fcms_do_hook('database_query', $items_query, [$id]);
        $order['items'] = $items_stmt ? $items_stmt->fetchAll() : [];
    }
    return $order;
}

function ecommerce_create_order($userId, $totalAmount, $items) {
    $pdo = fcms_do_hook('database_connect');
    if (!$pdo) return false;

    try {
        $pdo->beginTransaction();

        $order_query = "INSERT INTO ecommerce_orders (user_id, total_amount) VALUES (?, ?)";
        $order_stmt = $pdo->prepare($order_query);
        $order_stmt->execute([$userId, $totalAmount]);
        $order_id = $pdo->lastInsertId();

        foreach ($items as $item) {
            $item_query = "INSERT INTO ecommerce_order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)";
            $item_stmt = $pdo->prepare($item_query);
            $item_stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price_at_purchase']]);
        }

        $pdo->commit();
        return $order_id;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("E-commerce Plugin: Failed to create order - " . $e->getMessage());
        return false;
    }
}

function ecommerce_update_order_status($orderId, $status) {
    $query = "UPDATE ecommerce_orders SET status = ? WHERE id = ?";
    $stmt = fcms_do_hook('database_query', $query, [$status, $orderId]);
    return $stmt !== false;
}

// Admin page callbacks

function ecommerce_admin_products_page() {
    $message = '';
    $error = '';

    // Handle product actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_product'])) {
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $price = floatval($_POST['price']);
            $stock = intval($_POST['stock']);
            if (ecommerce_add_product($name, $description, $price, $stock)) {
                $message = 'Product added successfully!';
            } else {
                $error = 'Failed to add product.';
            }
        } elseif (isset($_POST['edit_product'])) {
            $id = intval($_POST['product_id']);
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $price = floatval($_POST['price']);
            $stock = intval($_POST['stock']);
            if (ecommerce_update_product($id, $name, $description, $price, $stock)) {
                $message = 'Product updated successfully!';
            } else {
                $error = 'Failed to update product.';
            }
        } elseif (isset($_POST['delete_product'])) {
            $id = intval($_POST['product_id']);
            if (ecommerce_delete_product($id)) {
                $message = 'Product deleted successfully!';
            } else {
                $error = 'Failed to delete product.';
            }
        }
    }

    $products = ecommerce_get_products();

    ob_start();
    ?>
    <h2 class="text-2xl font-bold mb-6">Manage Products</h2>

    <?php if ($message): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Add New Product Form -->
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h3 class="text-lg font-semibold mb-4">Add New Product</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="add_product" value="1">
            <div>
                <label class="block font-medium mb-1">Name</label>
                <input type="text" name="name" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Description</label>
                <textarea name="description" class="w-full border rounded px-3 py-2"></textarea>
            </div>
            <div>
                <label class="block font-medium mb-1">Price</label>
                <input type="number" name="price" step="0.01" min="0" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Stock</label>
                <input type="number" name="stock" min="0" required class="w-full border rounded px-3 py-2">
            </div>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Add Product</button>
        </form>
    </div>

    <!-- Product List -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-4">Existing Products</h3>
        <?php if (empty($products)): ?>
            <p>No products found.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">ID</th>
                            <th class="py-2 px-4 border-b">Name</th>
                            <th class="py-2 px-4 border-b">Description</th>
                            <th class="py-2 px-4 border-b">Price</th>
                            <th class="py-2 px-4 border-b">Stock</th>
                            <th class="py-2 px-4 border-b">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="py-2 px-4 border-b"><?= htmlspecialchars($product['id']) ?></td>
                                <td class="py-2 px-4 border-b"><?= htmlspecialchars($product['name']) ?></td>
                                <td class="py-2 px-4 border-b"><?= htmlspecialchars(substr($product['description'], 0, 50)) ?><?= strlen($product['description']) > 50 ? '...' : '' ?></td>
                                <td class="py-2 px-4 border-b">$<?= htmlspecialchars(number_format($product['price'], 2)) ?></td>
                                <td class="py-2 px-4 border-b"><?= htmlspecialchars($product['stock']) ?></td>
                                <td class="py-2 px-4 border-b">
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
                                        <input type="hidden" name="name" value="<?= htmlspecialchars($product['name']) ?>">
                                        <input type="hidden" name="description" value="<?= htmlspecialchars($product['description']) ?>">
                                        <input type="hidden" name="price" value="<?= htmlspecialchars($product['price']) ?>">
                                        <input type="hidden" name="stock" value="<?= htmlspecialchars($product['stock']) ?>">
                                        <button type="button" onclick="openEditModal(this)" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm">Edit</button>
                                    </form>
                                    <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
                                        <button type="submit" name="delete_product" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-semibold mb-4">Edit Product</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="edit_product" value="1">
                <input type="hidden" name="product_id" id="edit_product_id">
                <div>
                    <label class="block font-medium mb-1">Name</label>
                    <input type="text" name="name" id="edit_name" required class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block font-medium mb-1">Description</label>
                    <textarea name="description" id="edit_description" class="w-full border rounded px-3 py-2"></textarea>
                </div>
                <div>
                    <label class="block font-medium mb-1">Price</label>
                    <input type="number" name="price" id="edit_price" step="0.01" min="0" required class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block font-medium mb-1">Stock</label>
                    <input type="number" name="stock" id="edit_stock" min="0" required class="w-full border rounded px-3 py-2">
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeEditModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">Cancel</button>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(button) {
            const form = button.closest('form');
            document.getElementById('edit_product_id').value = form.elements['product_id'].value;
            document.getElementById('edit_name').value = form.elements['name'].value;
            document.getElementById('edit_description').value = form.elements['description'].value;
            document.getElementById('edit_price').value = form.elements['price'].value;
            document.getElementById('edit_stock').value = form.elements['stock'].value;
            document.getElementById('editProductModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editProductModal').classList.add('hidden');
        }
    </script>
    <?php
    return ob_get_clean();
}

function ecommerce_admin_orders_page() {
    $message = '';
    $error = '';

    // Handle order actions (e.g., update status)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_order_status'])) {
            $order_id = intval($_POST['order_id']);
            $status = trim($_POST['status']);
            if (ecommerce_update_order_status($order_id, $status)) {
                $message = 'Order status updated successfully!';
            } else {
                $error = 'Failed to update order status.';
            }
        }
    }

    $orders = ecommerce_get_orders();

    ob_start();
    ?>
    <h2 class="text-2xl font-bold mb-6">Manage Orders</h2>

    <?php if ($message): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Order List -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-4">Existing Orders</h3>
        <?php if (empty($orders)): ?>
            <p>No orders found.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">Order ID</th>
                            <th class="py-2 px-4 border-b">User ID</th>
                            <th class="py-2 px-4 border-b">Total Amount</th>
                            <th class="py-2 px-4 border-b">Status</th>
                            <th class="py-2 px-4 border-b">Order Date</th>
                            <th class="py-2 px-4 border-b">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="py-2 px-4 border-b"><?= htmlspecialchars($order['id']) ?></td>
                                <td class="py-2 px-4 border-b"><?= htmlspecialchars($order['user_id'] ?? 'N/A') ?></td>
                                <td class="py-2 px-4 border-b">$<?= htmlspecialchars(number_format($order['total_amount'], 2)) ?></td>
                                <td class="py-2 px-4 border-b"><?= htmlspecialchars(ucfirst($order['status'])) ?></td>
                                <td class="py-2 px-4 border-b"><?= htmlspecialchars($order['created_at']) ?></td>
                                <td class="py-2 px-4 border-b">
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                        <select name="status" class="border rounded px-2 py-1 text-sm">
                                            <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                                            <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_order_status" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">Update</button>
                                    </form>
                                    <button type="button" onclick="viewOrderDetails(<?= htmlspecialchars(json_encode(ecommerce_get_order($order['id']))) ?>)" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm">View Details</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Order Details Modal -->
    <div id="orderDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-semibold mb-4">Order Details <span id="order_detail_id"></span></h3>
            <div class="space-y-2 mb-4">
                <p><strong>User ID:</strong> <span id="order_detail_user_id"></span></p>
                <p><strong>Total Amount:</strong> <span id="order_detail_total_amount"></span></p>
                <p><strong>Status:</strong> <span id="order_detail_status"></span></p>
                <p><strong>Order Date:</strong> <span id="order_detail_created_at"></span></p>
            </div>
            <h4 class="font-semibold mb-2">Items:</h4>
            <ul id="order_detail_items" class="list-disc ml-5">
            </ul>
            <div class="flex justify-end mt-4">
                <button type="button" onclick="closeOrderDetailsModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">Close</button>
            </div>
        </div>
    </div>

    <script>
        function viewOrderDetails(order) {
            document.getElementById('order_detail_id').innerText = order.id;
            document.getElementById('order_detail_user_id').innerText = order.user_id || 'N/A';
            document.getElementById('order_detail_total_amount').innerText = '$' + parseFloat(order.total_amount).toFixed(2);
            document.getElementById('order_detail_status').innerText = order.status.charAt(0).toUpperCase() + order.status.slice(1);
            document.getElementById('order_detail_created_at').innerText = order.created_at;

            const itemsList = document.getElementById('order_detail_items');
            itemsList.innerHTML = '';
            if (order.items && order.items.length > 0) {
                order.items.forEach(item => {
                    const li = document.createElement('li');
                    li.innerText = `Product ID: ${item.product_id}, Quantity: ${item.quantity}, Price at Purchase: $${parseFloat(item.price_at_purchase).toFixed(2)}`;
                    itemsList.appendChild(li);
                });
            } else {
                const li = document.createElement('li');
                li.innerText = 'No items found for this order.';
                itemsList.appendChild(li);
            }

            document.getElementById('orderDetailsModal').classList.remove('hidden');
        }

        function closeOrderDetailsModal() {
            document.getElementById('orderDetailsModal').classList.add('hidden');
        }
    </script>
    <?php
    return ob_get_clean();
}


// Initialize the plugin
ecommerce_init();
?>