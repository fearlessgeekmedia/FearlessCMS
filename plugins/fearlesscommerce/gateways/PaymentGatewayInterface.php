<?php
/**
 * Payment Gateway Interface for FearlessCommerce
 * 
 * This interface defines the contract that all payment gateways must implement
 * to be compatible with FearlessCommerce.
 */

interface PaymentGatewayInterface {
    
    /**
     * Get the gateway name
     * 
     * @return string The gateway name
     */
    public function getName();
    
    /**
     * Get the gateway display name
     * 
     * @return string The display name for the gateway
     */
    public function getDisplayName();
    
    /**
     * Check if the gateway is enabled
     * 
     * @return bool True if enabled, false otherwise
     */
    public function isEnabled();
    
    /**
     * Get the gateway configuration
     * 
     * @return array The gateway configuration
     */
    public function getConfig();
    
    /**
     * Set the gateway configuration
     * 
     * @param array $config The configuration array
     * @return bool True on success, false on failure
     */
    public function setConfig($config);
    
    /**
     * Process a payment
     * 
     * @param array $paymentData Payment data including amount, currency, etc.
     * @return array Result array with 'success', 'transaction_id', 'message'
     */
    public function processPayment($paymentData);
    
    /**
     * Verify a payment/webhook
     * 
     * @param array $data The webhook/payment verification data
     * @return array Result array with 'success', 'transaction_id', 'amount', 'status'
     */
    public function verifyPayment($data);
    
    /**
     * Refund a payment
     * 
     * @param string $transactionId The original transaction ID
     * @param float $amount The amount to refund (optional, defaults to full amount)
     * @return array Result array with 'success', 'refund_id', 'message'
     */
    public function refundPayment($transactionId, $amount = null);
    
    /**
     * Get payment status
     * 
     * @param string $transactionId The transaction ID
     * @return array Result array with 'success', 'status', 'message'
     */
    public function getPaymentStatus($transactionId);
    
    /**
     * Validate configuration
     * 
     * @return array Result array with 'valid', 'errors'
     */
    public function validateConfig();
}