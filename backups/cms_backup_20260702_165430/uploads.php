<?php
// Simple uploads handler to serve files with proper MIME types
$requestUri = $_SERVER['REQUEST_URI'];
$filePath = __DIR__ . $requestUri;

// Security check - ensure the file is within the uploads directory
$uploadsDir = __DIR__ . '/uploads';
if (strpos(realpath($filePath), realpath($uploadsDir)) !== 0) {
    http_response_code(403);
    exit('Access denied');
}

if (file_exists($filePath) && is_file($filePath)) {
    // Get file info for proper MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    
    // Set proper headers
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
    
    // Output the file
    readfile($filePath);
    exit;
} else {
    http_response_code(404);
    exit('File not found');
}
?> 