<?php
/*
Plugin Name: FearlessCommerce
Description: A comprehensive e-commerce plugin for FearlessCMS with Stripe and PayPal integration.
Version: 2.0.0
Author: Fearless Geek
Dependencies: mariadb-connector
*/

// Define constants
define('FEARLESSCOMMERCE_PLUGIN_DIR', PLUGIN_DIR . '/fearlesscommerce');
define('FEARLESSCOMMERCE_TEMPLATES_DIR', FEARLESSCOMMERCE_PLUGIN_DIR . '/templates');
define('FEARLESSCOMMERCE_GATEWAYS_DIR', FEARLESSCOMMERCE_PLUGIN_DIR . '/gateways');

// Initialize plugin
function fearlesscommerce_init() {
    // Ensure the database tables exist
    fearlesscommerce_create_tables();

    // Register admin sections
    fcms_register_admin_section('fearlesscommerce_products', [
        'label' => 'Products',
        'menu_order' => 40,
        'parent' => 'manage_plugins',
        'render_callback' => 'fearlesscommerce_admin_products_page'
    ]);

    fcms_register_admin_section('fearlesscommerce_orders', [
        'label' => 'Orders',
        'menu_order' => 45,
        'parent' => 'manage_plugins',
        'render_callback' => 'fearlesscommerce_admin_orders_page'
    ]);

    fcms_register_admin_section('fearlesscommerce_payment_gateways', [
        'label' => 'Payment Gateways',
        'menu_order' => 50,
        'parent' => 'manage_plugins',
        'render_callback' => 'fearlesscommerce_admin_payment_gateways_page'
    ]);

    fcms_register_admin_section('fearlesscommerce_tax_rates', [
        'label' => 'Tax Rates',
        'menu_order' => 55,
        'parent' => 'manage_plugins',
        'render_callback' => 'fearlesscommerce_admin_tax_rates_page'
    ]);

    fcms_register_admin_section('fearlesscommerce_shipping_methods', [
        'label' => 'Shipping Methods',
        'menu_order' => 60,
        'parent' => 'manage_plugins',
        'render_callback' => 'fearlesscommerce_admin_shipping_methods_page'
    ]);

    // Load payment gateways
    fearlesscommerce_load_payment_gateways();
    
    // Load shipping methods
    fearlesscommerce_load_shipping_methods();

    // Register hooks for payment processing
    fcms_register_hook('fearlesscommerce_process_payment', 'fearlesscommerce_process_payment_hook');
}

