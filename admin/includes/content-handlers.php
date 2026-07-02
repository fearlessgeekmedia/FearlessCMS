<?php
/**
 * Content Management Handlers for FearlessCMS Admin
 * Handles page/content deletion and bulk operations
 */

// Handle content deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['delete_page', 'delete_content'])) {
    error_log("DEBUG: Content deletion handler triggered!");
    
    // Clear any existing output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'You must be logged in to delete pages';
    } elseif (!validate_csrf_token()) {
        $_SESSION['error'] = 'Invalid security token. Please refresh the page and try again.';
    } elseif (!check_operation_rate_limit('delete_content', $_SESSION['username'], 10, 60)) {
        $_SESSION['error'] = 'Too many deletion attempts. Please wait before trying again.';
    } else {
        // Handle both old and new parameter names with sanitization
        $fileName = sanitize_input($_POST['file_name'] ?? $_POST['path'] ?? '', 'path');

        // Determine content directory based on demo mode
        require_once PROJECT_ROOT . '/includes/DemoModeManager.php';
        $demoManager = new DemoModeManager();
        $deleteContentDir = ($demoManager->isDemoSession() || $demoManager->isDemoUserSession()) ? $demoManager->getDemoContentDir() : CONTENT_DIR;

        // If path is provided without extension, try both .md and .html
        $fileNameBase = $fileName;
        $hasExtension = str_ends_with($fileName, '.md') || str_ends_with($fileName, '.html');
        $filePath = null;
        $fileNotFound = false;

        if (!$hasExtension) {
            // Try both extensions to find the existing file
            $mdPath = $deleteContentDir . '/' . $fileNameBase . '.md';
            $htmlPath = $deleteContentDir . '/' . $fileNameBase . '.html';
            if (file_exists($mdPath)) {
                $fileName = $fileNameBase . '.md';
                $filePath = $mdPath;
            } elseif (file_exists($htmlPath)) {
                $fileName = $fileNameBase . '.html';
                $filePath = $htmlPath;
            } else {
                $fileNotFound = true;
            }
        }

        // Validate filename: allow slashes for subfolders (both .md and .html)
        if ($fileNotFound || empty($fileName) || !preg_match('/^[a-zA-Z0-9_\/-]+\.(md|html)$/', $fileName)) {
            $_SESSION['error'] = $fileNotFound ? 'File not found (.md or .html)' : 'Invalid filename';
        } elseif (strpos($fileName, '../') !== false || strpos($fileName, './') === 0) {
            $_SESSION['error'] = 'Invalid file path - path traversal detected';
        } else {
            // If extension was provided, construct the full path
            if ($filePath === null) {
                $filePath = $deleteContentDir . '/' . $fileName;
            }

            // Ensure we're only deleting files within the content directory
            $realFilePath = realpath($filePath);
            $realContentDir = realpath($deleteContentDir);

            if ($realFilePath === false || strpos($realFilePath, $realContentDir) !== 0) {
                $_SESSION['error'] = 'Invalid file path';
            } else if (!file_exists($filePath)) {
                $_SESSION['error'] = 'File not found';
            } else {
                // Delete the file
                if (unlink($filePath)) {
                    $_SESSION['success'] = 'Page deleted successfully';

                    // Clear page cache after content deletion
                    $cacheDir = dirname(dirname(__DIR__)) . '/cache';
                    if (is_dir($cacheDir)) {
                        foreach (glob($cacheDir . '/*.html') as $cacheFile) {
                            @unlink($cacheFile);
                        }
                    }

                    // Log security event
                    error_log("SECURITY: Content file '{$fileName}' deleted by '{$_SESSION['username']}' from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                } else {
                    $_SESSION['error'] = 'Failed to delete file';
                }
            }
        }
    }
    
    // Redirect back to the content management page
    $redirectUrl = $_SERVER['HTTP_REFERER'] ?? 'index.php?action=manage_content';
    
    if (headers_sent($file, $line)) {
        echo '<script>window.location.href = "' . htmlspecialchars($redirectUrl) . '";</script>';
        exit;
    }
    
    header('Location: ' . $redirectUrl);
    exit;
}

