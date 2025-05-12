<?php

return [
    'default_url' => env('STORE_URL', 'https://store.fearlesscms.com'),
    'cache_time' => env('STORE_CACHE_TIME', 3600), // 1 hour
    'sync_interval' => env('STORE_SYNC_INTERVAL', 86400), // 24 hours
    'storage_path' => storage_path('app/store'),
]; 