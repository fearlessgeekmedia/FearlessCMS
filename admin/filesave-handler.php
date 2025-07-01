<?php
// filesave-handler.php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_file') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to edit files';
    } else {
        $fileName = $_POST['file_name'] ?? '';
        $content = $_POST['content'] ?? '';
        $pageTitle = $_POST['page_title'] ?? '';
        $parentPage = $_POST['parent_page'] ?? '';
        $editorMode = $_POST['editor_mode'] ?? 'easy';
        $template = $_POST['template'] ?? 'page';

        // Validate filename: allow slashes for subfolders
        if (empty($fileName) || !preg_match('/^[a-zA-Z0-9_\/-]+\.md$/', $fileName)) {
            $error = 'Invalid filename';
        } else {
            $filePath = CONTENT_DIR . '/' . $fileName;

            // Ensure the directory exists
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Check if content already has JSON frontmatter
            $hasFrontmatter = preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $content, $matches);

            if ($hasFrontmatter) {
                // Update existing frontmatter
                $metadata = json_decode($matches[1], true) ?: [];
                $metadata['title'] = $pageTitle;
                $metadata['editor_mode'] = $editorMode;
                $metadata['template'] = $template;
                if (!empty($parentPage)) {
                    $metadata['parent'] = $parentPage;
                } elseif (isset($metadata['parent'])) {
                    unset($metadata['parent']); // Remove parent if empty
                }
                $newFrontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
                $content = preg_replace('/^<!--\s*json\s*.*?\s*-->/s', $newFrontmatter, $content);
            } else {
                // Add new frontmatter
                $metadata = [
                    'title' => $pageTitle,
                    'editor_mode' => $editorMode,
                    'template' => $template
                ];
                if (!empty($parentPage)) {
                    $metadata['parent'] = $parentPage;
                }
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
?>
