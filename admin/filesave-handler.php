<?php
// filesave-handler.php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_file') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to edit files';
    } elseif (!validate_csrf_token()) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $fileName = sanitize_input($_POST['file_name'] ?? '', 'path');
        $content = $_POST['content'] ?? '';
        $pageTitle = sanitize_input($_POST['page_title'] ?? '', 'string');
        $parentPage = sanitize_input($_POST['parent_page'] ?? '', 'path');
        $editorMode = sanitize_input($_POST['editor_mode'] ?? 'easy', 'string');
        $template = sanitize_input($_POST['template'] ?? 'page-with-sidebar', 'string');

        // Validate filename: allow slashes for subfolders
        if (empty($fileName) || !preg_match('/^[a-zA-Z0-9_\/-]+\.md$/', $fileName)) {
            $error = 'Invalid filename';
        } elseif (strpos($fileName, '../') !== false || strpos($fileName, './') === 0) {
            $error = 'Invalid file path - path traversal detected';
        } else {
            // DEMO MODE HANDLING
            require_once dirname(__DIR__) . '/includes/DemoModeManager.php';
            $demoManager = new DemoModeManager();

            if ($demoManager->isDemoSession()) {
                $filePath = $demoManager->getDemoContentDir() . '/' . $fileName;
            } else {
                $filePath = CONTENT_DIR . '/' . $fileName;
            }

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
                // Clear page cache after content update
                $cacheDir = dirname(__DIR__) . '/cache';
                if (is_dir($cacheDir)) {
                    foreach (glob($cacheDir . '/*.html') as $cacheFile) {
                        @unlink($cacheFile);
                    }
                }
            } else {
                $error = 'Failed to save file';
            }
        }
    }
}
?>