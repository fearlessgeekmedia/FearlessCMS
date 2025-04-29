<?php

// Handle file saving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_file') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to edit files';
    } else {
        $fileName = $_POST['file_name'] ?? '';
        $content = $_POST['content'] ?? '';
        $pageTitle = $_POST['page_title'] ?? '';
        
        // Validate filename
        if (empty($fileName) || !preg_match('/^[a-zA-Z0-9_-]+\.md$/', $fileName)) {
            $error = 'Invalid filename';
        } else {
            $filePath = CONTENT_DIR . '/' . $fileName;
            
            // Ensure we're only editing files within the content directory
            $realFilePath = realpath($filePath);
            $realContentDir = realpath(CONTENT_DIR);
            
            if ($realFilePath === false || strpos($realFilePath, $realContentDir) !== 0) {
                $error = 'Invalid file path';
            } else {
                // Check if content already has JSON frontmatter
                $hasFrontmatter = preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $content, $matches);
                
                if ($hasFrontmatter) {
                    // Update existing frontmatter
                    $metadata = json_decode($matches[1], true) ?: [];
                    $metadata['title'] = $pageTitle;
                    $newFrontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
                    $content = preg_replace('/^<!--\s*json\s*(.*?)\s*-->/s', $newFrontmatter, $content);
                } else if (!empty($pageTitle)) {
                    // Add new frontmatter
                    $metadata = ['title' => $pageTitle];
                    $newFrontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
                    $content = $newFrontmatter . "\n\n" . $content;
                }
                
                // Save the file
                if (file_put_contents($filePath, $content) !== false) {
                    $success = 'File saved successfully';
                } else {
                    $error = 'Failed to save file';
                }
            }
        }
    }
}
?>
