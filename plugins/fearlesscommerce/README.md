# FearlessCommerce Plugin

A comprehensive e-commerce plugin for FearlessCMS with integrated payment gateway support for Stripe and PayPal.

## Features

- **Product Management**: Create, edit, and manage products with inventory tracking
- **Order Management**: Process and track customer orders with detailed tax and shipping information
- **Payment Gateway Integration**: Built-in support for Stripe and PayPal
- **Tax Management**: Configurable tax rates with location-based calculations
- **Shipping Methods**: Built-in flat rate and weight-based shipping with plugin extensibility
- **Extensible Architecture**: Easy to add new payment gateways and shipping methods via plugins
- **Admin Interface**: User-friendly admin panels for all e-commerce operations

## Installation

1. Ensure the `mariadb-connector` plugin is installed and configured
2. Copy the `fearlesscommerce` directory to your FearlessCMS `plugins` folder
3. The plugin will automatically create necessary database tables on first load

## Configuration

### Stripe Setup

1. Go to Admin → Plugins → Payment Gateways
2. Enable Stripe and configure:
   - **Publishable Key**: Your Stripe publishable key (starts with `pk_`)
   - **Secret Key**: Your Stripe secret key (starts with `sk_`)
   - **Webhook Secret**: Your Stripe webhook endpoint secret (optional)

### PayPal Setup

1. Go to Admin → Plugins → Payment Gateways
2. Enable PayPal and configure:
   - **Client ID**: Your PayPal application client ID
   - **Client Secret**: Your PayPal application client secret
   - **Mode**: Choose between Sandbox (testing) or Live (production)

### Tax Configuration

1. Go to Admin → Plugins → Tax Rates
2. Add tax rates with:
   - **Name**: Descriptive name for the tax rate
   - **Rate**: Tax rate (as decimal, e.g., 0.08 for 8%)
   - **Type**: Percentage or fixed amount
   - **Location**: Country, state, city, or postal code restrictions
   - **Priority**: Higher priority rates are applied first

### Shipping Configuration

1. Go to Admin → Plugins → Shipping Methods
2. Configure available shipping methods:
   - **Flat Rate Shipping**: Fixed cost shipping with optional free shipping threshold
   - **Weight-Based Shipping**: Cost based on package weight with base cost and per-kg rate

## Database Schema

The plugin creates the following database tables:

- `fearlesscommerce_products`: Product catalog
- `fearlesscommerce_orders`: Customer orders with tax and shipping information
- `fearlesscommerce_order_items`: Individual items within orders
- `fearlesscommerce_order_taxes`: Tax details for each order
- `fearlesscommerce_payment_gateways`: Payment gateway configurations
- `fearlesscommerce_tax_rates`: Tax rate configurations
- `fearlesscommerce_shipping_methods`: Shipping method configurations

## API Usage

### Product Management

```php
// Get all products
$products = fearlesscommerce_get_products();

// Get a specific product
$product = fearlesscommerce_get_product($productId);

// Add a new product
$success = fearlesscommerce_add_product($name, $description, $price, $stock);

// Update a product
$success = fearlesscommerce_update_product($id, $name, $description, $price, $stock);

// Delete a product
$success = fearlesscommerce_delete_product($id);
```

### Order Management

```php
// Get all orders
$orders = fearlesscommerce_get_orders();

// Get a specific order with items
$order = fearlesscommerce_get_order($orderId);

// Create a new order with tax and shipping
$orderId = fearlesscommerce_create_order($userId, $subtotal, $items, $paymentGateway, $paymentId, $taxAmount, $shippingAmount, $shippingMethod, $shippingAddress, $billingAddress);

// Update order status
$success = fearlesscommerce_update_order_status($orderId, $status, $paymentStatus);
```

### Payment Gateway Usage

```php
// Get gateway manager instance
$gatewayManager = GatewayManager::getInstance();

// Process payment
$result = $gatewayManager->processPayment('stripe', [
    'amount' => 29.99,
    'currency' => 'USD',
    'payment_method_id' => 'pm_1234567890',
    'order_id' => '123',
    'customer_email' => 'customer@example.com'
]);

// Verify payment (webhook)
$result = $gatewayManager->verifyPayment('stripe', $webhookData);

// Refund payment
$result = $gatewayManager->refundPayment('stripe', $transactionId, $amount);
```

### Tax Management