// Handle bulk content deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_delete_content') {
    // Clear any existing output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'You must be logged in to delete pages';
    } elseif (!validate_csrf_token()) {
        $_SESSION['error'] = 'Invalid security token. Please refresh the page and try again.';
    } elseif (!check_operation_rate_limit('delete_content', $_SESSION['username'], 20, 60)) {
        $_SESSION['error'] = 'Too many deletion attempts. Please wait before trying again.';
    } else {
        $selectedItems = $_POST['selected_items'] ?? '';
        
        if (empty($selectedItems)) {
            $_SESSION['error'] = 'No items selected for deletion';
        } else {
            $itemPaths = explode(',', $selectedItems);
            $deletedCount = 0;
            $errorCount = 0;
            $errors = [];
            
            foreach ($itemPaths as $itemPath) {
                $itemPath = trim($itemPath);
                if (empty($itemPath)) continue;
                
                require_once PROJECT_ROOT . '/includes/DemoModeManager.php';
                $demoManager = new DemoModeManager();
                $bulkDeleteContentDir = ($demoManager->isDemoSession() || $demoManager->isDemoUserSession()) ? $demoManager->getDemoContentDir() : CONTENT_DIR;

                // If path is provided without extension, try both .md and .html
                $itemPathBase = $itemPath;
                $hasItemExtension = str_ends_with($itemPath, '.md') || str_ends_with($itemPath, '.html');
                $itemFilePath = null;

                if (!$hasItemExtension) {
                    // Try both extensions to find the existing file
                    $mdPath = $bulkDeleteContentDir . '/' . $itemPathBase . '.md';
                    $htmlPath = $bulkDeleteContentDir . '/' . $itemPathBase . '.html';
                    if (file_exists($mdPath)) {
                        $itemPath = $itemPathBase . '.md';
                        $itemFilePath = $mdPath;
                    } elseif (file_exists($htmlPath)) {
                        $itemPath = $itemPathBase . '.html';
                        $itemFilePath = $htmlPath;
                    }
                }

                // Validate filename (both .md and .html) - only if we have a valid extension
                if (!$hasItemExtension && $itemFilePath === null) {
                    $errors[] = "File not found: $itemPathBase (.md or .html)";
                    $errorCount++;
                    continue;
                }

                if (empty($itemPath) || !preg_match('/^[a-zA-Z0-9_\/-]+\.(md|html)$/', $itemPath)) {
                    $errors[] = "Invalid filename: $itemPathBase";
                    $errorCount++;
                    continue;
                }

                if (strpos($itemPath, '../') !== false || strpos($itemPath, './') === 0) {
                    $errors[] = "Invalid file path: $itemPath";
                    $errorCount++;
                    continue;
                }

                // If extension was provided, construct the full path
                if ($itemFilePath === null) {
                    $itemFilePath = $bulkDeleteContentDir . '/' . $itemPath;
                }

                $realFilePath = realpath($itemFilePath);
                $realContentDir = realpath($bulkDeleteContentDir);

                if ($realFilePath === false || strpos($realFilePath, $realContentDir) !== 0) {
                    $errors[] = "Invalid file path: $itemPath";
                    $errorCount++;
                    continue;
                }

                if (!file_exists($itemFilePath)) {
                    $errors[] = "File not found: $itemPath";
                    $errorCount++;
                    continue;
                }

                if (unlink($itemFilePath)) {
                    $deletedCount++;
                    error_log("SECURITY: Content file '{$itemPath}' deleted by '{$_SESSION['username']}'");
                } else {
                    $errors[] = "Failed to delete: $itemPath";
                    $errorCount++;
                }
            }
            
            // Clear page cache
            $cacheDir = dirname(dirname(__DIR__)) . '/cache';
            if (is_dir($cacheDir)) {
                foreach (glob($cacheDir . '/*.html') as $cacheFile) {
                    @unlink($cacheFile);
                }
            }
            
            if ($deletedCount > 0) {
                $_SESSION['success'] = "Successfully deleted $deletedCount item(s)." . ($errorCount > 0 ? " $errorCount failed." : "");
                if (!empty($errors)) $_SESSION['error'] = implode('; ', $errors);
            } else {
                $_SESSION['error'] = "No items were deleted. Errors: " . implode('; ', $errors);
            }
        }
    }
    
    $redirectUrl = $_SERVER['HTTP_REFERER'] ?? 'index.php?action=manage_content';
    if (headers_sent()) {
        echo '<script>window.location.href = "' . htmlspecialchars($redirectUrl) . '";</script>';
        exit;
    }
    header('Location: ' . $redirectUrl);
    exit;
}