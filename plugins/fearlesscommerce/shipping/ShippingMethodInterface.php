<?php
/**
 * Shipping Method Interface for FearlessCommerce
 * 
 * This interface defines the contract that all shipping methods must implement
 * to be compatible with FearlessCommerce.
 */

interface ShippingMethodInterface {
    
    /**
     * Get the shipping method name
     * 
     * @return string The shipping method name
     */
    public function getName();
    
    /**
     * Get the shipping method display name
     * 
     * @return string The display name for the shipping method
     */
    public function getDisplayName();
    
    /**
     * Check if the shipping method is enabled
     * 
     * @return bool True if enabled, false otherwise
     */
    public function isEnabled();
    
    /**
     * Get the shipping method configuration
     * 
     * @return array The shipping method configuration
     */
    public function getConfig();
    
    /**
     * Set the shipping method configuration
     * 
     * @param array $config The configuration array
     * @return bool True on success, false on failure
     */
    public function setConfig($config);
    
    /**
     * Calculate shipping cost for given items and address
     * 
     * @param array $items Array of items with product data
     * @param array $address Shipping address
     * @return array Result array with 'available', 'cost', 'estimated_days', 'message'
     */
    public function calculateShipping($items, $address);
    
    /**
     * Check if shipping method is available for given address
     * 
     * @param array $address Shipping address
     * @return bool True if available, false otherwise
     */
    public function isAvailableForAddress($address);
    
    /**
     * Get estimated delivery days
     * 
     * @param array $address Shipping address
     * @return int|null Estimated delivery days or null if not available
     */
    public function getEstimatedDeliveryDays($address);
    
    /**
     * Validate configuration
     * 
     * @return array Result array with 'valid', 'errors'
     */
    public function validateConfig();
}