<?php
error_log("DEBUG: filedel-handler.php - REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . ", POST data: " . print_r($_POST, true));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['delete_page', 'delete_content'])) {
    error_log("DEBUG: filedel-handler.php - Delete handler triggered!");
    if (!isLoggedIn()) {
        $error = 'You must be logged in to delete pages';

    } elseif (!validate_csrf_token()) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } elseif (!check_operation_rate_limit('delete_content', $_SESSION['username'])) {
        $error = 'Too many deletion attempts. Please wait before trying again.';
    } else {
        // Handle both old and new parameter names with sanitization
        $fileName = sanitize_input($_POST['file_name'] ?? $_POST['path'] ?? '', 'path');

        // If path is provided without .md extension, add it
        if (!empty($fileName) && !str_ends_with($fileName, '.md')) {
            $fileName .= '.md';
        }

        // Validate filename: allow slashes for subfolders
        if (empty($fileName) || !preg_match('/^[a-zA-Z0-9_\/-]+\.md$/', $fileName)) {
            $error = 'Invalid filename';
        } elseif (strpos($fileName, '../') !== false || strpos($fileName, './') === 0) {
            $error = 'Invalid file path - path traversal detected';
        } else {
            $filePath = CONTENT_DIR . '/' . $fileName;

            // Ensure we're only deleting files within the content directory
            $realFilePath = realpath($filePath);
            $realContentDir = realpath(CONTENT_DIR);

            if ($realFilePath === false || strpos($realFilePath, $realContentDir) !== 0) {
                $error = 'Invalid file path';
            } else if (!file_exists($filePath)) {
                $error = 'File not found';
            } else {
                // Delete the file
                if (unlink($filePath)) {
                    $success = 'Page deleted successfully';


                    // Clear page cache after content deletion
                    $cacheDir = dirname(__DIR__) . '/cache';
                    if (is_dir($cacheDir)) {
                        foreach (glob($cacheDir . '/*.html') as $cacheFile) {
                            @unlink($cacheFile);
                        }
                    }

                    // Log security event
                    error_log("SECURITY: Content file '{$fileName}' deleted by '{$_SESSION['username']}' from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                    

                } else {
                    $error = 'Failed to delete file';
                }
            }
        }
    }
}
?>
