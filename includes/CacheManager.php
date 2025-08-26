<?php

class CacheManager {
    private $config;
    private $cacheDir;
    private $statsFile;
    
    public function __construct() {
        $this->loadConfig();
        $this->cacheDir = dirname(__DIR__) . '/cache';
        $this->statsFile = $this->cacheDir . '/cache_stats.json';
        
        // Ensure cache directory exists
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        // Initialize stats if they don't exist
        if (!file_exists($this->statsFile)) {
            $this->initializeStats();
        }
    }
    
    /**
     * Load cache configuration
     */
    private function loadConfig() {
        $configFile = dirname(__DIR__) . '/config/cache.json';
        if (file_exists($configFile)) {
            $this->config = json_decode(file_get_contents($configFile), true) ?: [];
        } else {
            // Default configuration
            $this->config = [
                'enabled' => true,
                'cache_duration' => 3600,
                'cache_duration_unit' => 'seconds',
                'cache_pages' => true,
                'cache_assets' => true,
                'cache_queries' => false,
                'cache_compression' => true,
                'cache_headers' => true,
                'cache_clear_on_update' => true,
                'cache_auto_clear' => true,
                'cache_auto_clear_interval' => 86400,
                'cache_storage' => 'file',
                'cache_directory' => 'cache',
                'cache_max_size' => '100MB',
                'cache_cleanup_threshold' => 0.8
            ];
            $this->saveConfig();
        }
    }
    
    /**
     * Save cache configuration
     */
    public function saveConfig() {
        $configFile = dirname(__DIR__) . '/config/cache.json';
        file_put_contents($configFile, json_encode($this->config, JSON_PRETTY_PRINT));
    }
    
    /**
     * Get cache configuration
     */
    public function getConfig() {
        return $this->config;
    }
    
    /**
     * Get cache directory path
     */
    public function getCacheDir() {
        return $this->cacheDir;
    }
    
    /**
     * Update cache configuration
     */
    public function updateConfig($newConfig) {
        $this->config = array_merge($this->config, $newConfig);
        $this->saveConfig();
    }
    
    /**
     * Initialize cache statistics
     */
    private function initializeStats() {
        $stats = [
            'total_requests' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'cache_size' => 0,
            'last_cleared' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        file_put_contents($this->statsFile, json_encode($stats, JSON_PRETTY_PRINT));
    }
    
    /**
     * Get cache statistics
     */
    public function getStats() {
        if (file_exists($this->statsFile)) {
            $stats = json_decode(file_get_contents($this->statsFile), true) ?: [];
            $stats['cache_size'] = $this->getCacheSize();
            $stats['hit_rate'] = $stats['total_requests'] > 0 ? 
                round(($stats['cache_hits'] / $stats['total_requests']) * 100, 2) : 0;
            return $stats;
        }
        return [];
    }
    
    /**
     * Get current cache size
     */
    public function getCacheSize() {
        $size = 0;
        if (is_dir($this->cacheDir)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->cacheDir));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getFilename() !== 'cache_stats.json') {
                    $size += $file->getSize();
                }
            }
        }
        return $this->formatBytes($size);
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Clear all cache
     */
    public function clearCache() {
        $cleared = 0;
        if (is_dir($this->cacheDir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getFilename() !== 'cache_stats.json') {
                    if (unlink($file->getRealPath())) {
                        $cleared++;
                    }
                }
            }
        }
        
        // Update stats
        $this->updateStats(['last_cleared' => date('Y-m-d H:i:s')]);
        
        return $cleared;
    }
    
    /**
     * Clear cache statistics
     */
    public function clearCacheStats() {
        $this->initializeStats();
        return true;
    }
    
    /**
     * Update cache statistics
     */
    private function updateStats($updates) {
        $stats = $this->getStats();
        $stats = array_merge($stats, $updates);
        file_put_contents($this->statsFile, json_encode($stats, JSON_PRETTY_PRINT));
    }
    
    /**
     * Record cache hit
     */
    public function recordHit() {
        $stats = $this->getStats();
        $stats['total_requests']++;
        $stats['cache_hits']++;
        file_put_contents($this->statsFile, json_encode($stats, JSON_PRETTY_PRINT));
    }
    
    /**
     * Record cache miss
     */
    public function recordMiss() {
        $stats = $this->getStats();
        $stats['total_requests']++;
        $stats['cache_misses']++;
        file_put_contents($this->statsFile, json_encode($stats, JSON_PRETTY_PRINT));
    }
    
    /**
     * Check if cache is enabled
     */
    public function isEnabled() {
        return $this->config['enabled'] ?? false;
    }
    
    /**
     * Get cache duration in seconds
     */
    public function getCacheDuration() {
        $duration = $this->config['cache_duration'] ?? 3600;
        $unit = $this->config['cache_duration_unit'] ?? 'seconds';
        
        switch ($unit) {
            case 'minutes':
                return $duration * 60;
            case 'hours':
                return $duration * 3600;
            case 'days':
                return $duration * 86400;
            default:
                return $duration;
        }
    }
    
    /**
     * Get cache status
     */
    public function getCacheStatus() {
        if (!$this->isEnabled()) {
            return 'Disabled';
        }
        
        $stats = $this->getStats();
        if ($stats['total_requests'] === 0) {
            return 'No Activity';
        }
        
        $hitRate = $stats['hit_rate'] ?? 0;
        if ($hitRate >= 80) {
            return 'Excellent';
        } elseif ($hitRate >= 60) {
            return 'Good';
        } elseif ($hitRate >= 40) {
            return 'Fair';
        } else {
            return 'Poor';
        }
    }
    
    /**
     * Get cache last cleared time
     */
    public function getLastCleared() {
        $stats = $this->getStats();
        return $stats['last_cleared'] ?? 'Never';
    }
    
    /**
     * Check if cache needs cleanup
     */
    public function needsCleanup() {
        if (!$this->isEnabled()) {
            return false;
        }
        
        $maxSize = $this->parseSize($this->config['cache_max_size'] ?? '100MB');
        $currentSize = $this->getCacheSizeBytes();
        $threshold = $this->config['cache_cleanup_threshold'] ?? 0.8;
        
        return ($currentSize / $maxSize) > $threshold;
    }
    
    /**
     * Parse size string to bytes
     */
    private function parseSize($sizeStr) {
        $sizeStr = strtoupper(trim($sizeStr));
        $multiplier = 1;
        
        if (strpos($sizeStr, 'KB') !== false) {
            $multiplier = 1024;
            $sizeStr = str_replace('KB', '', $sizeStr);
        } elseif (strpos($sizeStr, 'MB') !== false) {
            $multiplier = 1024 * 1024;
            $sizeStr = str_replace('MB', '', $sizeStr);
        } elseif (strpos($sizeStr, 'GB') !== false) {
            $multiplier = 1024 * 1024 * 1024;
            $sizeStr = str_replace('GB', '', $sizeStr);
        }
        
        return (int)$sizeStr * $multiplier;
    }
    
    /**
     * Get cache size in bytes
     */
    private function getCacheSizeBytes() {
        $size = 0;
        if (is_dir($this->cacheDir)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->cacheDir));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getFilename() !== 'cache_stats.json') {
                    $size += $file->getSize();
                }
            }
        }
        return $size;
    }
    
    /**
     * Auto cleanup cache if needed
     */
    public function autoCleanup() {
        if ($this->needsCleanup()) {
            $this->clearCache();
            return true;
        }
        return false;
    }
} 