// Function to create necessary database tables
function fearlesscommerce_create_tables() {
    $pdo = fcms_do_hook('database_connect');
    if (!$pdo) {
        if (getenv('FCMS_DEBUG') === 'true') {
            error_log("FearlessCommerce Plugin: Could not connect to database to create tables.");
        }
        return false;
    }

    $queries = [
        "CREATE TABLE IF NOT EXISTS `fearlesscommerce_products` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `price` DECIMAL(10, 2) NOT NULL,
            `stock` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS `fearlesscommerce_orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT,
            `subtotal` DECIMAL(10, 2) NOT NULL,
            `tax_amount` DECIMAL(10, 2) DEFAULT 0.00,
            `shipping_amount` DECIMAL(10, 2) DEFAULT 0.00,
            `total_amount` DECIMAL(10, 2) NOT NULL,
            `status` VARCHAR(50) DEFAULT 'pending',
            `payment_gateway` VARCHAR(50),
            `payment_id` VARCHAR(255),
            `payment_status` VARCHAR(50),
            `shipping_method` VARCHAR(100),
            `shipping_address` JSON,
            `billing_address` JSON,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS `fearlesscommerce_order_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT NOT NULL,
            `product_id` INT NOT NULL,
            `quantity` INT NOT NULL,
            `price_at_purchase` DECIMAL(10, 2) NOT NULL,
            FOREIGN KEY (`order_id`) REFERENCES `fearlesscommerce_orders`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`product_id`) REFERENCES `fearlesscommerce_products`(`id`) ON DELETE RESTRICT
        )",
        "CREATE TABLE IF NOT EXISTS `fearlesscommerce_payment_gateways` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(50) NOT NULL UNIQUE,
            `enabled` BOOLEAN DEFAULT FALSE,
            `config` JSON,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS `fearlesscommerce_tax_rates` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `rate` DECIMAL(5, 4) NOT NULL,
            `type` ENUM('percentage', 'fixed') DEFAULT 'percentage',
            `country` VARCHAR(2),
            `state` VARCHAR(100),
            `city` VARCHAR(100),
            `postal_code` VARCHAR(20),
            `enabled` BOOLEAN DEFAULT TRUE,
            `priority` INT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS `fearlesscommerce_shipping_methods` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL UNIQUE,
            `display_name` VARCHAR(100) NOT NULL,
            `enabled` BOOLEAN DEFAULT FALSE,
            `config` JSON,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS `fearlesscommerce_order_taxes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT NOT NULL,
            `tax_rate_id` INT NOT NULL,
            `tax_name` VARCHAR(100) NOT NULL,
            `tax_rate` DECIMAL(5, 4) NOT NULL,
            `taxable_amount` DECIMAL(10, 2) NOT NULL,
            `tax_amount` DECIMAL(10, 2) NOT NULL,
            FOREIGN KEY (`order_id`) REFERENCES `fearlesscommerce_orders`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`tax_rate_id`) REFERENCES `fearlesscommerce_tax_rates`(`id`) ON DELETE RESTRICT
        )"
    ];

    foreach ($queries as $query) {
        $stmt = fcms_do_hook('database_query', $query);
        if ($stmt === false) {
        if (getenv('FCMS_DEBUG') === 'true') {
            error_log("FearlessCommerce Plugin: Failed to execute query: " . $query);
        }
        }
    }
}

// Product Management Functions

function fearlesscommerce_get_products() {
    $stmt = fcms_do_hook('database_query', "SELECT * FROM fearlesscommerce_products ORDER BY created_at DESC");
    return $stmt ? $stmt->fetchAll() : [];
}

function fearlesscommerce_get_product($id) {
    $stmt = fcms_do_hook('database_query', "SELECT * FROM fearlesscommerce_products WHERE id = ?", [$id]);
    return $stmt ? $stmt->fetch() : null;
}

function fearlesscommerce_add_product($name, $description, $price, $stock) {
    $query = "INSERT INTO fearlesscommerce_products (name, description, price, stock) VALUES (?, ?, ?, ?)";
    $stmt = fcms_do_hook('database_query', $query, [$name, $description, $price, $stock]);
    return $stmt !== false;
}

function fearlesscommerce_update_product($id, $name, $description, $price, $stock) {
    $query = "UPDATE fearlesscommerce_products SET name = ?, description = ?, price = ?, stock = ? WHERE id = ?";
    $stmt = fcms_do_hook('database_query', $query, [$name, $description, $price, $stock, $id]);
    return $stmt !== false;
}

function fearlesscommerce_delete_product($id) {
    $query = "DELETE FROM fearlesscommerce_products WHERE id = ?";
    $stmt = fcms_do_hook('database_query', $query, [$id]);
    return $stmt !== false;
}

// Order Management Functions

function fearlesscommerce_get_orders() {
    $stmt = fcms_do_hook('database_query', "SELECT * FROM fearlesscommerce_orders ORDER BY created_at DESC");
    return $stmt ? $stmt->fetchAll() : [];
}

function fearlesscommerce_get_order($id) {
    $order_query = "SELECT * FROM fearlesscommerce_orders WHERE id = ?";
    $order_stmt = fcms_do_hook('database_query', $order_query, [$id]);
    $order = $order_stmt ? $order_stmt->fetch() : null;

    if ($order) {
        $items_query = "SELECT * FROM fearlesscommerce_order_items WHERE order_id = ?";
        $items_stmt = fcms_do_hook('database_query', $items_query, [$id]);
        $order['items'] = $items_stmt ? $items_stmt->fetchAll() : [];
    }
    return $order;
}

function fearlesscommerce_create_order($userId, $subtotal, $items, $paymentGateway = null, $paymentId = null, $taxAmount = 0, $shippingAmount = 0, $shippingMethod = null, $shippingAddress = null, $billingAddress = null) {
    $pdo = fcms_do_hook('database_connect');
    if (!$pdo) return false;

    try {
        $pdo->beginTransaction();

        $totalAmount = $subtotal + $taxAmount + $shippingAmount;
        
        $order_query = "INSERT INTO fearlesscommerce_orders (user_id, subtotal, tax_amount, shipping_amount, total_amount, payment_gateway, payment_id, shipping_method, shipping_address, billing_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $order_stmt = $pdo->prepare($order_query);
        $order_stmt->execute([
            $userId, 
            $subtotal, 
            $taxAmount, 
            $shippingAmount, 
            $totalAmount, 
            $paymentGateway, 
            $paymentId, 
            $shippingMethod,
            $shippingAddress ? json_encode($shippingAddress) : null,
            $billingAddress ? json_encode($billingAddress) : null
        ]);
        $order_id = $pdo->lastInsertId();

        foreach ($items as $item) {
            $item_query = "INSERT INTO fearlesscommerce_order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)";
            $item_stmt = $pdo->prepare($item_query);
            $item_stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price_at_purchase']]);
        }

        $pdo->commit();
        return $order_id;
    } catch (PDOException $e) {
        $pdo->rollBack();
        if (getenv('FCMS_DEBUG') === 'true') {
            error_log("FearlessCommerce Plugin: Failed to create order - " . $e->getMessage());
        }
        return false;
    }
}

function fearlesscommerce_update_order_status($orderId, $status, $paymentStatus = null) {
    $query = "UPDATE fearlesscommerce_orders SET status = ?" . ($paymentStatus ? ", payment_status = ?" : "") . " WHERE id = ?";
    $params = [$status];
    if ($paymentStatus) {
        $params[] = $paymentStatus;
    }
    $params[] = $orderId;
    
    $stmt = fcms_do_hook('database_query', $query, $params);
    return $stmt !== false;
}

// Admin page callbacks

function fearlesscommerce_admin_products_page() {
    $message = '';
    $error = '';

    // Handle product actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_product'])) {
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $price = floatval($_POST['price']);
            $stock = intval($_POST['stock']);
            if (fearlesscommerce_add_product($name, $description, $price, $stock)) {
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
            if (fearlesscommerce_update_product($id, $name, $description, $price, $stock)) {
                $message = 'Product updated successfully!';
            } else {
                $error = 'Failed to update product.';
            }
        } elseif (isset($_POST['delete_product'])) {
            $id = intval($_POST['product_id']);
            if (fearlesscommerce_delete_product($id)) {
                $message = 'Product deleted successfully!';
            } else {
                $error = 'Failed to delete product.';
            }
        }
    }

    $products = fearlesscommerce_get_products();

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
            <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
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
                                        <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                                        <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
                                        <input type="hidden" name="name" value="<?= htmlspecialchars($product['name']) ?>">
                                        <input type="hidden" name="description" value="<?= htmlspecialchars($product['description']) ?>">
                                        <input type="hidden" name="price" value="<?= htmlspecialchars($product['price']) ?>">
                                        <input type="hidden" name="stock" value="<?= htmlspecialchars($product['stock']) ?>">
                                        <button type="button" onclick="openEditModal(this)" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm">Edit</button>
                                    </form>
                                    <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
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
                <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
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

function fearlesscommerce_admin_orders_page() {
    $message = '';
    $error = '';

    // Handle order actions (e.g., update status)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_order_status'])) {
            $order_id = intval($_POST['order_id']);
            $status = trim($_POST['status']);
            if (fearlesscommerce_update_order_status($order_id, $status)) {
                $message = 'Order status updated successfully!';
            } else {
                $error = 'Failed to update order status.';
            }
        }
    }

    $orders = fearlesscommerce_get_orders();

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
                                        <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                        <select name="status" class="border rounded px-2 py-1 text-sm">
                                            <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                                            <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_order_status" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">Update</button>
                                    </form>
                                    <button type="button" onclick="viewOrderDetails(<?= htmlspecialchars(json_encode(fearlesscommerce_get_order($order['id']))) ?>)" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm">View Details</button>
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


// Tax Management Functions

function fearlesscommerce_get_tax_rates() {
    $stmt = fcms_do_hook('database_query', "SELECT * FROM fearlesscommerce_tax_rates WHERE enabled = 1 ORDER BY priority DESC, name");
    return $stmt ? $stmt->fetchAll() : [];
}

function fearlesscommerce_get_tax_rate($id) {
    $stmt = fcms_do_hook('database_query', "SELECT * FROM fearlesscommerce_tax_rates WHERE id = ?", [$id]);
    return $stmt ? $stmt->fetch() : null;
}

function fearlesscommerce_add_tax_rate($name, $rate, $type, $country = null, $state = null, $city = null, $postalCode = null, $priority = 0) {
    $query = "INSERT INTO fearlesscommerce_tax_rates (name, rate, type, country, state, city, postal_code, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = fcms_do_hook('database_query', $query, [$name, $rate, $type, $country, $state, $city, $postalCode, $priority]);
    return $stmt !== false;
}

function fearlesscommerce_update_tax_rate($id, $name, $rate, $type, $country = null, $state = null, $city = null, $postalCode = null, $priority = 0, $enabled = true) {
    $query = "UPDATE fearlesscommerce_tax_rates SET name = ?, rate = ?, type = ?, country = ?, state = ?, city = ?, postal_code = ?, priority = ?, enabled = ? WHERE id = ?";
    $stmt = fcms_do_hook('database_query', $query, [$name, $rate, $type, $country, $state, $city, $postalCode, $priority, $enabled, $id]);
    return $stmt !== false;
}

function fearlesscommerce_delete_tax_rate($id) {
    $query = "DELETE FROM fearlesscommerce_tax_rates WHERE id = ?";
    $stmt = fcms_do_hook('database_query', $query, [$id]);
    return $stmt !== false;
}

function fearlesscommerce_calculate_tax($subtotal, $address = null) {
    $taxRates = fearlesscommerce_get_tax_rates();
    $totalTax = 0;
    $appliedTaxes = [];
    
    foreach ($taxRates as $taxRate) {
        $applies = false;
        
        // Check if tax rate applies to this address
        if ($address) {
            if ($taxRate['country'] && strtoupper($taxRate['country']) !== strtoupper($address['country'] ?? '')) {
                continue;
            }
            if ($taxRate['state'] && strtoupper($taxRate['state']) !== strtoupper($address['state'] ?? '')) {
                continue;
            }
            if ($taxRate['city'] && strtoupper($taxRate['city']) !== strtoupper($address['city'] ?? '')) {
                continue;
            }
            if ($taxRate['postal_code'] && $taxRate['postal_code'] !== ($address['postal_code'] ?? '')) {
                continue;
            }
            $applies = true;
        } else {
            // If no address provided, apply all rates (for admin calculations)
            $applies = true;
        }
        
        if ($applies) {
            if ($taxRate['type'] === 'percentage') {
                $taxAmount = $subtotal * $taxRate['rate'];
            } else {
                $taxAmount = $taxRate['rate'];
            }
            
            $totalTax += $taxAmount;
            $appliedTaxes[] = [
                'tax_rate_id' => $taxRate['id'],
                'tax_name' => $taxRate['name'],
                'tax_rate' => $taxRate['rate'],
                'taxable_amount' => $subtotal,
                'tax_amount' => $taxAmount
            ];
        }
    }
    
    return [
        'total_tax' => $totalTax,
        'applied_taxes' => $appliedTaxes
    ];
}

function fearlesscommerce_save_order_taxes($orderId, $appliedTaxes) {
    $pdo = fcms_do_hook('database_connect');
    if (!$pdo) return false;
    
    try {
        $pdo->beginTransaction();
        
        // Clear existing taxes for this order
        $delete_query = "DELETE FROM fearlesscommerce_order_taxes WHERE order_id = ?";
        $delete_stmt = $pdo->prepare($delete_query);
        $delete_stmt->execute([$orderId]);
        
        // Insert new taxes
        foreach ($appliedTaxes as $tax) {
            $insert_query = "INSERT INTO fearlesscommerce_order_taxes (order_id, tax_rate_id, tax_name, tax_rate, taxable_amount, tax_amount) VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $pdo->prepare($insert_query);
            $insert_stmt->execute([
                $orderId,
                $tax['tax_rate_id'],
                $tax['tax_name'],
                $tax['tax_rate'],
                $tax['taxable_amount'],
                $tax['tax_amount']
            ]);
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        if (getenv('FCMS_DEBUG') === 'true') {
            error_log("FearlessCommerce Plugin: Failed to save order taxes - " . $e->getMessage());
        }
        return false;
    }
}

// Shipping Management Functions

function fearlesscommerce_get_shipping_methods() {
    $stmt = fcms_do_hook('database_query', "SELECT * FROM fearlesscommerce_shipping_methods WHERE enabled = 1 ORDER BY name");
    return $stmt ? $stmt->fetchAll() : [];
}

function fearlesscommerce_get_shipping_method($name) {
    $stmt = fcms_do_hook('database_query', "SELECT * FROM fearlesscommerce_shipping_methods WHERE name = ?", [$name]);
    return $stmt ? $stmt->fetch() : null;
}

function fearlesscommerce_save_shipping_method($name, $displayName, $enabled, $config) {
    $existing = fearlesscommerce_get_shipping_method($name);
    
    if ($existing) {
        $query = "UPDATE fearlesscommerce_shipping_methods SET display_name = ?, enabled = ?, config = ?, updated_at = CURRENT_TIMESTAMP WHERE name = ?";
        $params = [$displayName, $enabled, json_encode($config), $name];
    } else {
        $query = "INSERT INTO fearlesscommerce_shipping_methods (name, display_name, enabled, config) VALUES (?, ?, ?, ?)";
        $params = [$name, $displayName, $enabled, json_encode($config)];
    }
    
    $stmt = fcms_do_hook('database_query', $query, $params);
    return $stmt !== false;
}

function fearlesscommerce_calculate_shipping($items, $address, $shippingMethod = null) {
    $shippingManager = ShippingManager::getInstance();
    
    if ($shippingMethod) {
        $method = $shippingManager->getShippingMethod($shippingMethod);
        if ($method && $method->isEnabled()) {
            return $method->calculateShipping($items, $address);
        }
    }
    
    // If no specific method or method not found, try all enabled methods
    $enabledMethods = $shippingManager->getEnabledShippingMethods();
    $shippingOptions = [];
    
    foreach ($enabledMethods as $method) {
        $result = $method->calculateShipping($items, $address);
        if ($result['available']) {
            $shippingOptions[] = [
                'method' => $method->getName(),
                'display_name' => $method->getDisplayName(),
                'cost' => $result['cost'],
                'estimated_days' => $result['estimated_days'] ?? null
            ];
        }
    }
    
    return $shippingOptions;
}

// Payment Gateway Management Functions

function fearlesscommerce_load_payment_gateways() {
    $gateways_dir = FEARLESSCOMMERCE_GATEWAYS_DIR;
    if (is_dir($gateways_dir)) {
        $gateway_files = glob($gateways_dir . '/*.php');
        foreach ($gateway_files as $file) {
            require_once $file;
        }
    }
}

function fearlesscommerce_load_shipping_methods() {
    $shipping_dir = FEARLESSCOMMERCE_PLUGIN_DIR . '/shipping';
    if (is_dir($shipping_dir)) {
        $shipping_files = glob($shipping_dir . '/*.php');
        foreach ($shipping_files as $file) {
            require_once $file;
        }
    }
}

function fearlesscommerce_get_payment_gateways() {
    $stmt = fcms_do_hook('database_query', "SELECT * FROM fearlesscommerce_payment_gateways ORDER BY name");
    return $stmt ? $stmt->fetchAll() : [];
}

function fearlesscommerce_get_payment_gateway($name) {
    $stmt = fcms_do_hook('database_query', "SELECT * FROM fearlesscommerce_payment_gateways WHERE name = ?", [$name]);
    return $stmt ? $stmt->fetch() : null;
}

function fearlesscommerce_save_payment_gateway($name, $enabled, $config) {
    $existing = fearlesscommerce_get_payment_gateway($name);
    
    if ($existing) {
        $query = "UPDATE fearlesscommerce_payment_gateways SET enabled = ?, config = ?, updated_at = CURRENT_TIMESTAMP WHERE name = ?";
        $params = [$enabled, json_encode($config), $name];
    } else {
        $query = "INSERT INTO fearlesscommerce_payment_gateways (name, enabled, config) VALUES (?, ?, ?)";
        $params = [$name, $enabled, json_encode($config)];
    }
    
    $stmt = fcms_do_hook('database_query', $query, $params);
    return $stmt !== false;
}

function fearlesscommerce_process_payment_hook($orderId, $gateway, $paymentData) {
    // This hook will be called by payment gateways to process payments
    $order = fearlesscommerce_get_order($orderId);
    if (!$order) {
        return ['success' => false, 'message' => 'Order not found'];
    }
    
    // Update order with payment information
    fearlesscommerce_update_order_status($orderId, 'processing', 'pending');
    
    return ['success' => true, 'order' => $order];
}

function fearlesscommerce_admin_tax_rates_page() {
    $message = '';
    $error = '';

    // Handle tax rate actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_tax_rate'])) {
            $name = trim($_POST['name']);
            $rate = floatval($_POST['rate']);
            $type = trim($_POST['type']);
            $country = trim($_POST['country']) ?: null;
            $state = trim($_POST['state']) ?: null;
            $city = trim($_POST['city']) ?: null;
            $postalCode = trim($_POST['postal_code']) ?: null;
            $priority = intval($_POST['priority']);
            
            if (fearlesscommerce_add_tax_rate($name, $rate, $type, $country, $state, $city, $postalCode, $priority)) {
                $message = 'Tax rate added successfully!';
            } else {
                $error = 'Failed to add tax rate.';
            }
        } elseif (isset($_POST['edit_tax_rate'])) {
            $id = intval($_POST['tax_rate_id']);
            $name = trim($_POST['name']);
            $rate = floatval($_POST['rate']);
            $type = trim($_POST['type']);
            $country = trim($_POST['country']) ?: null;
            $state = trim($_POST['state']) ?: null;
            $city = trim($_POST['city']) ?: null;
            $postalCode = trim($_POST['postal_code']) ?: null;
            $priority = intval($_POST['priority']);
            $enabled = isset($_POST['enabled']) ? 1 : 0;
            
            if (fearlesscommerce_update_tax_rate($id, $name, $rate, $type, $country, $state, $city, $postalCode, $priority, $enabled)) {
                $message = 'Tax rate updated successfully!';
            } else {
                $error = 'Failed to update tax rate.';
            }
        } elseif (isset($_POST['delete_tax_rate'])) {
            $id = intval($_POST['tax_rate_id']);
            if (fearlesscommerce_delete_tax_rate($id)) {
                $message = 'Tax rate deleted successfully!';
            } else {
                $error = 'Failed to delete tax rate.';
            }
        }
    }

    $taxRates = fearlesscommerce_get_tax_rates();

    ob_start();
    ?>
    <h2 class="text-2xl font-bold mb-6">Manage Tax Rates</h2>

    <?php if ($message): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Add New Tax Rate Form -->
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h3 class="text-lg font-semibold mb-4">Add New Tax Rate</h3>
        <form method="POST" class="space-y-4">
            <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
            <input type="hidden" name="add_tax_rate" value="1">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium mb-1">Name</label>
                    <input type="text" name="name" required class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block font-medium mb-1">Rate</label>
                    <input type="number" name="rate" step="0.0001" min="0" required class="w-full border rounded px-3 py-2">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium mb-1">Type</label>
                    <select name="type" required class="w-full border rounded px-3 py-2">
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed Amount</option>
                    </select>
                </div>
                <div>
                    <label class="block font-medium mb-1">Priority</label>
                    <input type="number" name="priority" min="0" value="0" class="w-full border rounded px-3 py-2">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium mb-1">Country (ISO Code)</label>
                    <input type="text" name="country" maxlength="2" class="w-full border rounded px-3 py-2" placeholder="US">
                </div>
                <div>
                    <label class="block font-medium mb-1">State/Province</label>
                    <input type="text" name="state" class="w-full border rounded px-3 py-2">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium mb-1">City</label>
                    <input type="text" name="city" class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block font-medium mb-1">Postal Code</label>
                    <input type="text" name="postal_code" class="w-full border rounded px-3 py-2">
                </div>
            </div>
            
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Add Tax Rate</button>
        </form>
    </div>

    <!-- Tax Rates List -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-4">Existing Tax Rates</h3>
        <?php if (empty($taxRates)): ?>
            <p>No tax rates found.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">Name</th>
                            <th class="py-2 px-4 border-b">Rate</th>
                            <th class="py-2 px-4 border-b">Type</th>
                            <th class="py-2 px-4 border-b">Location</th>
                            <th class="py-2 px-4 border-b">Priority</th>
                            <th class="py-2 px-4 border-b">Status</th>
                            <th class="py-2 px-4 border-b">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($taxRates as $taxRate): ?>
                            <tr>
                                <td class="py-2 px-4 border-b"><?= htmlspecialchars($taxRate['name']) ?></td>
                                <td class="py-2 px-4 border-b">
                                    <?= $taxRate['type'] === 'percentage' ? 
                                        htmlspecialchars(($taxRate['rate'] * 100) . '%') : 
                                        '$' . htmlspecialchars(number_format($taxRate['rate'], 2)) 
                                    ?>
                                </td>
                                <td class="py-2 px-4 border-b"><?= htmlspecialchars(ucfirst($taxRate['type'])) ?></td>
                                <td class="py-2 px-4 border-b">
                                    <?php
                                    $location = [];
                                    if ($taxRate['country']) $location[] = $taxRate['country'];
                                    if ($taxRate['state']) $location[] = $taxRate['state'];
                                    if ($taxRate['city']) $location[] = $taxRate['city'];
                                    if ($taxRate['postal_code']) $location[] = $taxRate['postal_code'];
                                    echo htmlspecialchars(implode(', ', $location) ?: 'All Locations');
                                    ?>
                                </td>
                                <td class="py-2 px-4 border-b"><?= htmlspecialchars($taxRate['priority']) ?></td>
                                <td class="py-2 px-4 border-b">
                                    <span class="px-2 py-1 rounded text-sm <?= $taxRate['enabled'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $taxRate['enabled'] ? 'Enabled' : 'Disabled' ?>
                                    </span>
                                </td>
                                <td class="py-2 px-4 border-b">
                                    <button onclick="editTaxRate(<?= htmlspecialchars(json_encode($taxRate)) ?>)" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm">Edit</button>
                                    <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this tax rate?');">
                                        <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                                        <input type="hidden" name="tax_rate_id" value="<?= htmlspecialchars($taxRate['id']) ?>">
                                        <button type="submit" name="delete_tax_rate" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Edit Tax Rate Modal -->
    <div id="editTaxRateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-semibold mb-4">Edit Tax Rate</h3>
            <form method="POST" class="space-y-4">
                <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                <input type="hidden" name="edit_tax_rate" value="1">
                <input type="hidden" name="tax_rate_id" id="edit_tax_rate_id">
                
                <div>
                    <label class="block font-medium mb-1">Name</label>
                    <input type="text" name="name" id="edit_tax_name" required class="w-full border rounded px-3 py-2">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium mb-1">Rate</label>
                        <input type="number" name="rate" id="edit_tax_rate" step="0.0001" min="0" required class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-medium mb-1">Type</label>
                        <select name="type" id="edit_tax_type" required class="w-full border rounded px-3 py-2">
                            <option value="percentage">Percentage</option>
                            <option value="fixed">Fixed Amount</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block font-medium mb-1">Priority</label>
                    <input type="number" name="priority" id="edit_tax_priority" min="0" class="w-full border rounded px-3 py-2">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium mb-1">Country</label>
                        <input type="text" name="country" id="edit_tax_country" maxlength="2" class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-medium mb-1">State</label>
                        <input type="text" name="state" id="edit_tax_state" class="w-full border rounded px-3 py-2">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium mb-1">City</label>
                        <input type="text" name="city" id="edit_tax_city" class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-medium mb-1">Postal Code</label>
                        <input type="text" name="postal_code" id="edit_tax_postal_code" class="w-full border rounded px-3 py-2">
                    </div>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" name="enabled" id="edit_tax_enabled" class="mr-2">
                    <label for="edit_tax_enabled" class="font-medium">Enabled</label>
                </div>
                
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeEditTaxRateModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">Cancel</button>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editTaxRate(taxRate) {
            document.getElementById('edit_tax_rate_id').value = taxRate.id;
            document.getElementById('edit_tax_name').value = taxRate.name;
            document.getElementById('edit_tax_rate').value = taxRate.rate;
            document.getElementById('edit_tax_type').value = taxRate.type;
            document.getElementById('edit_tax_priority').value = taxRate.priority;
            document.getElementById('edit_tax_country').value = taxRate.country || '';
            document.getElementById('edit_tax_state').value = taxRate.state || '';
            document.getElementById('edit_tax_city').value = taxRate.city || '';
            document.getElementById('edit_tax_postal_code').value = taxRate.postal_code || '';
            document.getElementById('edit_tax_enabled').checked = taxRate.enabled == 1;
            document.getElementById('editTaxRateModal').classList.remove('hidden');
        }

        function closeEditTaxRateModal() {
            document.getElementById('editTaxRateModal').classList.add('hidden');
        }
    </script>
    <?php
    return ob_get_clean();
}

function fearlesscommerce_admin_shipping_methods_page() {
    $message = '';
    $error = '';

    // Handle shipping method actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['save_shipping_method'])) {
            $name = trim($_POST['shipping_method_name']);
            $displayName = trim($_POST['display_name']);
            $enabled = isset($_POST['enabled']) ? 1 : 0;
            $config = [];
            
            // Collect configuration based on method type
            if ($name === 'flat_rate') {
                $config = [
                    'cost' => floatval($_POST['flat_rate_cost']),
                    'free_shipping_threshold' => floatval($_POST['free_shipping_threshold']) ?: null
                ];
            } elseif ($name === 'weight_based') {
                $config = [
                    'base_cost' => floatval($_POST['weight_base_cost']),
                    'cost_per_kg' => floatval($_POST['cost_per_kg']),
                    'max_weight' => floatval($_POST['max_weight']) ?: null
                ];
            }
            
            if (fearlesscommerce_save_shipping_method($name, $displayName, $enabled, $config)) {
                $message = 'Shipping method configuration saved successfully!';
            } else {
                $error = 'Failed to save shipping method configuration.';
            }
        }
    }

    $shippingMethods = fearlesscommerce_get_shipping_methods();
    $flatRateConfig = fearlesscommerce_get_shipping_method('flat_rate');
    $weightBasedConfig = fearlesscommerce_get_shipping_method('weight_based');

    ob_start();
    ?>
    <h2 class="text-2xl font-bold mb-6">Shipping Method Configuration</h2>

    <?php if ($message): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Flat Rate Shipping -->
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h3 class="text-lg font-semibold mb-4">Flat Rate Shipping</h3>
        <form method="POST" class="space-y-4">
            <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
            <input type="hidden" name="save_shipping_method" value="1">
            <input type="hidden" name="shipping_method_name" value="flat_rate">
            
            <div class="flex items-center">
                <input type="checkbox" name="enabled" id="flat_rate_enabled" <?= $flatRateConfig && $flatRateConfig['enabled'] ? 'checked' : '' ?> class="mr-2">
                <label for="flat_rate_enabled" class="font-medium">Enable Flat Rate Shipping</label>
            </div>
            
            <div>
                <label class="block font-medium mb-1">Display Name</label>
                <input type="text" name="display_name" value="<?= $flatRateConfig ? htmlspecialchars($flatRateConfig['display_name']) : 'Flat Rate Shipping' ?>" class="w-full border rounded px-3 py-2">
            </div>
            
            <div>
                <label class="block font-medium mb-1">Cost</label>
                <input type="number" name="flat_rate_cost" step="0.01" min="0" value="<?= $flatRateConfig ? htmlspecialchars(json_decode($flatRateConfig['config'], true)['cost'] ?? '') : '' ?>" class="w-full border rounded px-3 py-2">
            </div>
            
            <div>
                <label class="block font-medium mb-1">Free Shipping Threshold (optional)</label>
                <input type="number" name="free_shipping_threshold" step="0.01" min="0" value="<?= $flatRateConfig ? htmlspecialchars(json_decode($flatRateConfig['config'], true)['free_shipping_threshold'] ?? '') : '' ?>" class="w-full border rounded px-3 py-2" placeholder="Leave empty for no free shipping">
            </div>
            
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Save Flat Rate Configuration</button>
        </form>
    </div>

    <!-- Weight-Based Shipping -->
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h3 class="text-lg font-semibold mb-4">Weight-Based Shipping</h3>
        <form method="POST" class="space-y-4">
            <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
            <input type="hidden" name="save_shipping_method" value="1">
            <input type="hidden" name="shipping_method_name" value="weight_based">
            
            <div class="flex items-center">
                <input type="checkbox" name="enabled" id="weight_based_enabled" <?= $weightBasedConfig && $weightBasedConfig['enabled'] ? 'checked' : '' ?> class="mr-2">
                <label for="weight_based_enabled" class="font-medium">Enable Weight-Based Shipping</label>
            </div>
            
            <div>
                <label class="block font-medium mb-1">Display Name</label>
                <input type="text" name="display_name" value="<?= $weightBasedConfig ? htmlspecialchars($weightBasedConfig['display_name']) : 'Weight-Based Shipping' ?>" class="w-full border rounded px-3 py-2">
            </div>
            
            <div>
                <label class="block font-medium mb-1">Base Cost</label>
                <input type="number" name="weight_base_cost" step="0.01" min="0" value="<?= $weightBasedConfig ? htmlspecialchars(json_decode($weightBasedConfig['config'], true)['base_cost'] ?? '') : '' ?>" class="w-full border rounded px-3 py-2">
            </div>
            
            <div>
                <label class="block font-medium mb-1">Cost per Kilogram</label>
                <input type="number" name="cost_per_kg" step="0.01" min="0" value="<?= $weightBasedConfig ? htmlspecialchars(json_decode($weightBasedConfig['config'], true)['cost_per_kg'] ?? '') : '' ?>" class="w-full border rounded px-3 py-2">
            </div>
            
            <div>
                <label class="block font-medium mb-1">Maximum Weight (kg)</label>
                <input type="number" name="max_weight" step="0.1" min="0" value="<?= $weightBasedConfig ? htmlspecialchars(json_decode($weightBasedConfig['config'], true)['max_weight'] ?? '') : '' ?>" class="w-full border rounded px-3 py-2" placeholder="Leave empty for no limit">
            </div>
            
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Save Weight-Based Configuration</button>
        </form>
    </div>

    <!-- Shipping Method Status -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-4">Shipping Method Status</h3>
        <div class="space-y-2">
            <?php foreach ($shippingMethods as $method): ?>
                <div class="flex justify-between items-center p-3 border rounded">
                    <span class="font-medium"><?= htmlspecialchars($method['display_name']) ?></span>
                    <span class="px-2 py-1 rounded text-sm <?= $method['enabled'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                        <?= $method['enabled'] ? 'Enabled' : 'Disabled' ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function fearlesscommerce_admin_payment_gateways_page() {
    $message = '';
    $error = '';

    // Handle gateway configuration
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['save_gateway'])) {
            $gateway_name = trim($_POST['gateway_name']);
            $enabled = isset($_POST['enabled']) ? 1 : 0;
            $config = [];
            
            // Collect configuration based on gateway type
            if ($gateway_name === 'stripe') {
                $config = [
                    'publishable_key' => trim($_POST['stripe_publishable_key']),
                    'secret_key' => trim($_POST['stripe_secret_key']),
                    'webhook_secret' => trim($_POST['stripe_webhook_secret'])
                ];
            } elseif ($gateway_name === 'paypal') {
                $config = [
                    'client_id' => trim($_POST['paypal_client_id']),
                    'client_secret' => trim($_POST['paypal_client_secret']),
                    'mode' => trim($_POST['paypal_mode']) // sandbox or live
                ];
            }
            
            if (fearlesscommerce_save_payment_gateway($gateway_name, $enabled, $config)) {
                $message = 'Payment gateway configuration saved successfully!';
            } else {
                $error = 'Failed to save payment gateway configuration.';
            }
        }
    }

    $gateways = fearlesscommerce_get_payment_gateways();
    $stripe_config = fearlesscommerce_get_payment_gateway('stripe');
    $paypal_config = fearlesscommerce_get_payment_gateway('paypal');

    ob_start();
    ?>
    <h2 class="text-2xl font-bold mb-6">Payment Gateway Configuration</h2>

    <?php if ($message): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Stripe Configuration -->
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h3 class="text-lg font-semibold mb-4">Stripe Configuration</h3>
        <form method="POST" class="space-y-4">
            <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
            <input type="hidden" name="save_gateway" value="1">
            <input type="hidden" name="gateway_name" value="stripe">
            
            <div class="flex items-center">
                <input type="checkbox" name="enabled" id="stripe_enabled" <?= $stripe_config && $stripe_config['enabled'] ? 'checked' : '' ?> class="mr-2">
                <label for="stripe_enabled" class="font-medium">Enable Stripe</label>
            </div>
            
            <div>
                <label class="block font-medium mb-1">Publishable Key</label>
                <input type="text" name="stripe_publishable_key" value="<?= $stripe_config ? htmlspecialchars(json_decode($stripe_config['config'], true)['publishable_key'] ?? '') : '' ?>" class="w-full border rounded px-3 py-2">
            </div>
            
            <div>
                <label class="block font-medium mb-1">Secret Key</label>
                <input type="password" name="stripe_secret_key" value="<?= $stripe_config ? htmlspecialchars(json_decode($stripe_config['config'], true)['secret_key'] ?? '') : '' ?>" class="w-full border rounded px-3 py-2">
            </div>
            
            <div>
                <label class="block font-medium mb-1">Webhook Secret</label>
                <input type="password" name="stripe_webhook_secret" value="<?= $stripe_config ? htmlspecialchars(json_decode($stripe_config['config'], true)['webhook_secret'] ?? '') : '' ?>" class="w-full border rounded px-3 py-2">
            </div>
            
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Save Stripe Configuration</button>
        </form>
    </div>

    <!-- PayPal Configuration -->
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h3 class="text-lg font-semibold mb-4">PayPal Configuration</h3>
        <form method="POST" class="space-y-4">
            <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
            <input type="hidden" name="save_gateway" value="1">
            <input type="hidden" name="gateway_name" value="paypal">
            
            <div class="flex items-center">
                <input type="checkbox" name="enabled" id="paypal_enabled" <?= $paypal_config && $paypal_config['enabled'] ? 'checked' : '' ?> class="mr-2">
                <label for="paypal_enabled" class="font-medium">Enable PayPal</label>
            </div>
            
            <div>
                <label class="block font-medium mb-1">Client ID</label>
                <input type="text" name="paypal_client_id" value="<?= $paypal_config ? htmlspecialchars(json_decode($paypal_config['config'], true)['client_id'] ?? '') : '' ?>" class="w-full border rounded px-3 py-2">
            </div>
            
            <div>
                <label class="block font-medium mb-1">Client Secret</label>
                <input type="password" name="paypal_client_secret" value="<?= $paypal_config ? htmlspecialchars(json_decode($paypal_config['config'], true)['client_secret'] ?? '') : '' ?>" class="w-full border rounded px-3 py-2">
            </div>
            
            <div>
                <label class="block font-medium mb-1">Mode</label>
                <select name="paypal_mode" class="w-full border rounded px-3 py-2">
                    <option value="sandbox" <?= $paypal_config && json_decode($paypal_config['config'], true)['mode'] === 'sandbox' ? 'selected' : '' ?>>Sandbox</option>
                    <option value="live" <?= $paypal_config && json_decode($paypal_config['config'], true)['mode'] === 'live' ? 'selected' : '' ?>>Live</option>
                </select>
            </div>
            
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Save PayPal Configuration</button>
        </form>
    </div>

    <!-- Gateway Status -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-4">Gateway Status</h3>
        <div class="space-y-2">
            <?php foreach ($gateways as $gateway): ?>
                <div class="flex justify-between items-center p-3 border rounded">
                    <span class="font-medium"><?= htmlspecialchars(ucfirst($gateway['name'])) ?></span>
                    <span class="px-2 py-1 rounded text-sm <?= $gateway['enabled'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                        <?= $gateway['enabled'] ? 'Enabled' : 'Disabled' ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Initialize the plugin
fearlesscommerce_init();
?>
