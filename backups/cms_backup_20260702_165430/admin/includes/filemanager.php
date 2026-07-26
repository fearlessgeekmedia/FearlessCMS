<?php
// File manager functions - registration moved to includes/plugins.php

function fcms_render_file_manager() {
    error_log("DEBUG: fcms_render_file_manager() called");
    
    // Use root uploads directory to match theme system expectations
    $uploadsDir = dirname(__DIR__) . '/../uploads';
    $webUploadsDir = '/uploads';
    
    // Ensure uploads directory exists
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    
    // List files
    $files = [];
    if (is_dir($uploadsDir)) {
        foreach (scandir($uploadsDir) as $f) {
            if ($f === '.' || $f === '..') continue;
            $full = $uploadsDir . '/' . $f;
            if (is_file($full)) {
                $files[] = [
                    'name' => $f,
                    'size' => filesize($full),
                    'type' => mime_content_type($full),
                    'url'  => $webUploadsDir . '/' . rawurlencode($f)
                ];
            }
        }
    }
    
    // Start output buffering to capture template output
    ob_start();
    
    // Include the template with variables defined
    $templatePath = dirname(__DIR__) . '/templates/file_manager.php';
    if (file_exists($templatePath)) {
        include $templatePath;
    } else {
        echo '<div class="alert alert-danger">File manager template not found</div>';
    }
    
    // Get the captured output and return it
    $output = ob_get_clean();
    
    return $output;
}

// Separate function to handle file operations (called only when needed)
function fcms_handle_file_operations() {
    // This function will be called separately for POST operations
    // to avoid executing on every page load
    return true;
}
?> 