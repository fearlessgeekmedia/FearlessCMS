<?php
// File manager functions - registration moved to includes/plugins.php

function fcms_render_file_manager() {
    error_log("DEBUG: fcms_render_file_manager() called");
    // Use root uploads directory to match theme system expectations
    $uploadsDir = dirname(__DIR__) . '/../uploads';
    $webUploadsDir = '/uploads';
    $allowedExts = ['jpg','jpeg','png','gif','webp','pdf','zip','svg','txt','md'];
    $maxFileSize = 10 * 1024 * 1024; // 10MB

    $error = '';
    $success = '';

    // Handle file upload (single or multiple files)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_file') {
        // Validate CSRF token
        if (!function_exists('validate_csrf_token') || !validate_csrf_token()) {
            $error = 'Invalid security token. Please refresh the page and try again.';
        } elseif (!empty($_FILES['files']['name'][0])) {
            $uploadedFiles = $_FILES['files'];
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            
            // Process each uploaded file
            for ($i = 0; $i < count($uploadedFiles['name']); $i++) {
                $file = [
                    'name' => $uploadedFiles['name'][$i],
                    'type' => $uploadedFiles['type'][$i],
                    'tmp_name' => $uploadedFiles['tmp_name'][$i],
                    'error' => $uploadedFiles['error'][$i],
                    'size' => $uploadedFiles['size'][$i]
                ];
                
                // Comprehensive file validation
                $originalName = $file['name'];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $mimeType = $file['type'];
                $tmpName = $file['tmp_name'];
                
                // Check for upload errors
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = $originalName . ': Upload error occurred.';
                    $errorCount++;
                    continue;
                }
                
                // Validate file extension
                if (!in_array($ext, $allowedExts)) {
                    $errors[] = $originalName . ': File type not allowed. Allowed types: ' . implode(', ', $allowedExts);
                    $errorCount++;
                    continue;
                }
                
                // Validate file size
                if ($file['size'] > $maxFileSize) {
                    $errors[] = $originalName . ': File is too large. Maximum size: ' . round($maxFileSize/1024/1024) . 'MB';
                    $errorCount++;
                    continue;
                }
                
                // Validate MIME type for additional security
                if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'text/plain', 'application/pdf'])) {
                    $errors[] = $originalName . ': Invalid file type detected.';
                    $errorCount++;
                    continue;
                }
                
                // Check for executable content in filename
                if (preg_match('/\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$/i', $originalName)) {
                    $errors[] = $originalName . ': Executable files are not allowed.';
                    $errorCount++;
                    continue;
                }
                
                // Sanitize filename - remove dangerous characters
                $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $safeName = preg_replace('/_{2,}/', '_', $safeName); // Remove multiple underscores
                
                // Ensure filename doesn't start with dot
                if (strpos($safeName, '.') === 0) {
                    $safeName = 'file' . $safeName;
                }
                
                // Add timestamp to prevent conflicts
                $pathInfo = pathinfo($safeName);
                $finalName = $pathInfo['filename'] . '_' . time() . '_' . $i . '.' . $pathInfo['extension'];
                
                $target = $uploadsDir . '/' . $finalName;
                
                // Additional security: validate the actual file content
                if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $detectedMime = finfo_file($finfo, $tmpName);
                    finfo_close($finfo);
                    
                    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'text/plain', 'application/pdf'];
                    if (!in_array($detectedMime, $allowedMimes)) {
                        $errors[] = $originalName . ': File content does not match allowed types.';
                        $errorCount++;
                        continue;
                    }
                }
                
                // Attempt to upload the file
                if (move_uploaded_file($tmpName, $target)) {
                    // Set secure file permissions
                    chmod($target, 0644);
                    $successCount++;
                } else {
                    $errors[] = $originalName . ': Failed to upload file.';
                    $errorCount++;
                }
            }
            
            // Set success/error messages
            if ($successCount > 0) {
                if ($successCount === 1) {
                    $success = '1 file uploaded successfully.';
                } else {
                    $success = $successCount . ' files uploaded successfully.';
                }
            }
            
            if ($errorCount > 0) {
                if (empty($success)) {
                    $error = 'Upload failed. ' . implode(' ', $errors);
                } else {
                    $error = 'Some files failed to upload: ' . implode(' ', $errors);
                }
            }
        } else {
            $error = 'No files selected.';
        }
    }

    // Handle file deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_file') {
        // Validate CSRF token
        if (!function_exists('validate_csrf_token') || !validate_csrf_token()) {
            $error = 'Invalid security token. Please refresh the page and try again.';
        } else {
            $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
            $filepath = realpath($uploadsDir . '/' . $filename);
            if ($filename && $filepath && strpos($filepath, realpath($uploadsDir)) === 0 && is_file($filepath)) {
                if (unlink($filepath)) {
                    $success = 'File deleted.';
                } else {
                    $error = 'Failed to delete file.';
                }
            } else {
                $error = 'Invalid file.';
            }
        }
    }

    // Handle bulk file deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_delete_files') {
        // Validate CSRF token
        if (!function_exists('validate_csrf_token') || !validate_csrf_token()) {
            $error = 'Invalid security token. Please refresh the page and try again.';
        } else {
            $selectedFiles = isset($_POST['selected_files']) ? $_POST['selected_files'] : '';
            
            if (empty($selectedFiles)) {
                $error = 'No files selected for deletion';
            } else {
                $fileNames = explode(',', $selectedFiles);
                $deletedCount = 0;
                $errorCount = 0;
                $errors = [];
                
                foreach ($fileNames as $fileName) {
                    $fileName = trim($fileName);
                    if (empty($fileName)) continue;
                    
                    $filepath = realpath($uploadsDir . '/' . $fileName);
                    if ($fileName && $filepath && strpos($filepath, realpath($uploadsDir)) === 0 && is_file($filepath)) {
                        if (unlink($filepath)) {
                            $deletedCount++;
                            error_log("SECURITY: File '{$fileName}' deleted by '" . (isset($_SESSION['username']) ? $_SESSION['username'] : 'unknown') . "' from IP: " . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown'));
                        } else {
                            $errors[] = "Failed to delete: $fileName";
                            $errorCount++;
                        }
                    } else {
                        $errors[] = "Invalid file: $fileName";
                        $errorCount++;
                    }
                }
                
                // Set success/error messages
                if ($deletedCount > 0) {
                    if ($errorCount > 0) {
                        $success = "Successfully deleted $deletedCount file(s). $errorCount file(s) failed to delete.";
                        if (!empty($errors)) {
                            $error = "Errors: " . implode('; ', $errors);
                        }
                    } else {
                        $success = "Successfully deleted $deletedCount file(s).";
                    }
                } else {
                    $error = "No files were deleted. Errors: " . implode('; ', $errors);
                }
            }
        }
    }

    // Handle file renaming
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rename_file') {
        // Validate CSRF token
        if (!function_exists('validate_csrf_token') || !validate_csrf_token()) {
            $error = 'Invalid security token. Please refresh the page and try again.';
        } else {
            $oldFilename = isset($_POST['old_filename']) ? $_POST['old_filename'] : '';
            $newFilename = isset($_POST['new_filename']) ? $_POST['new_filename'] : '';
            
            if (empty($oldFilename) || empty($newFilename)) {
                $error = 'Both old and new filenames are required.';
            } else {
                // Sanitize new filename
                $newFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $newFilename);
                $newFilename = preg_replace('/_{2,}/', '_', $newFilename);
                
                if (strpos($newFilename, '.') === 0) {
                    $newFilename = 'file' . $newFilename;
                }
                
                $oldPath = $uploadsDir . '/' . $oldFilename;
                $newPath = $uploadsDir . '/' . $newFilename;
                
                // Check if old file exists and is within uploads directory
                if (file_exists($oldPath) && strpos(realpath($oldPath), realpath($uploadsDir)) === 0) {
                    // Check if new filename already exists
                    if (file_exists($newPath)) {
                        $error = 'A file with that name already exists.';
                    } else {
                        if (rename($oldPath, $newPath)) {
                            $success = 'File renamed successfully.';
                            
                            // Update theme options if this was the hero banner
                            if ($oldFilename === 'herobanner_1755845067.png') {
                                $themeOptionsFile = dirname(__DIR__) . '/config/theme_options.json';
                                if (file_exists($themeOptionsFile)) {
                                    $themeOptions = json_decode(file_get_contents($themeOptionsFile), true) ?: [];
                                    $themeOptions['herobanner'] = 'uploads/' . $newFilename;
                                    file_put_contents($themeOptionsFile, json_encode($themeOptions, JSON_PRETTY_PRINT));
                                }
                            }
                        } else {
                            $error = 'Failed to rename file.';
                        }
                    }
                } else {
                    $error = 'Invalid file.';
                }
            }
        }
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

    // Now include the enhanced template with all the variables defined
    error_log("DEBUG: About to include template. Files count: " . count($files));
    error_log("DEBUG: Template path: " . dirname(__DIR__) . '/templates/file_manager.php');
    
    $templatePath = dirname(__DIR__) . '/templates/file_manager.php';
    error_log("DEBUG: Final template path: " . $templatePath);
    error_log("DEBUG: Template file exists: " . (file_exists($templatePath) ? 'YES' : 'NO'));
    
    if (file_exists($templatePath)) {
        include $templatePath;
    } else {
        error_log("ERROR: Template file not found at: " . $templatePath);
        echo "<div class='alert alert-danger'>Template file not found</div>";
    }
    
    // Don't return anything - let the admin section system handle the output
}
?> 