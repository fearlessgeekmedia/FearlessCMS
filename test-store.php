<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Fetch content from GitHub repository
 */
function fetch_github_content($path) {
    $storeUrl = 'https://github.com/fearlessgeekmedia/FearlessCMS-Store.git';
    
    // Transform GitHub repository URL to raw content URL (explicitly correct)
    $baseUrl = preg_replace('#https?://github\\.com/([^/]+)/([^/]+)\\.git#', 'https://raw.githubusercontent.com/$1/$2', $storeUrl) . '/main';
    $rawUrl = $baseUrl . '/' . $path;
    
    error_log("Fetching content from: " . $rawUrl);
    
    // Try cURL first if available
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $rawUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FearlessCMS/1.0');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json, text/plain, */*',
            'Accept-Language: en-US,en;q=0.9'
        ]);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        
        // Create a temporary file handle for CURL debug output
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);
        
        $content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        // Get the verbose debug information
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        
        error_log("CURL Verbose Log: " . $verboseLog);
        error_log("HTTP response code: " . $http_code);
        if ($error) {
            error_log("CURL error: " . $error);
        }
        error_log("Response content: " . substr($content, 0, 1000)); // Log first 1000 chars of response
        
        curl_close($ch);
        fclose($verbose);
        
        if ($http_code === 200) {
            return $content;
        }
    }
    
    // Fallback to file_get_contents
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: FearlessCMS/1.0',
                'Accept: application/json, text/plain, */*',
                'Accept-Language: en-US,en;q=0.9'
            ],
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    
    $content = @file_get_contents($rawUrl, false, $context);
    if ($content === false) {
        error_log("Failed to fetch content using file_get_contents. URL: " . $rawUrl);
        return false;
    }
    
    // Check if we got a valid response
    $response_code = 0;
    if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches)) {
        $response_code = intval($matches[1]);
    }
    
    if ($response_code !== 200) {
        error_log("Failed to fetch content. HTTP code: " . $response_code . " URL: " . $rawUrl);
        return false;
    }
    
    return $content;
}

echo "Testing store content fetch...\n";

// Test store.json
$store_data = fetch_github_content('store.json');
if ($store_data === false) {
    echo "Failed to fetch store.json\n";
} else {
    echo "Successfully fetched store.json\n";
    $store = json_decode($store_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Error decoding store.json: " . json_last_error_msg() . "\n";
    } else {
        echo "Successfully decoded store.json\n";
    }
}

// Test news.md
$news_content = fetch_github_content('news.md');
if ($news_content === false) {
    echo "Failed to fetch news.md\n";
} else {
    echo "Successfully fetched news.md\n";
}

// Test featured.md
$featured_content = fetch_github_content('featured.md');
if ($featured_content === false) {
    echo "Failed to fetch featured.md\n";
} else {
    echo "Successfully fetched featured.md\n";
} 