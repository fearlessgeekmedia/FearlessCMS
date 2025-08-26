<?php

/**
 * Simple cache helper functions
 */

/**
 * Get cache configuration
 */
function get_cache_config() {
    $configFile = CONFIG_DIR . '/cache.json';
    if (file_exists($configFile)) {
        return json_decode(file_get_contents($configFile), true) ?: [];
    }
    return [
        'enabled' => false,
        'cache_duration' => 3600,
        'cache_pages' => false,
        'cache_assets' => false,
        'cache_queries' => false
    ];
}

/**
 * Check if caching is enabled
 */
function is_cache_enabled() {
    $config = get_cache_config();
    return $config['enabled'] ?? false;
}

/**
 * Get cache duration in seconds
 */
function get_cache_duration() {
    $config = get_cache_config();
    $duration = $config['cache_duration'] ?? 3600;
    $unit = $config['cache_duration_unit'] ?? 'seconds';
    
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
 * Generate cache key for content
 */
function generate_cache_key($content, $additional = '') {
    return 'cache_' . md5($content . $additional) . '.html';
}

/**
 * Get cache file path
 */
function get_cache_file_path($key) {
    $cacheDir = dirname(__DIR__) . '/cache';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    return $cacheDir . '/' . $key;
}

/**
 * Check if cache exists and is valid
 */
function cache_exists($key) {
    if (!is_cache_enabled()) {
        return false;
    }
    
    $cacheFile = get_cache_file_path($key);
    if (!file_exists($cacheFile)) {
        return false;
    }
    
    $cacheTime = filemtime($cacheFile);
    $cacheDuration = get_cache_duration();
    
    return (time() - $cacheTime) < $cacheDuration;
}

/**
 * Get cached content
 */
function get_cached_content($key) {
    if (!cache_exists($key)) {
        return false;
    }
    
    $cacheFile = get_cache_file_path($key);
    return file_get_contents($cacheFile);
}

/**
 * Set cached content
 */
function set_cached_content($key, $content) {
    if (!is_cache_enabled()) {
        return false;
    }
    
    $cacheFile = get_cache_file_path($key);
    return file_put_contents($cacheFile, $content);
}

/**
 * Clear specific cache
 */
function clear_cache($key) {
    $cacheFile = get_cache_file_path($key);
    if (file_exists($cacheFile)) {
        return unlink($cacheFile);
    }
    return false;
}

/**
 * Clear all cache
 */
function clear_all_cache() {
    $cacheDir = dirname(__DIR__) . '/cache';
    if (!is_dir($cacheDir)) {
        return 0;
    }
    
    $cleared = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getFilename() !== 'cache_stats.json') {
            if (unlink($file->getRealPath())) {
                $cleared++;
            }
        }
    }
    
    return $cleared;
}

/**
 * Cache page content if enabled
 */
function cache_page($url, $content) {
    if (!is_cache_enabled()) {
        return false;
    }
    
    $config = get_cache_config();
    if (!($config['cache_pages'] ?? false)) {
        return false;
    }
    
    $key = generate_cache_key($url);
    return set_cached_content($key, $content);
}

/**
 * Get cached page if available
 */
function get_cached_page($url) {
    if (!is_cache_enabled()) {
        return false;
    }
    
    $config = get_cache_config();
    if (!($config['cache_pages'] ?? false)) {
        return false;
    }
    
    $key = generate_cache_key($url);
    return get_cached_content($key);
}

/**
 * Cache asset if enabled
 */
function cache_asset($path, $content) {
    if (!is_cache_enabled()) {
        return false;
    }
    
    $config = get_cache_config();
    if (!($config['cache_assets'] ?? false)) {
        return false;
    }
    
    $key = generate_cache_key($path, 'asset');
    return set_cached_content($key, $content);
}

/**
 * Get cached asset if available
 */
function get_cached_asset($path) {
    if (!is_cache_enabled()) {
        return false;
    }
    
    $config = get_cache_config();
    if (!($config['cache_assets'] ?? false)) {
        return false;
    }
    
    $key = generate_cache_key($path, 'asset');
    return get_cached_content($key);
}

/**
 * Cache query result if enabled
 */
function cache_query($query, $result) {
    if (!is_cache_enabled()) {
        return false;
    }
    
    $config = get_cache_config();
    if (!($config['cache_queries'] ?? false)) {
        return false;
    }
    
    $key = generate_cache_key($query, 'query');
    return set_cached_content($key, serialize($result));
}

/**
 * Get cached query result if available
 */
function get_cached_query($query) {
    if (!is_cache_enabled()) {
        return false;
    }
    
    $config = get_cache_config();
    if (!($config['cache_queries'] ?? false)) {
        return false;
    }
    
    $key = generate_cache_key($query, 'query');
    $cached = get_cached_content($key);
    if ($cached !== false) {
        return unserialize($cached);
    }
    return false;
} 