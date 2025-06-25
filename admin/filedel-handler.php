<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['delete_page', 'delete_content'])) {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to delete pages';
    } else {
        // Handle both old and new parameter names
        $fileName = $_POST['file_name'] ?? $_POST['path'] ?? '';
        
        // If path is provided without .md extension, add it
        if (!empty($fileName) && !str_ends_with($fileName, '.md')) {
            $fileName .= '.md';
        }
        
        // Validate filename: allow slashes for subfolders
        if (empty($fileName) || !preg_match('/^[a-zA-Z0-9_\/-]+\.md$/', $fileName)) {
            $error = 'Invalid filename';
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
                } else {
                    $error = 'Failed to delete file';
                }
            }
        }
    }
}
?>
