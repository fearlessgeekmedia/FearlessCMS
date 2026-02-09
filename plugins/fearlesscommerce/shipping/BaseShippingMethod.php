<?php
/**
 * Base Shipping Method Class for FearlessCommerce
 * 
 * This abstract class provides common functionality for all shipping methods
 * and implements the ShippingMethodInterface.
 */

require_once 'ShippingMethodInterface.php';

abstract class BaseShippingMethod implements ShippingMethodInterface {
    
    protected $name;
    protected $displayName;
    protected $enabled = false;
    protected $config = [];
    
    /**
     * Constructor
     * 
     * @param string $name The shipping method name
     * @param string $displayName The display name
     */
    public function __construct($name, $displayName) {
        $this->name = $name;
        $this->displayName = $displayName;
        $this->loadConfig();
    }
    
    /**
     * Get the shipping method name
     * 
     * @return string The shipping method name
     */
    public function getName() {
        return $this->name;
    }
    
    /**
     * Get the shipping method display name
     * 
     * @return string The display name for the shipping method
     */
    public function getDisplayName() {
        return $this->displayName;
    }
    
    /**
     * Check if the shipping method is enabled
     * 
     * @return bool True if enabled, false otherwise
     */
    public function isEnabled() {
        return $this->enabled;
    }
    
    /**
     * Get the shipping method configuration
     * 
     * @return array The shipping method configuration
     */
    public function getConfig() {
        return $this->config;
    }
    
    /**
     * Set the shipping method configuration
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
        $method_data = fearlesscommerce_get_shipping_method($this->name);
        if ($method_data) {
            $this->enabled = (bool) $method_data['enabled'];
            $this->config = json_decode($method_data['config'], true) ?: [];
        }
    }
    
    /**
     * Save configuration to database
     * 
     * @return bool True on success, false on failure
     */
    protected function saveConfig() {
        return fearlesscommerce_save_shipping_method($this->name, $this->displayName, $this->enabled, $this->config);
    }
    
    /**
     * Log shipping method activity
     * 
     * @param string $level Log level (info, warning, error)
     * @param string $message Log message
     * @param array $context Additional context data
     */
    protected function log($level, $message, $context = []) {
        if (getenv('FCMS_DEBUG') === 'true') {
            $logMessage = sprintf(
                "[FearlessCommerce Shipping %s] %s: %s %s",
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
     * Calculate total weight of items
     * 
     * @param array $items Array of items
     * @return float Total weight in kg
     */
    protected function calculateTotalWeight($items) {
        $totalWeight = 0;
        
        foreach ($items as $item) {
            // Assume each item has a weight field (in kg)
            // If not available, use a default weight
            $weight = $item['weight'] ?? 0.5; // Default 0.5kg per item
            $totalWeight += $weight * $item['quantity'];
        }
        
        return $totalWeight;
    }
    
    /**
     * Calculate total value of items
     * 
     * @param array $items Array of items
     * @return float Total value
     */
    protected function calculateTotalValue($items) {
        $totalValue = 0;
        
        foreach ($items as $item) {
            $totalValue += $item['price_at_purchase'] * $item['quantity'];
        }
        
        return $totalValue;
    }
    
    /**
     * Check if address is valid
     * 
     * @param array $address Address array
     * @return bool True if valid, false otherwise
     */
    protected function isValidAddress($address) {
        return !empty($address['country']) && 
               !empty($address['state']) && 
               !empty($address['city']) && 
               !empty($address['postal_code']);
    }
    
    /**
     * Get country code from address
     * 
     * @param array $address Address array
     * @return string Country code
     */
    protected function getCountryCode($address) {
        return strtoupper($address['country'] ?? '');
    }
    
    /**
     * Check if shipping is available to country
     * 
     * @param string $countryCode Country code
     * @param array $allowedCountries Array of allowed country codes
     * @return bool True if available, false otherwise
     */
    protected function isCountryAllowed($countryCode, $allowedCountries = []) {
        if (empty($allowedCountries)) {
            return true; // No restrictions
        }
        
        return in_array($countryCode, array_map('strtoupper', $allowedCountries));
    }
}