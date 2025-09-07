<?php
/**
 * Base Payment Gateway Class for FearlessCommerce
 * 
 * This abstract class provides common functionality for all payment gateways
 * and implements the PaymentGatewayInterface.
 */

require_once 'PaymentGatewayInterface.php';

abstract class BasePaymentGateway implements PaymentGatewayInterface {
    
    protected $name;
    protected $displayName;
    protected $enabled = false;
    protected $config = [];
    
    /**
     * Constructor
     * 
     * @param string $name The gateway name
     * @param string $displayName The display name
     */
    public function __construct($name, $displayName) {
        $this->name = $name;
        $this->displayName = $displayName;
        $this->loadConfig();
    }
    
    /**
     * Get the gateway name
     * 
     * @return string The gateway name
     */
    public function getName() {
        return $this->name;
    }
    
    /**
     * Get the gateway display name
     * 
     * @return string The display name for the gateway
     */
    public function getDisplayName() {
        return $this->displayName;
    }
    
    /**
     * Check if the gateway is enabled
     * 
     * @return bool True if enabled, false otherwise
     */
    public function isEnabled() {
        return $this->enabled;
    }
    
    /**
     * Get the gateway configuration
     * 
     * @return array The gateway configuration
     */
    public function getConfig() {
        return $this->config;
    }
    
    /**
     * Set the gateway configuration
     * 
     * @param array $config The configuration array
     * @return bool True on success, false on failure
     */
    public function setConfig($config) {
        $this->config = $config;
        return $this->saveConfig();
    }
    
    /**
     * Load configuration from database
     */
    protected function loadConfig() {
        $gateway_data = fearlesscommerce_get_payment_gateway($this->name);
        if ($gateway_data) {
            $this->enabled = (bool) $gateway_data['enabled'];
            $this->config = json_decode($gateway_data['config'], true) ?: [];
        }
    }
    
    /**
     * Save configuration to database
     * 
     * @return bool True on success, false on failure
     */
    protected function saveConfig() {
        return fearlesscommerce_save_payment_gateway($this->name, $this->enabled, $this->config);
    }
    
    /**
     * Log gateway activity
     * 
     * @param string $level Log level (info, warning, error)
     * @param string $message Log message
     * @param array $context Additional context data
     */
    protected function log($level, $message, $context = []) {
        if (getenv('FCMS_DEBUG') === 'true') {
            $logMessage = sprintf(
                "[FearlessCommerce %s] %s: %s %s",
                $this->name,
                strtoupper($level),
                $message,
                !empty($context) ? json_encode($context) : ''
            );
            error_log($logMessage);
        }
    }
    
    /**
     * Validate required configuration fields
     * 
     * @param array $requiredFields Array of required field names
     * @return array Array of validation errors
     */
    protected function validateRequiredFields($requiredFields) {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (empty($this->config[$field])) {
                $errors[] = "Field '{$field}' is required";
            }
        }
        
        return $errors;
    }
    
    /**
     * Make HTTP request
     * 
     * @param string $url The URL to request
     * @param array $data The data to send
     * @param array $headers Additional headers
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @return array Response array with 'success', 'data', 'error'
     */
    protected function makeHttpRequest($url, $data = [], $headers = [], $method = 'POST') {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => array_merge([
                'Content-Type: application/json',
                'User-Agent: FearlessCommerce/2.0.0'
            ], $headers)
        ]);
        
        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            if ($method === 'PUT') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            }
        } elseif ($method === 'GET' && !empty($data)) {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => $error
            ];
        }
        
        $decodedResponse = json_decode($response, true);
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'data' => $decodedResponse ?: $response,
            'http_code' => $httpCode
        ];
    }
}