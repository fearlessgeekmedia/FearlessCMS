<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_page') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to create pages';
    } else {
        $newPageFilename = $_POST['new_page_filename'] ?? '';
        $newPageContent = $_POST['new_page_content'] ?? '';
        $pageTitle = $_POST['page_title'] ?? '';
        $parentPage = $_POST['parent_page'] ?? '';

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
        } else {
            $filePath = CONTENT_DIR . '/' . $newPageFilename;
            if (file_exists($filePath)) {
                $error = 'A page with that filename already exists.';
            } else {
                // Make sure the directory exists
                $dir = dirname($filePath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                // Add JSON frontmatter with title and parent if provided
                $metadata = [
                    'title' => $pageTitle,
                    'template' => 'page' // Default template
                ];
                if (!empty($parentPage)) {
                    $metadata['parent'] = $parentPage;
                }
                $frontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
                $newPageContent = $frontmatter . "\n\n" . $newPageContent;

                if (file_put_contents($filePath, $newPageContent) !== false) {
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
