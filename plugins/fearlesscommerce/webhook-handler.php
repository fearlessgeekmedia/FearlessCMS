<?php
/**
 * Webhook Handler for FearlessCommerce Payment Gateways
 * 
 * This file handles webhooks from payment gateways like Stripe and PayPal.
 * Place this file in your web root and configure the webhook URLs in your
 * payment gateway dashboards to point to this file.
 */

// Include FearlessCMS base
require_once dirname(__DIR__, 2) . '/base.php';

// Include the plugin
require_once __DIR__ . '/fearlesscommerce.php';

// Include gateway manager
require_once __DIR__ . '/gateways/GatewayManager.php';

// Get the raw POST data
$payload = file_get_contents('php://input');
$headers = getallheaders();

// Determine which gateway this webhook is from
$gateway = null;
$signature = null;

// Check for Stripe webhook
if (isset($headers['Stripe-Signature'])) {
    $gateway = 'stripe';
    $signature = $headers['Stripe-Signature'];
}
// Check for PayPal webhook
elseif (isset($headers['Paypal-Transmission-Id'])) {
    $gateway = 'paypal';
    $signature = $headers;
}

if (!$gateway) {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown webhook source']);
    exit;
}

// Get gateway manager
$gatewayManager = GatewayManager::getInstance();
$gatewayInstance = $gatewayManager->getGateway($gateway);

if (!$gatewayInstance) {
    http_response_code(400);
    echo json_encode(['error' => 'Gateway not found: ' . $gateway]);
    exit;
}

// Verify webhook signature
$isValid = false;
if ($gateway === 'stripe') {
    $isValid = $gatewayInstance->verifyWebhookSignature($payload, $signature);
} elseif ($gateway === 'paypal') {
    $isValid = $gatewayInstance->verifyWebhookSignature($payload, $signature);
}

if (!$isValid) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid webhook signature']);
    exit;
}

// Parse the webhook data
$data = json_decode($payload, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

// Process the webhook
$result = $gatewayManager->verifyPayment($gateway, $data);

if ($result['success']) {
    // Update order status based on payment result
    $orderId = null;
    
    // Extract order ID from webhook data
    if ($gateway === 'stripe' && isset($data['data']['object']['metadata']['order_id'])) {
        $orderId = $data['data']['object']['metadata']['order_id'];
    } elseif ($gateway === 'paypal' && isset($data['resource']['custom_id'])) {
        $orderId = $data['resource']['custom_id'];
    }
    
    if ($orderId) {
        // Update order status to completed
        fearlesscommerce_update_order_status($orderId, 'completed', 'completed');
        
        // Log the successful payment
        if (getenv('FCMS_DEBUG') === 'true') {
            error_log("FearlessCommerce: Payment completed for order {$orderId} via {$gateway}");
        }
    }
    
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Webhook processed successfully']);
} else {
    // Log the failed webhook
    if (getenv('FCMS_DEBUG') === 'true') {
        error_log("FearlessCommerce: Webhook processing failed for {$gateway}: " . $result['message']);
    }
    
    http_response_code(400);
    echo json_encode(['error' => $result['message']]);
}
?>