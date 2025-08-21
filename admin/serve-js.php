<?php
$file = __DIR__ . '/templates/js/Sortable.min.js';

if (file_exists($file)) {
    header('Content-Type: application/javascript');
    readfile($file);
} else {
    http_response_code(404);
    echo 'File not found.';
}
