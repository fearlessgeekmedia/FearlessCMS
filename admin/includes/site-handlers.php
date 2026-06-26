<?php
/**
 * Site Settings and Cache Handlers for FearlessCMS Admin
 */

// Handle POST for site name and tagline update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_site_name') {
    $newSiteName = trim($_POST['site_name'] ?? '');
    $newTagline = trim($_POST['site_description'] ?? '');
    $configFile = CONFIG_DIR . '/config.json';
    $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
    if ($newSiteName !== '') {
        $config['site_name'] = $newSiteName;
    }
    $config['site_description'] = $newTagline;
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    
    // Update variables for current request
    $siteName = $config['site_name'];
    $siteDescription = $config['site_description'];
    $success = 'Site name and tagline updated.';
}

// Handle POST for cache settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_cache_settings') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to update cache settings';
    } elseif (!validate_csrf_token()) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $cacheConfig = [
            'enabled' => isset($_POST['cache_enabled']),
            'cache_duration' => (int)($_POST['cache_duration'] ?? 3600),
            'cache_duration_unit' => $_POST['cache_duration_unit'] ?? 'seconds',
            'cache_pages' => isset($_POST['cache_pages']),
            'cache_assets' => isset($_POST['cache_assets']),
            'cache_queries' => isset($_POST['cache_queries']),
            'cache_compression' => isset($_POST['cache_compression']),
            'cache_storage' => $_POST['cache_storage'] ?? 'file',
            'cache_max_size' => $_POST['cache_max_size'] ?? '100MB'
        ];
        
        $cacheManager->updateConfig($cacheConfig);
        $success = 'Cache settings updated successfully.';
    }
}

// Handle POST for clearing cache
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_cache') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to clear cache';
    } elseif (!validate_csrf_token()) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $cleared = $cacheManager->clearCache();
        $success = "Cache cleared successfully. {$cleared} files removed.";
        
        // Refresh cache statistics after clearing
        $cacheStats = $cacheManager->getStats();
        $cacheStatus = $cacheManager->getCacheStatus();
        $cacheSize = $cacheManager->getCacheSize();
        $cacheLastCleared = $cacheManager->getLastCleared();
    }
}

// Handle POST for clearing cache stats
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_cache_stats') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to clear cache statistics';
    } elseif (!validate_csrf_token()) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $cacheManager->clearCacheStats();
        $success = 'Cache statistics cleared successfully.';
        
        // Refresh cache statistics after clearing stats
        $cacheStats = $cacheManager->getStats();
        $cacheStatus = $cacheManager->getCacheStatus();
        $cacheSize = $cacheManager->getCacheSize();
        $cacheLastCleared = $cacheManager->getLastCleared();
    }
}
