<?php
/**
 * Product Catalog Template for FearlessCommerce
 * 
 * This template displays products and handles the shopping cart functionality.
 * Include this template in your theme or use it as a reference for custom implementations.
 */

// Get products
$products = fearlesscommerce_get_products();
$gatewayManager = GatewayManager::getInstance();
$enabledGateways = $gatewayManager->getEnabledGateways();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Catalog - FearlessCommerce</title>
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://www.paypal.com/sdk/js?client-id=<?= htmlspecialchars($gatewayManager->getGateway('paypal')->getConfig()['client_id'] ?? '') ?>"></script>
    <style>
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .product-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        .product-image {
            width: 100%;
            height: 200px;
            background-color: #f5f5f5;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .product-title {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .product-price {
            font-size: 1.5em;
            color: #2c5aa0;
            margin-bottom: 15px;
        }
        .product-description {
            margin-bottom: 15px;
            color: #666;
        }
        .add-to-cart-btn {
            background-color: #2c5aa0;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        .add-to-cart-btn:hover {
            background-color: #1e3d6f;
        }
        .cart {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            min-width: 300px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .checkout-btn {
            width: 100%;
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 15px;
        }
        .checkout-btn:hover {
            background-color: #218838;
        }
        .payment-methods {
            margin-top: 20px;
        }
        .payment-method {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <div class="product-image"></div>
                <div class="product-title"><?= htmlspecialchars($product['name']) ?></div>
                <div class="product-price">$<?= htmlspecialchars(number_format($product['price'], 2)) ?></div>
                <div class="product-description"><?= htmlspecialchars($product['description']) ?></div>
                <div>Stock: <?= htmlspecialchars($product['stock']) ?></div>
                <button class="add-to-cart-btn" onclick="addToCart(<?= htmlspecialchars(json_encode($product)) ?>)">
                    Add to Cart
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="cart" id="cart">
        <h3>Shopping Cart</h3>
        <div id="cart-items"></div>
        <div id="cart-total"></div>
        <button class="checkout-btn" onclick="showCheckout()" id="checkout-btn" style="display: none;">
            Proceed to Checkout
        </button>
    </div>

    <!-- Checkout Modal -->
    <div id="checkout-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; max-width: 800px; width: 90%; max-height: 90%; overflow-y: auto;">
            <h2>Checkout</h2>
            
            <!-- Order Summary -->
            <div id="checkout-summary" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                <h3>Order Summary</h3>
                <div id="order-items"></div>
                <div style="border-top: 1px solid #dee2e6; margin-top: 10px; padding-top: 10px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Subtotal:</span>
                        <span id="order-subtotal">$0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Tax:</span>
                        <span id="order-tax">$0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Shipping:</span>
                        <span id="order-shipping">$0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-weight: bold; border-top: 1px solid #dee2e6; margin-top: 10px; padding-top: 10px;">
                        <span>Total:</span>
                        <span id="order-total">$0.00</span>
                    </div>
                </div>
            </div>
            
            <!-- Shipping Address -->
            <div style="margin-bottom: 20px;">
                <h3>Shipping Address</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <input type="text" id="shipping_name" placeholder="Full Name" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <input type="text" id="shipping_address" placeholder="Address" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <input type="text" id="shipping_city" placeholder="City" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <input type="text" id="shipping_state" placeholder="State/Province" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <input type="text" id="shipping_postal_code" placeholder="Postal Code" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <select id="shipping_country" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="US">United States</option>
                        <option value="CA">Canada</option>
                        <option value="GB">United Kingdom</option>
                        <option value="AU">Australia</option>
                        <option value="DE">Germany</option>
                        <option value="FR">France</option>
                        <option value="IT">Italy</option>
                        <option value="ES">Spain</option>
                    </select>
                </div>
            </div>
            
            <!-- Billing Address -->
            <div style="margin-bottom: 20px;">
                <h3>Billing Address</h3>
                <label style="display: flex; align-items: center; margin-bottom: 10px;">
                    <input type="checkbox" id="same_as_shipping" onchange="copyShippingToBilling()" style="margin-right: 8px;">
                    Same as shipping address
                </label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <input type="text" id="billing_name" placeholder="Full Name" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <input type="text" id="billing_address" placeholder="Address" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <input type="text" id="billing_city" placeholder="City" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <input type="text" id="billing_state" placeholder="State/Province" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <input type="text" id="billing_postal_code" placeholder="Postal Code" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <select id="billing_country" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="US">United States</option>
                        <option value="CA">Canada</option>
                        <option value="GB">United Kingdom</option>
                        <option value="AU">Australia</option>
                        <option value="DE">Germany</option>
                        <option value="FR">France</option>
                        <option value="IT">Italy</option>
                        <option value="ES">Spain</option>
                    </select>
                </div>
            </div>
            
            <!-- Shipping Methods -->
            <div style="margin-bottom: 20px;">
                <h3>Shipping Methods</h3>
                <div id="shipping-methods">
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px;">
                        <label style="display: flex; align-items: center;">
                            <input type="radio" name="shipping_method" value="flat_rate" data-cost="5.99" style="margin-right: 8px;">
                            <span>Flat Rate Shipping - $5.99 (3-5 business days)</span>
                        </label>
                    </div>
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px;">
                        <label style="display: flex; align-items: center;">
                            <input type="radio" name="shipping_method" value="weight_based" data-cost="8.99" style="margin-right: 8px;">
                            <span>Weight-Based Shipping - $8.99 (5-7 business days)</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Payment Methods -->
            <div class="payment-methods">
                <h3>Payment Methods</h3>
                <?php if (isset($enabledGateways['stripe'])): ?>
                    <div class="payment-method">
                        <button onclick="payWithStripe()" style="width: 100%; padding: 12px; background: #635bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            Pay with Stripe
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($enabledGateways['paypal'])): ?>
                    <div class="payment-method">
                        <div id="paypal-button-container"></div>
                    </div>
                <?php endif; ?>
            </div>
            
            <button onclick="closeCheckout()" style="margin-top: 20px; padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
                Cancel
            </button>
        </div>
    </div>

    <script>
        let cart = [];
        let stripe = null;
        
        // Initialize Stripe if available
        <?php if (isset($enabledGateways['stripe'])): ?>
            stripe = Stripe('<?= htmlspecialchars($gatewayManager->getGateway('stripe')->getConfig()['publishable_key'] ?? '') ?>');
        <?php endif; ?>
        
        function addToCart(product) {
            const existingItem = cart.find(item => item.id === product.id);
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({...product, quantity: 1});
            }
            updateCartDisplay();
        }
        
        function updateCartDisplay() {
            const cartItems = document.getElementById('cart-items');
            const cartTotal = document.getElementById('cart-total');
            const checkoutBtn = document.getElementById('checkout-btn');
            
            cartItems.innerHTML = '';
            let total = 0;
            
            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                
                const itemDiv = document.createElement('div');
                itemDiv.className = 'cart-item';
                itemDiv.innerHTML = `
                    <span>${item.name} x${item.quantity}</span>
                    <span>$${itemTotal.toFixed(2)}</span>
                `;
                cartItems.appendChild(itemDiv);
            });
            
            cartTotal.innerHTML = `<strong>Total: $${total.toFixed(2)}</strong>`;
            checkoutBtn.style.display = cart.length > 0 ? 'block' : 'none';
        }
        
        function showCheckout() {
            const modal = document.getElementById('checkout-modal');
            const orderItems = document.getElementById('order-items');
            const orderSubtotal = document.getElementById('order-subtotal');
            const orderTax = document.getElementById('order-tax');
            const orderShipping = document.getElementById('order-shipping');
            const orderTotal = document.getElementById('order-total');
            
            // Display order items
            orderItems.innerHTML = '';
            let subtotal = 0;
            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                
                const itemDiv = document.createElement('div');
                itemDiv.style.display = 'flex';
                itemDiv.style.justifyContent = 'space-between';
                itemDiv.style.marginBottom = '5px';
                itemDiv.innerHTML = `
                    <span>${item.name} x${item.quantity}</span>
                    <span>$${itemTotal.toFixed(2)}</span>
                `;
                orderItems.appendChild(itemDiv);
            });
            
            // Calculate totals
            const taxAmount = calculateTax(subtotal);
            const shippingAmount = getSelectedShippingCost();
            const total = subtotal + taxAmount + shippingAmount;
            
            // Update display
            orderSubtotal.textContent = `$${subtotal.toFixed(2)}`;
            orderTax.textContent = `$${taxAmount.toFixed(2)}`;
            orderShipping.textContent = `$${shippingAmount.toFixed(2)}`;
            orderTotal.textContent = `$${total.toFixed(2)}`;
            
            modal.style.display = 'block';
            
            // Initialize PayPal button if available
            <?php if (isset($enabledGateways['paypal'])): ?>
                if (typeof paypal !== 'undefined') {
                    paypal.Buttons({
                        createOrder: function(data, actions) {
                            return actions.order.create({
                                purchase_units: [{
                                    amount: {
                                        value: total.toFixed(2)
                                    }
                                }]
                            });
                        },
                        onApprove: function(data, actions) {
                            return actions.order.capture().then(function(details) {
                                alert('Payment completed by ' + details.payer.name.given_name);
                                // Process the order here
                                processOrder('paypal', details.id);
                            });
                        }
                    }).render('#paypal-button-container');
                }
            <?php endif; ?>
        }
        
        function closeCheckout() {
            document.getElementById('checkout-modal').style.display = 'none';
        }
        
        function payWithStripe() {
            if (!stripe) {
                alert('Stripe not available');
                return;
            }
            
            // Create payment intent on server
            fetch('/admin/payment-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    gateway: 'stripe',
                    amount: cart.reduce((total, item) => total + (item.price * item.quantity), 0),
                    currency: 'usd',
                    items: cart
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.requires_action) {
                        stripe.confirmCardPayment(data.client_secret).then(function(result) {
                            if (result.error) {
                                alert('Payment failed: ' + result.error.message);
                            } else {
                                alert('Payment succeeded!');
                                processOrder('stripe', result.paymentIntent.id);
                            }
                        });
                    } else {
                        alert('Payment succeeded!');
                        processOrder('stripe', data.transaction_id);
                    }
                } else {
                    alert('Payment failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Payment processing failed');
            });
        }
        
        function processOrder(gateway, transactionId) {
            // Calculate totals including tax and shipping
            const subtotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
            const taxAmount = calculateTax(subtotal);
            const shippingAmount = getSelectedShippingCost();
            const total = subtotal + taxAmount + shippingAmount;
            
            // Send order data to server
            fetch('/admin/order-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    gateway: gateway,
                    transaction_id: transactionId,
                    items: cart,
                    subtotal: subtotal,
                    tax_amount: taxAmount,
                    shipping_amount: shippingAmount,
                    total: total,
                    shipping_method: getSelectedShippingMethod(),
                    shipping_address: getShippingAddress(),
                    billing_address: getBillingAddress()
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Order placed successfully! Order ID: ' + data.order_id);
                    cart = [];
                    updateCartDisplay();
                    closeCheckout();
                } else {
                    alert('Order failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Order processing failed');
            });
        }
        
        function calculateTax(subtotal) {
            // This would typically call the server to calculate tax based on address
            // For now, we'll use a simple 8% tax rate
            return subtotal * 0.08;
        }
        
        function getSelectedShippingCost() {
            const selectedShipping = document.querySelector('input[name="shipping_method"]:checked');
            return selectedShipping ? parseFloat(selectedShipping.dataset.cost) : 0;
        }
        
        function getSelectedShippingMethod() {
            const selectedShipping = document.querySelector('input[name="shipping_method"]:checked');
            return selectedShipping ? selectedShipping.value : null;
        }
        
        function getShippingAddress() {
            return {
                name: document.getElementById('shipping_name')?.value || '',
                address: document.getElementById('shipping_address')?.value || '',
                city: document.getElementById('shipping_city')?.value || '',
                state: document.getElementById('shipping_state')?.value || '',
                postal_code: document.getElementById('shipping_postal_code')?.value || '',
                country: document.getElementById('shipping_country')?.value || ''
            };
        }
        
        function getBillingAddress() {
            return {
                name: document.getElementById('billing_name')?.value || '',
                address: document.getElementById('billing_address')?.value || '',
                city: document.getElementById('billing_city')?.value || '',
                state: document.getElementById('billing_state')?.value || '',
                postal_code: document.getElementById('billing_postal_code')?.value || '',
                country: document.getElementById('billing_country')?.value || ''
            };
        }
        
        function copyShippingToBilling() {
            const sameAsShipping = document.getElementById('same_as_shipping').checked;
            if (sameAsShipping) {
                document.getElementById('billing_name').value = document.getElementById('shipping_name').value;
                document.getElementById('billing_address').value = document.getElementById('shipping_address').value;
                document.getElementById('billing_city').value = document.getElementById('shipping_city').value;
                document.getElementById('billing_state').value = document.getElementById('shipping_state').value;
                document.getElementById('billing_postal_code').value = document.getElementById('shipping_postal_code').value;
                document.getElementById('billing_country').value = document.getElementById('shipping_country').value;
            }
        }
        
        function updateOrderTotals() {
            const orderSubtotal = document.getElementById('order-subtotal');
            const orderTax = document.getElementById('order-tax');
            const orderShipping = document.getElementById('order-shipping');
            const orderTotal = document.getElementById('order-total');
            
            if (orderSubtotal) {
                const subtotal = parseFloat(orderSubtotal.textContent.replace('$', ''));
                const taxAmount = calculateTax(subtotal);
                const shippingAmount = getSelectedShippingCost();
                const total = subtotal + taxAmount + shippingAmount;
                
                orderTax.textContent = `$${taxAmount.toFixed(2)}`;
                orderShipping.textContent = `$${shippingAmount.toFixed(2)}`;
                orderTotal.textContent = `$${total.toFixed(2)}`;
            }
        }
        
        // Add event listeners for shipping method changes
        document.addEventListener('change', function(e) {
            if (e.target.name === 'shipping_method') {
                updateOrderTotals();
            }
        });
    </script>
</body>
</html>