```php
// Get all tax rates
$taxRates = fearlesscommerce_get_tax_rates();

// Get a specific tax rate
$taxRate = fearlesscommerce_get_tax_rate($taxRateId);

// Add a new tax rate
$success = fearlesscommerce_add_tax_rate($name, $rate, $type, $country, $state, $city, $postalCode, $priority);

// Update a tax rate
$success = fearlesscommerce_update_tax_rate($id, $name, $rate, $type, $country, $state, $city, $postalCode, $priority, $enabled);

// Delete a tax rate
$success = fearlesscommerce_delete_tax_rate($id);

// Calculate tax for an order
$taxResult = fearlesscommerce_calculate_tax($subtotal, $address);
// Returns: ['total_tax' => $amount, 'applied_taxes' => $taxDetails]

// Save tax details for an order
$success = fearlesscommerce_save_order_taxes($orderId, $appliedTaxes);
```

### Shipping Management

```php
// Get all shipping methods
$shippingMethods = fearlesscommerce_get_shipping_methods();

// Get a specific shipping method
$shippingMethod = fearlesscommerce_get_shipping_method($methodName);

// Save shipping method configuration
$success = fearlesscommerce_save_shipping_method($name, $displayName, $enabled, $config);

// Calculate shipping options
$shippingOptions = fearlesscommerce_calculate_shipping($items, $address, $shippingMethod);

// Get shipping manager instance
$shippingManager = ShippingManager::getInstance();

// Calculate shipping for specific method
$result = $shippingManager->calculateShipping('flat_rate', $items, $address);

// Get all available shipping options
$options = $shippingManager->calculateShippingOptions($items, $address);
```

## Adding Custom Payment Gateways

To add a new payment gateway:

1. Create a new class that extends `BasePaymentGateway`
2. Implement all required methods from `PaymentGatewayInterface`
3. Register the gateway in `GatewayManager`

Example:

```php
class CustomGateway extends BasePaymentGateway {
    public function __construct() {
        parent::__construct('custom', 'Custom Payment');
    }
    
    public function processPayment($paymentData) {
        // Implement payment processing logic
    }
    
    // Implement other required methods...
}

// Register the gateway
$gatewayManager = GatewayManager::getInstance();
$gatewayManager->registerGateway(new CustomGateway());
```

## Adding Custom Shipping Methods

To add a new shipping method:

1. Create a new class that extends `BaseShippingMethod`
2. Implement all required methods from `ShippingMethodInterface`
3. Register the shipping method in `ShippingManager`

Example:

```php
class ExpressShipping extends BaseShippingMethod {
    public function __construct() {
        parent::__construct('express', 'Express Shipping');
    }
    
    public function calculateShipping($items, $address) {
        // Implement shipping calculation logic
        return [
            'available' => true,
            'cost' => 15.99,
            'estimated_days' => 1,
            'message' => 'Express shipping calculated successfully'
        ];
    }
    
    public function isAvailableForAddress($address) {
        // Check if express shipping is available for this address
        return true;
    }
    
    public function getEstimatedDeliveryDays($address) {
        return 1; // Next day delivery
    }
    
    public function validateConfig() {
        // Validate configuration
        return ['valid' => true, 'errors' => []];
    }
}

// Register the shipping method
$shippingManager = ShippingManager::getInstance();
$shippingManager->registerShippingMethod(new ExpressShipping());
```

## Webhook Handling

### Stripe Webhooks

1. Set up a webhook endpoint in your Stripe dashboard
2. Point it to your site's webhook handler
3. Use the webhook secret for signature verification

### PayPal Webhooks

1. Set up a webhook in your PayPal developer dashboard
2. Configure the webhook URL in your application
3. Handle the webhook events in your application

## Security Considerations

- Always use HTTPS in production
- Store sensitive configuration securely
- Validate webhook signatures
- Use environment variables for sensitive data
- Implement proper CSRF protection

## Troubleshooting

### Common Issues

1. **Database Connection Errors**: Ensure MariaDB connector is properly configured
2. **Payment Gateway Errors**: Check API keys and configuration
3. **Webhook Failures**: Verify webhook URLs and signatures

### Debug Mode

Enable debug mode by setting `FCMS_DEBUG=true` in your environment to see detailed logs.

## Support

For support and feature requests, please refer to the FearlessCMS documentation or contact the development team.

## License

This plugin is part of FearlessCMS and follows the same licensing terms.