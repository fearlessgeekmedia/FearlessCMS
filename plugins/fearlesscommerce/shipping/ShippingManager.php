<?php
/**
 * Shipping Manager for FearlessCommerce
 * 
 * This class manages all shipping methods and provides a unified interface
 * for shipping calculations.
 */

require_once 'ShippingMethodInterface.php';
require_once 'BaseShippingMethod.php';
require_once 'FlatRateShipping.php';
require_once 'WeightBasedShipping.php';

class ShippingManager {
    
    private static $instance = null;
    private $shippingMethods = [];
    
    /**
     * Get singleton instance
     * 
     * @return ShippingManager
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
        $this->registerDefaultShippingMethods();
    }
    
    /**
     * Register default shipping methods
     */
    private function registerDefaultShippingMethods() {
        $this->registerShippingMethod(new FlatRateShipping());
        $this->registerShippingMethod(new WeightBasedShipping());
    }
    
    /**
     * Register a shipping method
     * 
     * @param ShippingMethodInterface $method The shipping method to register
     */
    public function registerShippingMethod(ShippingMethodInterface $method) {
        $this->shippingMethods[$method->getName()] = $method;
    }
    
    /**
     * Get all registered shipping methods
     * 
     * @return array Array of shipping methods
     */
    public function getAllShippingMethods() {
        return $this->shippingMethods;
    }
    
    /**
     * Get enabled shipping methods only
     * 
     * @return array Array of enabled shipping methods
     */
    public function getEnabledShippingMethods() {
        $enabled = [];
        foreach ($this->shippingMethods as $method) {
            if ($method->isEnabled()) {
                $enabled[$method->getName()] = $method;
            }
        }
        return $enabled;
    }
    
    /**
     * Get a specific shipping method
     * 
     * @param string $name Shipping method name
     * @return ShippingMethodInterface|null The shipping method or null if not found
     */
    public function getShippingMethod($name) {
        return isset($this->shippingMethods[$name]) ? $this->shippingMethods[$name] : null;
    }
    
    /**
     * Calculate shipping options for given items and address
     * 
     * @param array $items Array of items
     * @param array $address Shipping address
     * @return array Array of available shipping options
     */
    public function calculateShippingOptions($items, $address) {
        $shippingOptions = [];
        $enabledMethods = $this->getEnabledShippingMethods();
        
        foreach ($enabledMethods as $method) {
            $result = $method->calculateShipping($items, $address);
            
            if ($result['available']) {
                $shippingOptions[] = [
                    'method' => $method->getName(),
                    'display_name' => $method->getDisplayName(),
                    'cost' => $result['cost'],
                    'estimated_days' => $result['estimated_days'],
                    'message' => $result['message']
                ];
            }
        }
        
        // Sort by cost (lowest first)
        usort($shippingOptions, function($a, $b) {
            return $a['cost'] <=> $b['cost'];
        });
        
        return $shippingOptions;
    }
    
    /**
     * Calculate shipping cost for specific method
     * 
     * @param string $methodName Shipping method name
     * @param array $items Array of items
     * @param array $address Shipping address
     * @return array Result array
     */
    public function calculateShipping($methodName, $items, $address) {
        $method = $this->getShippingMethod($methodName);
        
        if (!$method) {
            return [
                'available' => false,
                'message' => 'Shipping method not found: ' . $methodName
            ];
        }
        
        if (!$method->isEnabled()) {
            return [
                'available' => false,
                'message' => 'Shipping method is not enabled: ' . $methodName
            ];
        }
        
        return $method->calculateShipping($items, $address);
    }
    
    /**
     * Get shipping method configuration form fields
     * 
     * @param string $methodName Shipping method name
     * @return array Array of form field configurations
     */
    public function getShippingMethodConfigFields($methodName) {
        $method = $this->getShippingMethod($methodName);
        
        if (!$method) {
            return [];
        }
        
        return $method->getConfigFields();
    }
    
    /**
     * Validate shipping method configuration
     * 
     * @param string $methodName Shipping method name
     * @return array Result array with 'valid', 'errors'
     */
    public function validateShippingMethodConfig($methodName) {
        $method = $this->getShippingMethod($methodName);
        
        if (!$method) {
            return [
                'valid' => false,
                'errors' => ['Shipping method not found: ' . $methodName]
            ];
        }
        
        return $method->validateConfig();
    }
    
    /**
     * Get shipping method status
     * 
     * @param string $methodName Shipping method name
     * @return array Status information
     */
    public function getShippingMethodStatus($methodName) {
        $method = $this->getShippingMethod($methodName);
        
        if (!$method) {
            return [
                'exists' => false,
                'enabled' => false,
                'valid_config' => false
            ];
        }
        
        $validation = $method->validateConfig();
        
        return [
            'exists' => true,
            'enabled' => $method->isEnabled(),
            'valid_config' => $validation['valid'],
            'config_errors' => $validation['errors'] ?? []
        ];
    }
    
    /**
     * Get all shipping method statuses
     * 
     * @return array Array of status information for all methods
     */
    public function getAllShippingMethodStatuses() {
        $statuses = [];
        
        foreach ($this->shippingMethods as $method) {
            $statuses[$method->getName()] = $this->getShippingMethodStatus($method->getName());
        }
        
        return $statuses;
    }
}