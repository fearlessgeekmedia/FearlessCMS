# FearlessCMS Cache System

The FearlessCMS cache system provides comprehensive caching capabilities to improve website performance and reduce server load.

## Features

- **Page Caching**: Cache rendered HTML pages
- **Asset Caching**: Cache CSS, JavaScript, and other static assets
- **Query Caching**: Cache database query results
- **Configurable Duration**: Set cache expiration in seconds, minutes, hours, or days
- **Storage Options**: File-based or memory-based caching
- **Automatic Cleanup**: Automatic cache cleanup when size limits are reached
- **Statistics**: Track cache hit rates and performance metrics

## Configuration

Cache settings are managed through the admin dashboard at `?action=manage_settings`. The configuration is stored in `config/cache.json`.

### Available Settings

- **Enable Caching**: Turn caching on/off globally
- **Cache Duration**: How long cached content remains valid
- **Cache Pages**: Enable/disable page caching
- **Cache Assets**: Enable/disable asset caching
- **Cache Queries**: Enable/disable query caching
- **Enable Compression**: Compress cached content
- **Cache Storage**: Choose between file system or memory storage
- **Maximum Cache Size**: Set cache size limits (e.g., 100MB, 1GB)

## Usage

### Basic Caching

```php
// Include the cache helper
require_once 'includes/cache.php';

// Check if caching is enabled
if (is_cache_enabled()) {
    // Try to get cached content
    $cached = get_cached_page($url);
    if ($cached !== false) {
        echo $cached;
        exit;
    }
    
    // Generate content if not cached
    $content = generate_page_content();
    
    // Cache the content
    cache_page($url, $content);
    
    echo $content;
}
```

### Asset Caching

```php
// Cache CSS/JS files
$cssContent = file_get_contents('style.css');
cache_asset('style.css', $cssContent);

// Retrieve cached asset
$cachedCSS = get_cached_asset('style.css');
if ($cachedCSS !== false) {
    echo $cachedCSS;
}
```

### Query Caching

```php
// Cache database query results
$query = "SELECT * FROM users WHERE active = 1";
$cached = get_cached_query($query);

if ($cached === false) {
    $result = $database->query($query);
    cache_query($query, $result);
    $cached = $result;
}

// Use $cached result
```

## Cache Management

### Admin Dashboard

Access cache management through the admin dashboard:

1. Go to `?action=manage_settings`
2. Configure cache settings in the "Cache Settings" section
3. Use "Cache Management" to monitor and control cache

### Manual Cache Control

```php
// Clear specific cache
clear_cache('cache_key');

// Clear all cache
clear_all_cache();

// Get cache statistics
$stats = $cacheManager->getStats();
```

## Cache Files

Cache files are stored in the `cache/` directory:

- `cache_stats.json`: Cache performance statistics
- `cache_*.html`: Cached page content
- `cache_*.asset`: Cached assets
- `cache_*.query`: Cached query results

## Performance Monitoring

The cache system provides real-time statistics:

- **Cache Status**: Excellent, Good, Fair, or Poor based on hit rate
- **Cache Size**: Current cache directory size
- **Hit Rate**: Percentage of cache hits vs. total requests
- **Last Cleared**: When cache was last cleared

## Best Practices

1. **Enable caching** for production environments
2. **Set appropriate cache duration** based on content update frequency
3. **Monitor cache hit rates** to optimize performance
4. **Clear cache** when content is updated
5. **Use cache compression** for better storage efficiency
6. **Set reasonable size limits** to prevent disk space issues

## Troubleshooting

### Cache Not Working

1. Check if caching is enabled in admin settings
2. Verify cache directory permissions (755)
3. Check cache configuration file exists
4. Review error logs for cache-related errors

### High Memory Usage

1. Reduce cache duration
2. Enable cache compression
3. Lower maximum cache size
4. Switch to file-based storage

### Cache Corruption

1. Clear all cache from admin dashboard
2. Check disk space availability
3. Verify file system permissions
4. Review cache cleanup settings

## API Reference

### CacheManager Class

- `getConfig()`: Get current cache configuration
- `updateConfig($config)`: Update cache settings
- `getStats()`: Get cache statistics
- `clearCache()`: Clear all cache
- `clearCacheStats()`: Reset cache statistics
- `isEnabled()`: Check if caching is enabled
- `getCacheStatus()`: Get cache performance status

### Helper Functions

- `is_cache_enabled()`: Check global cache status
- `cache_page($url, $content)`: Cache page content
- `get_cached_page($url)`: Retrieve cached page
- `cache_asset($path, $content)`: Cache asset
- `get_cached_asset($path)`: Retrieve cached asset
- `cache_query($query, $result)`: Cache query result
- `get_cached_query($query)`: Retrieve cached query
- `clear_all_cache()`: Clear all cache files

## Security Considerations

- Cache files are stored in a protected directory
- Admin access required for cache management
- CSRF protection on all cache operations
- Cache content is not executable
- Regular cache cleanup prevents disk space abuse 