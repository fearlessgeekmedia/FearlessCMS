<?php
/**
 * Flat Rate Shipping Method for FearlessCommerce
 * 
 * This class implements flat rate shipping for FearlessCommerce.
 */

require_once 'BaseShippingMethod.php';

class FlatRateShipping extends BaseShippingMethod {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('flat_rate', 'Flat Rate Shipping');
    }
    
    /**
     * Calculate shipping cost for given items and address
     * 
     * @param array $items Array of items with product data
     * @param array $address Shipping address
     * @return array Result array with 'available', 'cost', 'estimated_days', 'message'
     */
    public function calculateShipping($items, $address) {
        $this->log('info', 'Calculating flat rate shipping', ['items_count' => count($items), 'address' => $address]);
        
        if (!$this->isEnabled()) {
            return [
                'available' => false,
                'message' => 'Flat rate shipping is not enabled'
            ];
        }
        
        if (!$this->isAvailableForAddress($address)) {
            return [
                'available' => false,
                'message' => 'Flat rate shipping is not available for this address'
            ];
        }
        
        $validation = $this->validateConfig();
        if (!$validation['valid']) {
            return [
                'available' => false,
                'message' => 'Invalid configuration: ' . implode(', ', $validation['errors'])
            ];
        }
        
        $totalValue = $this->calculateTotalValue($items);
        $cost = $this->config['cost'];
        
        // Check for free shipping threshold
        if (!empty($this->config['free_shipping_threshold']) && 
            $totalValue >= $this->config['free_shipping_threshold']) {
            $cost = 0;
            $this->log('info', 'Free shipping applied', ['total_value' => $totalValue, 'threshold' => $this->config['free_shipping_threshold']]);
        }
        
        $estimatedDays = $this->getEstimatedDeliveryDays($address);
        
        return [
            'available' => true,
            'cost' => $cost,
            'estimated_days' => $estimatedDays,
            'message' => 'Flat rate shipping calculated successfully'
        ];
    }
    
    /**
     * Check if shipping method is available for given address
     * 
     * @param array $address Shipping address
     * @return bool True if available, false otherwise
     */
    public function isAvailableForAddress($address) {
        if (!$this->isValidAddress($address)) {
            return false;
        }
        
        // Check if country is allowed (if restrictions are configured)
        $allowedCountries = $this->config['allowed_countries'] ?? [];
        if (!empty($allowedCountries)) {
            $countryCode = $this->getCountryCode($address);
            if (!$this->isCountryAllowed($countryCode, $allowedCountries)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get estimated delivery days
     * 
     * @param array $address Shipping address
     * @return int|null Estimated delivery days or null if not available
     */
    public function getEstimatedDeliveryDays($address) {
        // Default delivery times based on country
        $countryCode = $this->getCountryCode($address);
        
        $deliveryTimes = [
            'US' => 3,
            'CA' => 5,
            'GB' => 7,
            'AU' => 10,
            'DE' => 5,
            'FR' => 5,
            'IT' => 5,
            'ES' => 5,
            'NL' => 5,
            'BE' => 5,
            'AT' => 5,
            'CH' => 5,
            'SE' => 7,
            'NO' => 7,
            'DK' => 7,
            'FI' => 7
        ];
        
        return $deliveryTimes[$countryCode] ?? $this->config['default_delivery_days'] ?? 7;
    }
    
    /**
     * Validate configuration
     * 
     * @return array Result array with 'valid', 'errors'
     */
    public function validateConfig() {
        $requiredFields = ['cost'];
        $errors = $this->validateRequiredFields($requiredFields);
        
        // Validate cost
        if (!empty($this->config['cost']) && $this->config['cost'] < 0) {
            $errors[] = 'Cost must be a positive number';
        }
        
        // Validate free shipping threshold
        if (!empty($this->config['free_shipping_threshold']) && $this->config['free_shipping_threshold'] < 0) {
            $errors[] = 'Free shipping threshold must be a positive number';
        }
        
        // Validate default delivery days
        if (!empty($this->config['default_delivery_days']) && $this->config['default_delivery_days'] < 1) {
            $errors[] = 'Default delivery days must be at least 1';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Get configuration form fields
     * 
     * @return array Array of form field configurations
     */
    public function getConfigFields() {
        return [
            'cost' => [
                'type' => 'number',
                'label' => 'Shipping Cost',
                'required' => true,
                'step' => '0.01',
                'min' => '0',
                'description' => 'Fixed shipping cost in your store currency'
            ],
            'free_shipping_threshold' => [
                'type' => 'number',
                'label' => 'Free Shipping Threshold',
                'required' => false,
                'step' => '0.01',
                'min' => '0',
                'description' => 'Order value threshold for free shipping (leave empty to disable)'
            ],
            'default_delivery_days' => [
                'type' => 'number',
                'label' => 'Default Delivery Days',
                'required' => false,
                'min' => '1',
                'description' => 'Default delivery time in days for countries not specifically configured'
            ],
            'allowed_countries' => [
                'type' => 'select_multiple',
                'label' => 'Allowed Countries',
                'required' => false,
                'options' => $this->getCountryOptions(),
                'description' => 'Countries where this shipping method is available (leave empty for all countries)'
            ]
        ];
    }
    
    /**
     * Get country options for configuration
     * 
     * @return array Array of country options
     */
    private function getCountryOptions() {
        return [
            'US' => 'United States',
            'CA' => 'Canada',
            'GB' => 'United Kingdom',
            'AU' => 'Australia',
            'DE' => 'Germany',
            'FR' => 'France',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'NL' => 'Netherlands',
            'BE' => 'Belgium',
            'AT' => 'Austria',
            'CH' => 'Switzerland',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'FI' => 'Finland'
        ];
    }
}