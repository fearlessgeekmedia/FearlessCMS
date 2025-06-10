<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';
require_once 'admin/store-handler.php';

// Test store URL
$storeUrl = defined('STORE_URL') ? STORE_URL : 'https://github.com/fearlessgeekmedia/FearlessCMS-Store.git';
echo "Store URL: " . $storeUrl . "\n";

// Test fetching store content
$storeContent = fetch_github_content('store.json');
if ($storeContent === false) {
    echo "Failed to fetch store content\n";
    exit;
}

// Test parsing store content
$store = json_decode($storeContent, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON decode error: " . json_last_error_msg() . "\n";
    exit;
}

// Test searching plugins
$results = search_plugins('test');
echo "Search results: " . print_r($results, true) . "\n"; 