<?php

/**
 * FearlessCMS
 *
 * Copyright (C) 2026 FearlessCMS
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * As a special exception to the GNU General Public License version 2
 * ("GPL"), the copyright holder(s) of FearlessCMS give you permission
 * to link, load, or combine this software with independent modules
 * ("Themes" and "Plugins") that are not derived from or based on this
 * software, regardless of the license terms of those Themes or
 * Plugins, including proprietary or commercial licenses, and to
 * convey the resulting combination under terms of your choice,
 * provided that you also convey the corresponding source code of the
 * FearlessCMS portions under the terms of the GPL.
 *
 * This exception does not affect any of your obligations under the
 * GPL with respect to the FearlessCMS core software itself, and it
 * does not extend to modifications of FearlessCMS core files — only
 * to independent Theme and Plugin modules that interact with
 * FearlessCMS through its documented Theme/Plugin API.
 */

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