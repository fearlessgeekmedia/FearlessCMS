<?php
/**
 * Weight-Based Shipping Method for FearlessCommerce
 * 
 * This class implements weight-based shipping for FearlessCommerce.
 */

require_once 'BaseShippingMethod.php';

class WeightBasedShipping extends BaseShippingMethod {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('weight_based', 'Weight-Based Shipping');
    }
    
    /**
     * Calculate shipping cost for given items and address
     * 
     * @param array $items Array of items with product data
     * @param array $address Shipping address
     * @return array Result array with 'available', 'cost', 'estimated_days', 'message'
     */
    public function calculateShipping($items, $address) {
        $this->log('info', 'Calculating weight-based shipping', ['items_count' => count($items), 'address' => $address]);
        
        if (!$this->isEnabled()) {
            return [
                'available' => false,
                'message' => 'Weight-based shipping is not enabled'
            ];
        }
        
        if (!$this->isAvailableForAddress($address)) {
            return [
                'available' => false,
                'message' => 'Weight-based shipping is not available for this address'
            ];
        }
        
        $validation = $this->validateConfig();
        if (!$validation['valid']) {
            return [
                'available' => false,
                'message' => 'Invalid configuration: ' . implode(', ', $validation['errors'])
            ];
        }
        
        $totalWeight = $this->calculateTotalWeight($items);
        
        // Check weight limits
        if (!empty($this->config['max_weight']) && $totalWeight > $this->config['max_weight']) {
            return [
                'available' => false,
                'message' => 'Order weight exceeds maximum allowed weight'
            ];
        }
        
        // Calculate cost: base cost + (weight * cost per kg)
        $baseCost = $this->config['base_cost'] ?? 0;
        $costPerKg = $this->config['cost_per_kg'] ?? 0;
        $cost = $baseCost + ($totalWeight * $costPerKg);
        
        // Apply minimum cost if configured
        if (!empty($this->config['min_cost']) && $cost < $this->config['min_cost']) {
            $cost = $this->config['min_cost'];
        }
        
        // Apply maximum cost if configured
        if (!empty($this->config['max_cost']) && $cost > $this->config['max_cost']) {
            $cost = $this->config['max_cost'];
        }
        
        $estimatedDays = $this->getEstimatedDeliveryDays($address);
        
        $this->log('info', 'Weight-based shipping calculated', [
            'total_weight' => $totalWeight,
            'base_cost' => $baseCost,
            'cost_per_kg' => $costPerKg,
            'final_cost' => $cost
        ]);
        
        return [
            'available' => true,
            'cost' => $cost,
            'estimated_days' => $estimatedDays,
            'message' => 'Weight-based shipping calculated successfully'
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
        $countryCode = $this->getCountryCode($address);
        
        // Delivery times based on country and weight
        $baseDeliveryTimes = [
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
        
        $baseDays = $baseDeliveryTimes[$countryCode] ?? $this->config['default_delivery_days'] ?? 7;
        
        // Add extra days for heavier packages
        $weightMultiplier = $this->config['weight_delivery_multiplier'] ?? 0;
        if ($weightMultiplier > 0) {
            // This would need the total weight, but we don't have it in this context
            // In a real implementation, you might want to pass the weight as a parameter
        }
        
        return $baseDays;
    }
    
    /**
     * Validate configuration
     * 
     * @return array Result array with 'valid', 'errors'
     */
    public function validateConfig() {
        $requiredFields = ['base_cost', 'cost_per_kg'];
        $errors = $this->validateRequiredFields($requiredFields);
        
        // Validate base cost
        if (!empty($this->config['base_cost']) && $this->config['base_cost'] < 0) {
            $errors[] = 'Base cost must be a positive number';
        }
        
        // Validate cost per kg
        if (!empty($this->config['cost_per_kg']) && $this->config['cost_per_kg'] < 0) {
            $errors[] = 'Cost per kg must be a positive number';
        }
        
        // Validate max weight
        if (!empty($this->config['max_weight']) && $this->config['max_weight'] <= 0) {
            $errors[] = 'Maximum weight must be a positive number';
        }
        
        // Validate min cost
        if (!empty($this->config['min_cost']) && $this->config['min_cost'] < 0) {
            $errors[] = 'Minimum cost must be a positive number';
        }
        
        // Validate max cost
        if (!empty($this->config['max_cost']) && $this->config['max_cost'] < 0) {
            $errors[] = 'Maximum cost must be a positive number';
        }
        
        // Validate min/max cost relationship
        if (!empty($this->config['min_cost']) && !empty($this->config['max_cost']) && 
            $this->config['min_cost'] > $this->config['max_cost']) {
            $errors[] = 'Minimum cost cannot be greater than maximum cost';
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
            'base_cost' => [
                'type' => 'number',
                'label' => 'Base Cost',
                'required' => true,
                'step' => '0.01',
                'min' => '0',
                'description' => 'Base shipping cost regardless of weight'
            ],
            'cost_per_kg' => [
                'type' => 'number',
                'label' => 'Cost per Kilogram',
                'required' => true,
                'step' => '0.01',
                'min' => '0',
                'description' => 'Additional cost per kilogram of weight'
            ],
            'max_weight' => [
                'type' => 'number',
                'label' => 'Maximum Weight (kg)',
                'required' => false,
                'step' => '0.1',
                'min' => '0',
                'description' => 'Maximum weight allowed for this shipping method (leave empty for no limit)'
            ],
            'min_cost' => [
                'type' => 'number',
                'label' => 'Minimum Cost',
                'required' => false,
                'step' => '0.01',
                'min' => '0',
                'description' => 'Minimum shipping cost (leave empty for no minimum)'
            ],
            'max_cost' => [
                'type' => 'number',
                'label' => 'Maximum Cost',
                'required' => false,
                'step' => '0.01',
                'min' => '0',
                'description' => 'Maximum shipping cost (leave empty for no maximum)'
            ],
            'default_delivery_days' => [
                'type' => 'number',
                'label' => 'Default Delivery Days',
                'required' => false,
                'min' => '1',
                'description' => 'Default delivery time in days for countries not specifically configured'
            ],
            'weight_delivery_multiplier' => [
                'type' => 'number',
                'label' => 'Weight Delivery Multiplier',
                'required' => false,
                'step' => '0.1',
                'min' => '0',
                'description' => 'Additional days per kg of weight (leave empty to disable)'
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