<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_page') {
    error_log("Received POST data: " . print_r($_POST, true));

    if (!isLoggedIn()) {
        $error = 'You must be logged in to create pages';
    } elseif (false) { // CSRF validation handled globally in admin/index.php
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $newPageFilename = sanitize_input($_POST['new_page_filename'] ?? '', 'path');
        $newPageContent = $_POST['new_page_content'] ?? '';
        $pageTitle = sanitize_input($_POST['page_title'] ?? '', 'string');
        $parentPage = sanitize_input($_POST['parent_page'] ?? '', 'path');

        error_log("Processing page creation:");
        error_log("Filename: " . $newPageFilename);
        error_log("Title: " . $pageTitle);
        error_log("Parent: " . $parentPage);
        error_log("Content length: " . strlen($newPageContent));

        // Remove .md if user included it
        $newPageFilename = preg_replace('/\.md$/i', '', $newPageFilename);

        // If parent is set, prepend it
        if (!empty($parentPage)) {
            $newPageFilename = $parentPage . '/' . $newPageFilename;
        }
        $newPageFilename .= '.md';

        // Validate filename (allow slashes for subfolders)
        if (!preg_match('/^[a-zA-Z0-9_\/-]+\.md$/', $newPageFilename)) {
            $error = 'Invalid filename. Use only letters, numbers, dashes, underscores, and slashes, and end with .md';
            error_log("Invalid filename: " . $newPageFilename);
        } elseif (strpos($newPageFilename, '../') !== false || strpos($newPageFilename, './') === 0) {
            $error = 'Invalid file path - path traversal detected';
            error_log("Path traversal attempt: " . $newPageFilename);
        } else {
            $filePath = CONTENT_DIR . '/' . $newPageFilename;
            if (file_exists($filePath)) {
                $error = 'A page with that filename already exists.';
                error_log("File already exists: " . $filePath);
            } else {
                // Make sure the directory exists
                $dir = dirname($filePath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                // Add JSON frontmatter with title and parent if provided
                $metadata = [
                    'title' => $pageTitle,
                    'template' => $_POST['template'] ?? 'page' // Use selected template or default to page
                ];
                if (!empty($parentPage)) {
                    $metadata['parent'] = $parentPage;
                }
                $frontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
                $newPageContent = $frontmatter . "\n\n" . $newPageContent;

                error_log("Attempting to save file: " . $filePath);
                error_log("Content to save: " . $newPageContent);

                if (file_put_contents($filePath, $newPageContent) !== false) {
                    error_log("File saved successfully");
                    // Remove .md extension for the redirect path
                    $redirectPath = str_replace('.md', '', $newPageFilename);
                    error_log("About to redirect to: ?action=edit_content&path=" . urlencode($redirectPath));
                    
                    // Try redirect first
                    if (!headers_sent()) {
                        header('Location: ?action=edit_content&path=' . urlencode($redirectPath));
                        error_log("Redirect header sent, exiting");
                        exit;
                    } else {
                        error_log("Headers already sent, cannot redirect");
                    }
                    
                    // If redirect fails, set session variable to indicate page was created
                    $_SESSION['just_created_page'] = $redirectPath;
                    $_SESSION['just_created_message'] = 'Page created successfully';
                    error_log("Fallback - setting session variable for just_created_page: " . $redirectPath);
                } else {
                    $error = 'Failed to create new page.';
                    error_log("Failed to save file");
                }
            }
        }
    }
}
?>
