<?php
/**
 * Stripe Payment Gateway for FearlessCommerce
 * 
 * This class implements Stripe payment processing for FearlessCommerce.
 */

require_once 'BasePaymentGateway.php';

class StripeGateway extends BasePaymentGateway {
    
    private $apiUrl = 'https://api.stripe.com/v1';
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('stripe', 'Stripe');
    }
    
    /**
     * Process a payment
     * 
     * @param array $paymentData Payment data including amount, currency, etc.
     * @return array Result array with 'success', 'transaction_id', 'message'
     */
    public function processPayment($paymentData) {
        $this->log('info', 'Processing Stripe payment', $paymentData);
        
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'Stripe gateway is not enabled'
            ];
        }
        
        $validation = $this->validateConfig();
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'Invalid configuration: ' . implode(', ', $validation['errors'])
            ];
        }
        
        try {
            // Create payment intent
            $intentData = [
                'amount' => $this->convertToStripeAmount($paymentData['amount']),
                'currency' => strtolower($paymentData['currency'] ?? 'usd'),
                'payment_method' => $paymentData['payment_method_id'],
                'confirmation_method' => 'manual',
                'confirm' => true,
                'metadata' => [
                    'order_id' => $paymentData['order_id'] ?? '',
                    'customer_email' => $paymentData['customer_email'] ?? ''
                ]
            ];
            
            $response = $this->makeHttpRequest(
                $this->apiUrl . '/payment_intents',
                $intentData,
                ['Authorization: Bearer ' . $this->config['secret_key']]
            );
            
            if ($response['success']) {
                $intent = $response['data'];
                
                if ($intent['status'] === 'succeeded') {
                    $this->log('info', 'Payment succeeded', ['intent_id' => $intent['id']]);
                    return [
                        'success' => true,
                        'transaction_id' => $intent['id'],
                        'message' => 'Payment processed successfully'
                    ];
                } elseif ($intent['status'] === 'requires_action') {
                    return [
                        'success' => true,
                        'transaction_id' => $intent['id'],
                        'requires_action' => true,
                        'client_secret' => $intent['client_secret'],
                        'message' => 'Payment requires additional authentication'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Payment failed: ' . ($intent['last_payment_error']['message'] ?? 'Unknown error')
                    ];
                }
            } else {
                $this->log('error', 'Stripe API error', $response);
                return [
                    'success' => false,
                    'message' => 'Payment processing failed: ' . ($response['data']['error']['message'] ?? 'Unknown error')
                ];
            }
        } catch (Exception $e) {
            $this->log('error', 'Payment processing exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verify a payment/webhook
     * 
     * @param array $data The webhook/payment verification data
     * @return array Result array with 'success', 'transaction_id', 'amount', 'status'
     */
    public function verifyPayment($data) {
        $this->log('info', 'Verifying Stripe payment', $data);
        
        if (isset($data['type']) && $data['type'] === 'payment_intent.succeeded') {
            $intent = $data['data']['object'];
            
            return [
                'success' => true,
                'transaction_id' => $intent['id'],
                'amount' => $this->convertFromStripeAmount($intent['amount'], $intent['currency']),
                'status' => 'completed',
                'currency' => strtoupper($intent['currency'])
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
        $this->log('info', 'Processing Stripe refund', ['transaction_id' => $transactionId, 'amount' => $amount]);
        
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'Stripe gateway is not enabled'
            ];
        }
        
        try {
            $refundData = [
                'payment_intent' => $transactionId
            ];
            
            if ($amount !== null) {
                $refundData['amount'] = $this->convertToStripeAmount($amount);
            }
            
            $response = $this->makeHttpRequest(
                $this->apiUrl . '/refunds',
                $refundData,
                ['Authorization: Bearer ' . $this->config['secret_key']]
            );
            
            if ($response['success']) {
                $refund = $response['data'];
                $this->log('info', 'Refund successful', ['refund_id' => $refund['id']]);
                
                return [
                    'success' => true,
                    'refund_id' => $refund['id'],
                    'message' => 'Refund processed successfully'
                ];
            } else {
                $this->log('error', 'Refund failed', $response);
                return [
                    'success' => false,
                    'message' => 'Refund failed: ' . ($response['data']['error']['message'] ?? 'Unknown error')
                ];
            }
        } catch (Exception $e) {
            $this->log('error', 'Refund exception', ['error' => $e->getMessage()]);
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
        $this->log('info', 'Getting Stripe payment status', ['transaction_id' => $transactionId]);
        
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'Stripe gateway is not enabled'
            ];
        }
        
        try {
            $response = $this->makeHttpRequest(
                $this->apiUrl . '/payment_intents/' . $transactionId,
                [],
                ['Authorization: Bearer ' . $this->config['secret_key']],
                'GET'
            );
            
            if ($response['success']) {
                $intent = $response['data'];
                
                return [
                    'success' => true,
                    'status' => $intent['status'],
                    'amount' => $this->convertFromStripeAmount($intent['amount'], $intent['currency']),
                    'currency' => strtoupper($intent['currency'])
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to get payment status: ' . ($response['data']['error']['message'] ?? 'Unknown error')
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
        $requiredFields = ['secret_key', 'publishable_key'];
        $errors = $this->validateRequiredFields($requiredFields);
        
        // Validate Stripe key format
        if (!empty($this->config['secret_key']) && !preg_match('/^sk_(test_|live_)/', $this->config['secret_key'])) {
            $errors[] = 'Invalid Stripe secret key format';
        }
        
        if (!empty($this->config['publishable_key']) && !preg_match('/^pk_(test_|live_)/', $this->config['publishable_key'])) {
            $errors[] = 'Invalid Stripe publishable key format';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Convert amount to Stripe format (cents)
     * 
     * @param float $amount Amount in dollars
     * @return int Amount in cents
     */
    private function convertToStripeAmount($amount) {
        return (int) round($amount * 100);
    }
    
    /**
     * Convert amount from Stripe format (cents to dollars)
     * 
     * @param int $amount Amount in cents
     * @param string $currency Currency code
     * @return float Amount in dollars
     */
    private function convertFromStripeAmount($amount, $currency) {
        // For currencies with no decimal places (like JPY)
        $zeroDecimalCurrencies = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];
        
        if (in_array(strtoupper($currency), $zeroDecimalCurrencies)) {
            return (float) $amount;
        }
        
        return (float) ($amount / 100);
    }
    
    /**
     * Verify webhook signature
     * 
     * @param string $payload Raw webhook payload
     * @param string $signature Stripe signature header
     * @return bool True if signature is valid
     */
    public function verifyWebhookSignature($payload, $signature) {
        if (empty($this->config['webhook_secret'])) {
            return false;
        }
        
        $elements = explode(',', $signature);
        $signatureData = [];
        
        foreach ($elements as $element) {
            $pair = explode('=', $element, 2);
            if (count($pair) === 2) {
                $signatureData[$pair[0]] = $pair[1];
            }
        }
        
        if (!isset($signatureData['v1'])) {
            return false;
        }
        
        $expectedSignature = hash_hmac('sha256', $payload, $this->config['webhook_secret']);
        
        return hash_equals($signatureData['v1'], $expectedSignature);
    }
}