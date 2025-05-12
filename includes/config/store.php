<?php

// Store Configuration
define('STORE_URL', 'https://github.com/fearlessgeekmedia/fearlesscms-store');
define('STORE_CACHE_TIME', 3600); // 1 hour
define('STORE_SYNC_INTERVAL', 86400); // 24 hours
define('STORE_STORAGE_PATH', __DIR__ . '/../../storage/store');

// Create store directory if it doesn't exist
if (!is_dir(STORE_STORAGE_PATH)) {
    mkdir(STORE_STORAGE_PATH, 0755, true);
} 