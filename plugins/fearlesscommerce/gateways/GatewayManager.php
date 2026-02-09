<?php
/**
 * Payment Gateway Manager for FearlessCommerce
 * 
 * This class manages all payment gateways and provides a unified interface
 * for payment processing.
 */

require_once 'PaymentGatewayInterface.php';
require_once 'BasePaymentGateway.php';
require_once 'StripeGateway.php';
require_once 'PayPalGateway.php';

class GatewayManager {
    
    private static $instance = null;
    private $gateways = [];
    
    /**
     * Get singleton instance
     * 
     * @return GatewayManager
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->registerDefaultGateways();
    }
    
    /**
     * Register default payment gateways
     */
    private function registerDefaultGateways() {
        $this->registerGateway(new StripeGateway());
        $this->registerGateway(new PayPalGateway());
    }
    
    /**
     * Register a payment gateway
     * 
     * @param PaymentGatewayInterface $gateway The gateway to register
     */
    public function registerGateway(PaymentGatewayInterface $gateway) {
        $this->gateways[$gateway->getName()] = $gateway;
    }
    
    /**
     * Get all registered gateways
     * 
     * @return array Array of payment gateways
     */
    public function getAllGateways() {
        return $this->gateways;
    }
    
    /**
     * Get enabled gateways only
     * 
     * @return array Array of enabled payment gateways
     */
    public function getEnabledGateways() {
        $enabled = [];
        foreach ($this->gateways as $gateway) {
            if ($gateway->isEnabled()) {
                $enabled[$gateway->getName()] = $gateway;
            }
        }
        return $enabled;
    }
    
    /**
     * Get a specific gateway
     * 
     * @param string $name Gateway name
     * @return PaymentGatewayInterface|null The gateway or null if not found
     */
    public function getGateway($name) {
        return isset($this->gateways[$name]) ? $this->gateways[$name] : null;
    }
    
    /**
     * Process payment with specified gateway
     * 
     * @param string $gatewayName Gateway name
     * @param array $paymentData Payment data
     * @return array Result array
     */
    public function processPayment($gatewayName, $paymentData) {
        $gateway = $this->getGateway($gatewayName);
        
        if (!$gateway) {
            return [
                'success' => false,
                'message' => 'Payment gateway not found: ' . $gatewayName
            ];
        }
        
        if (!$gateway->isEnabled()) {
            return [
                'success' => false,
                'message' => 'Payment gateway is not enabled: ' . $gatewayName
            ];
        }
        
        return $gateway->processPayment($paymentData);
    }
    
    /**
     * Verify payment with specified gateway
     * 
     * @param string $gatewayName Gateway name
     * @param array $data Verification data
     * @return array Result array
     */
    public function verifyPayment($gatewayName, $data) {
        $gateway = $this->getGateway($gatewayName);
        
        if (!$gateway) {
            return [
                'success' => false,
                'message' => 'Payment gateway not found: ' . $gatewayName
            ];
        }
        
        return $gateway->verifyPayment($data);
    }
    
    /**
     * Refund payment with specified gateway
     * 
     * @param string $gatewayName Gateway name
     * @param string $transactionId Transaction ID
     * @param float $amount Refund amount (optional)
     * @return array Result array
     */
    public function refundPayment($gatewayName, $transactionId, $amount = null) {
        $gateway = $this->getGateway($gatewayName);
        
        if (!$gateway) {
            return [
                'success' => false,
                'message' => 'Payment gateway not found: ' . $gatewayName
            ];
        }
        
        if (!$gateway->isEnabled()) {
            return [
                'success' => false,
                'message' => 'Payment gateway is not enabled: ' . $gatewayName
            ];
        }
        
        return $gateway->refundPayment($transactionId, $amount);
    }
    
    /**
     * Get payment status from specified gateway
     * 
     * @param string $gatewayName Gateway name
     * @param string $transactionId Transaction ID
     * @return array Result array
     */
    public function getPaymentStatus($gatewayName, $transactionId) {
        $gateway = $this->getGateway($gatewayName);
        
        if (!$gateway) {
            return [
                'success' => false,
                'message' => 'Payment gateway not found: ' . $gatewayName
            ];
        }
        
        return $gateway->getPaymentStatus($transactionId);
    }
    
    /**
     * Get gateway configuration form fields
     * 
     * @param string $gatewayName Gateway name
     * @return array Array of form field configurations
     */
    public function getGatewayConfigFields($gatewayName) {
        $gateway = $this->getGateway($gatewayName);
        
        if (!$gateway) {
            return [];
        }
        
        $fields = [];
        
        switch ($gatewayName) {
            case 'stripe':
                $fields = [
                    'publishable_key' => [
                        'type' => 'text',
                        'label' => 'Publishable Key',
                        'required' => true,
                        'description' => 'Your Stripe publishable key (starts with pk_)'
                    ],
                    'secret_key' => [
                        'type' => 'password',
                        'label' => 'Secret Key',
                        'required' => true,
                        'description' => 'Your Stripe secret key (starts with sk_)'
                    ],
                    'webhook_secret' => [
                        'type' => 'password',
                        'label' => 'Webhook Secret',
                        'required' => false,
                        'description' => 'Your Stripe webhook endpoint secret'
                    ]
                ];
                break;
                
            case 'paypal':
                $fields = [
                    'client_id' => [
                        'type' => 'text',
                        'label' => 'Client ID',
                        'required' => true,
                        'description' => 'Your PayPal application client ID'
                    ],
                    'client_secret' => [
                        'type' => 'password',
                        'label' => 'Client Secret',
                        'required' => true,
                        'description' => 'Your PayPal application client secret'
                    ],
                    'mode' => [
                        'type' => 'select',
                        'label' => 'Mode',
                        'required' => true,
                        'options' => [
                            'sandbox' => 'Sandbox (Testing)',
                            'live' => 'Live (Production)'
                        ],
                        'description' => 'Choose between sandbox for testing or live for production'
                    ]
                ];
                break;
        }
        
        return $fields;
    }
    
    /**
     * Validate gateway configuration
     * 
     * @param string $gatewayName Gateway name
     * @return array Result array with 'valid', 'errors'
     */
    public function validateGatewayConfig($gatewayName) {
        $gateway = $this->getGateway($gatewayName);
        
        if (!$gateway) {
            return [
                'valid' => false,
                'errors' => ['Gateway not found: ' . $gatewayName]
            ];
        }
        
        return $gateway->validateConfig();
    }
}