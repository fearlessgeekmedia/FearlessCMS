<?php

// Handle new page creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_page') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to create pages';
    } else {
        $newPageFilename = $_POST['new_page_filename'] ?? '';
        $newPageContent = $_POST['new_page_content'] ?? '';
        $pageTitle = $_POST['page_title'] ?? '';
        
        if (!preg_match('/^[a-zA-Z0-9_-]+\.md$/', $newPageFilename)) {
            $error = 'Invalid filename. Use only letters, numbers, dashes, underscores, and end with .md';
        } else {
            $filePath = CONTENT_DIR . '/' . $newPageFilename;
            if (file_exists($filePath)) {
                $error = 'A page with that filename already exists.';
            } else {
                // Add JSON frontmatter with title if provided
                if (!empty($pageTitle)) {
                    $metadata = ['title' => $pageTitle];
                    $frontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
                    $newPageContent = $frontmatter . "\n\n" . $newPageContent;
                }
                
                if (file_put_contents($filePath, $newPageContent) !== false) {
                    // Redirect to editor for the new page
                    header('Location: ?edit=' . urlencode($newPageFilename));
                    exit;
                } else {
                    $error = 'Failed to create new page.';
                }
            }
        }
    }
}
?>
