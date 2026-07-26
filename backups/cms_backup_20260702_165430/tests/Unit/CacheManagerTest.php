<?php

/**
 * Tests for CacheManager
 */

beforeEach(function () {
    // Ensure a clean cache config for every test
    $cacheConfigFile = FCMS_TEST_DIR . '/config/cache.json';
    if (file_exists($cacheConfigFile)) {
        unlink($cacheConfigFile);
    }

    // Clean the cache directory
    $cacheDir = FCMS_TEST_DIR . '/cache';
    if (is_dir($cacheDir)) {
        foreach (glob($cacheDir . '/*') as $f) {
            if (is_file($f)) {
                unlink($f);
            }
        }
    }

    require_once __DIR__ . '/../../includes/CacheManager.php';
    $this->cacheManager = new CacheManager();
});

test('CacheManager creates default config when none exists', function () {
    $config = $this->cacheManager->getConfig();
    expect($config)->toBeArray()
        ->and($config['enabled'])->toBeTrue()
        ->and($config['cache_duration'])->toBe(3600)
        ->and($config['cache_pages'])->toBeTrue();
});

test('getCacheDuration converts units correctly', function () {
    $this->cacheManager->updateConfig(['cache_duration' => 10, 'cache_duration_unit' => 'minutes']);
    expect($this->cacheManager->getCacheDuration())->toBe(600);

    $this->cacheManager->updateConfig(['cache_duration' => 2, 'cache_duration_unit' => 'hours']);
    expect($this->cacheManager->getCacheDuration())->toBe(7200);

    $this->cacheManager->updateConfig(['cache_duration' => 1, 'cache_duration_unit' => 'days']);
    expect($this->cacheManager->getCacheDuration())->toBe(86400);

    $this->cacheManager->updateConfig(['cache_duration' => 500, 'cache_duration_unit' => 'seconds']);
    expect($this->cacheManager->getCacheDuration())->toBe(500);
});

test('isEnabled reflects config', function () {
    expect($this->cacheManager->isEnabled())->toBeTrue();

    $this->cacheManager->updateConfig(['enabled' => false]);
    expect($this->cacheManager->isEnabled())->toBeFalse();
});

test('recordHit and recordMiss update stats', function () {
    $this->cacheManager->recordHit();
    $this->cacheManager->recordHit();
    $this->cacheManager->recordMiss();

    $stats = $this->cacheManager->getStats();
    expect($stats['total_requests'])->toBe(3)
        ->and($stats['cache_hits'])->toBe(2)
        ->and($stats['cache_misses'])->toBe(1);
});

test('hit rate is calculated correctly', function () {
    $this->cacheManager->recordHit();
    $this->cacheManager->recordHit();
    $this->cacheManager->recordHit();
    $this->cacheManager->recordMiss();

    $stats = $this->cacheManager->getStats();
    expect($stats['hit_rate'])->toBe(75.0);
});

test('clearCache removes cached files', function () {
    $cacheDir = $this->cacheManager->getCacheDir();
    file_put_contents($cacheDir . '/page_abc123.html', '<html>cached</html>');
    file_put_contents($cacheDir . '/page_def456.html', '<html>cached2</html>');

    $cleared = $this->cacheManager->clearCache();
    expect($cleared)->toBe(2)
        ->and(file_exists($cacheDir . '/page_abc123.html'))->toBeFalse();
});

test('clearCache does not remove stats file', function () {
    $cacheDir = $this->cacheManager->getCacheDir();
    file_put_contents($cacheDir . '/page_test.html', 'data');
    $this->cacheManager->clearCache();

    expect(file_exists($cacheDir . '/cache_stats.json'))->toBeTrue();
});

test('getCacheStatus returns Disabled when cache is off', function () {
    $this->cacheManager->updateConfig(['enabled' => false]);
    expect($this->cacheManager->getCacheStatus())->toBe('Disabled');
});

test('getCacheStatus returns No Activity with zero requests', function () {
    $this->cacheManager->clearCacheStats();
    expect($this->cacheManager->getCacheStatus())->toBe('No Activity');
});

test('getCacheStatus returns Poor with 0% hit rate', function () {
    $this->cacheManager->clearCacheStats();
    $this->cacheManager->recordMiss();
    expect($this->cacheManager->getCacheStatus())->toBe('Poor');
});

test('updateConfig merges with existing config', function () {
    $this->cacheManager->updateConfig(['cache_max_size' => '200MB']);
    $config = $this->cacheManager->getConfig();

    expect($config['cache_max_size'])->toBe('200MB')
        ->and($config['enabled'])->toBeTrue(); // original value preserved
});
