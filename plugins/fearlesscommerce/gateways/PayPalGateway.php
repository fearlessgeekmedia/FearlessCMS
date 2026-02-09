<?php
/**
 * PayPal Payment Gateway for FearlessCommerce
 * 
 * This class implements PayPal payment processing for FearlessCommerce.
 */

require_once 'BasePaymentGateway.php';

class PayPalGateway extends BasePaymentGateway {
    
    private $sandboxApiUrl = 'https://api-m.sandbox.paypal.com';
    private $liveApiUrl = 'https://api-m.paypal.com';
    private $accessToken = null;
    private $tokenExpiry = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('paypal', 'PayPal');
    }
    
    /**
     * Get the appropriate API URL based on mode
     * 
     * @return string The API URL
     */
    private function getApiUrl() {
        $mode = $this->config['mode'] ?? 'sandbox';
        return $mode === 'live' ? $this->liveApiUrl : $this->sandboxApiUrl;
    }
    
    /**
     * Get access token for API requests
     * 
     * @return string|false Access token or false on failure
     */
    private function getAccessToken() {
        // Check if we have a valid cached token
        if ($this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }
        
        $this->log('info', 'Requesting PayPal access token');
        
        $authData = [
            'grant_type' => 'client_credentials'
        ];
        
        $response = $this->makeHttpRequest(
            $this->getApiUrl() . '/v1/oauth2/token',
            $authData,
            [
                'Authorization: Basic ' . base64_encode($this->config['client_id'] . ':' . $this->config['client_secret']),
                'Content-Type: application/x-www-form-urlencoded'
            ]
        );
        
        if ($response['success'] && isset($response['data']['access_token'])) {
            $this->accessToken = $response['data']['access_token'];
            $this->tokenExpiry = time() + ($response['data']['expires_in'] - 60); // 60 second buffer
            
            $this->log('info', 'PayPal access token obtained');
            return $this->accessToken;
        }
        
        $this->log('error', 'Failed to get PayPal access token', $response);
        return false;
    }
    
    /**
     * Process a payment
     * 
     * @param array $paymentData Payment data including amount, currency, etc.
     * @return array Result array with 'success', 'transaction_id', 'message'
     */
    public function processPayment($paymentData) {
        $this->log('info', 'Processing PayPal payment', $paymentData);
        
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'PayPal gateway is not enabled'
            ];
        }
        
        $validation = $this->validateConfig();
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'Invalid configuration: ' . implode(', ', $validation['errors'])
            ];
        }
        
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return [
                'success' => false,
                'message' => 'Failed to authenticate with PayPal'
            ];
        }
        
        try {
            // Create order
            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => strtoupper($paymentData['currency'] ?? 'USD'),
                            'value' => number_format($paymentData['amount'], 2, '.', '')
                        ],
                        'description' => $paymentData['description'] ?? 'Order payment',
                        'custom_id' => $paymentData['order_id'] ?? ''
                    ]
                ],
                'application_context' => [
                    'return_url' => $paymentData['return_url'] ?? '',
                    'cancel_url' => $paymentData['cancel_url'] ?? '',
                    'brand_name' => $paymentData['brand_name'] ?? 'FearlessCommerce'
                ]
            ];
            
            $response = $this->makeHttpRequest(
                $this->getApiUrl() . '/v2/checkout/orders',
                $orderData,
                ['Authorization: Bearer ' . $accessToken]
            );
            
            if ($response['success'] && isset($response['data']['id'])) {
                $order = $response['data'];
                
                // If payment method is provided, capture the payment immediately
                if (isset($paymentData['payment_method_id'])) {
                    return $this->capturePayment($order['id'], $accessToken);
                }
                
                $this->log('info', 'PayPal order created', ['order_id' => $order['id']]);
                
                return [
                    'success' => true,
                    'transaction_id' => $order['id'],
                    'approval_url' => $this->getApprovalUrl($order),
                    'message' => 'Order created successfully'
                ];
            } else {
                $this->log('error', 'PayPal order creation failed', $response);
                return [
                    'success' => false,
                    'message' => 'Order creation failed: ' . ($response['data']['message'] ?? 'Unknown error')
                ];
            }
        } catch (Exception $e) {
            $this->log('error', 'PayPal payment processing exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Capture a PayPal payment
     * 
     * @param string $orderId PayPal order ID
     * @param string $accessToken Access token
     * @return array Result array
     */
    private function capturePayment($orderId, $accessToken) {
        $response = $this->makeHttpRequest(
            $this->getApiUrl() . '/v2/checkout/orders/' . $orderId . '/capture',
            [],
            ['Authorization: Bearer ' . $accessToken],
            'POST'
        );
        
        if ($response['success'] && isset($response['data']['id'])) {
            $capture = $response['data'];
            
            if ($capture['status'] === 'COMPLETED') {
                $this->log('info', 'PayPal payment captured', ['capture_id' => $capture['id']]);
                
                return [
                    'success' => true,
                    'transaction_id' => $capture['id'],
                    'message' => 'Payment captured successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Payment capture failed: ' . $capture['status']
                ];
            }
        } else {
            $this->log('error', 'PayPal capture failed', $response);
            return [
                'success' => false,
                'message' => 'Payment capture failed: ' . ($response['data']['message'] ?? 'Unknown error')
            ];
        }
    }
    
    /**
     * Get approval URL from PayPal order
     * 
     * @param array $order PayPal order data
     * @return string|null Approval URL
     */
    private function getApprovalUrl($order) {
        if (isset($order['links'])) {
            foreach ($order['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    return $link['href'];
                }
            }
        }
        return null;
    }
    
    /**
     * Verify a payment/webhook
     * 
     * @param array $data The webhook/payment verification data
     * @return array Result array with 'success', 'transaction_id', 'amount', 'status'
     */
    public function verifyPayment($data) {
        $this->log('info', 'Verifying PayPal payment', $data);
        
        if (isset($data['event_type']) && $data['event_type'] === 'PAYMENT.CAPTURE.COMPLETED') {
            $capture = $data['resource'];
            
            return [
                'success' => true,
                'transaction_id' => $capture['id'],
                'amount' => (float) $capture['amount']['value'],
                'status' => 'completed',
                'currency' => $capture['amount']['currency_code']
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Invalid webhook data'
        ];
    }
    
    /**
     * Refund a payment
     * 
     * @param string $transactionId The original transaction ID
     * @param float $amount The amount to refund (optional, defaults to full amount)
     * @return array Result array with 'success', 'refund_id', 'message'
     */
    public function refundPayment($transactionId, $amount = null) {
        $this->log('info', 'Processing PayPal refund', ['transaction_id' => $transactionId, 'amount' => $amount]);
        
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'PayPal gateway is not enabled'
            ];
        }
        
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return [
                'success' => false,
                'message' => 'Failed to authenticate with PayPal'
            ];
        }
        
        try {
            $refundData = [
                'amount' => [
                    'value' => $amount ? number_format($amount, 2, '.', '') : null,
                    'currency_code' => 'USD' // This should be determined from the original transaction
                ]
            ];
            
            $response = $this->makeHttpRequest(
                $this->getApiUrl() . '/v2/payments/captures/' . $transactionId . '/refund',
                $refundData,
                ['Authorization: Bearer ' . $accessToken]
            );
            
            if ($response['success'] && isset($response['data']['id'])) {
                $refund = $response['data'];
                $this->log('info', 'PayPal refund successful', ['refund_id' => $refund['id']]);
                
                return [
                    'success' => true,
                    'refund_id' => $refund['id'],
                    'message' => 'Refund processed successfully'
                ];
            } else {
                $this->log('error', 'PayPal refund failed', $response);
                return [
                    'success' => false,
                    'message' => 'Refund failed: ' . ($response['data']['message'] ?? 'Unknown error')
                ];
            }
        } catch (Exception $e) {
            $this->log('error', 'PayPal refund exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Refund failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get payment status
     * 
     * @param string $transactionId The transaction ID
     * @return array Result array with 'success', 'status', 'message'
     */
    public function getPaymentStatus($transactionId) {
        $this->log('info', 'Getting PayPal payment status', ['transaction_id' => $transactionId]);
        
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'PayPal gateway is not enabled'
            ];
        }
        
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return [
                'success' => false,
                'message' => 'Failed to authenticate with PayPal'
            ];
        }
        
        try {
            $response = $this->makeHttpRequest(
                $this->getApiUrl() . '/v2/payments/captures/' . $transactionId,
                [],
                ['Authorization: Bearer ' . $accessToken],
                'GET'
            );
            
            if ($response['success']) {
                $capture = $response['data'];
                
                return [
                    'success' => true,
                    'status' => $capture['status'],
                    'amount' => (float) $capture['amount']['value'],
                    'currency' => $capture['amount']['currency_code']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to get payment status: ' . ($response['data']['message'] ?? 'Unknown error')
                ];
            }
        } catch (Exception $e) {
            $this->log('error', 'Get status exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to get payment status: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate configuration
     * 
     * @return array Result array with 'valid', 'errors'
     */
    public function validateConfig() {
        $requiredFields = ['client_id', 'client_secret', 'mode'];
        $errors = $this->validateRequiredFields($requiredFields);
        
        // Validate mode
        if (!empty($this->config['mode']) && !in_array($this->config['mode'], ['sandbox', 'live'])) {
            $errors[] = 'Mode must be either "sandbox" or "live"';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Verify webhook signature
     * 
     * @param string $payload Raw webhook payload
     * @param array $headers HTTP headers
     * @return bool True if signature is valid
     */
    public function verifyWebhookSignature($payload, $headers) {
        // PayPal webhook verification requires additional implementation
        // This is a simplified version - in production, you should implement
        // proper PayPal webhook signature verification
        return true;
    }
}