<?php
// File manager functions - registration moved to includes/plugins.php

function fcms_render_file_manager() {
    error_log("DEBUG: fcms_render_file_manager() called");
    
    // Start output buffering to capture template output
    ob_start();
    
    // Call the original function logic
    fcms_render_file_manager_internal();
    
    // Get the captured output and return it
    $output = ob_get_clean();
    
    return $output;
}

function fcms_render_file_manager_internal() {
    // Use root uploads directory to match theme system expectations
    $uploadsDir = dirname(__DIR__) . '/../uploads';
    $webUploadsDir = '/uploads';
    $allowedExts = ['jpg','jpeg','png','gif','webp','pdf','zip','svg','txt','md'];
    $maxFileSize = 10 * 1024 * 1024; // 10MB
    
    // Ensure uploads directory exists
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    
    $error = '';
    $success = '';
    
    // Handle file upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_file') {
        if (!empty($_FILES['files']['name'][0])) {
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
                
                $originalName = $file['name'];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
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
                
                // Sanitize filename
                $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $safeName = preg_replace('/_{2,}/', '_', $safeName);
                
                if (strpos($safeName, '.') === 0) {
                    $safeName = 'file' . $safeName;
                }
                
                // Add timestamp to prevent conflicts
                $pathInfo = pathinfo($safeName);
                $finalName = $pathInfo['filename'] . '_' . time() . '_' . $i . '.' . $pathInfo['extension'];
                
                $target = $uploadsDir . '/' . $finalName;
                
                if (move_uploaded_file($tmpName, $target)) {
                    $successCount++;
                } else {
                    $errors[] = $originalName . ': Failed to save file.';
                    $errorCount++;
                }
            }
            
            if ($successCount > 0) {
                if (!empty($errors)) {
                    $error = "Errors: " . implode('; ', $errors);
                }
            } else {
                $success = "Successfully uploaded $successCount file(s).";
            }
        } else {
            $error = "No files were uploaded. Errors: " . implode('; ', $errors);
        }
    }
    
    // Handle file deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_file') {
        // Validate CSRF token
        if (!function_exists('validate_csrf_token') || !validate_csrf_token()) {
            $error = 'Invalid security token. Please refresh the page and try again.';
        } else {
            $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
            
            if (empty($filename)) {
                $error = 'Filename is required.';
            } else {
                $filePath = $uploadsDir . '/' . $filename;
                
                // Check if file exists and is within uploads directory
                if (file_exists($filePath) && strpos(realpath($filePath), realpath($uploadsDir)) === 0) {
                    if (unlink($filePath)) {
                        $success = 'File deleted successfully.';
                        
                        // Update theme options if this was the hero banner
                        if ($filename === 'herobanner_1755845067.png') {
                            $themeOptionsFile = dirname(__DIR__) . '/config/theme_options.json';
                            if (file_exists($themeOptionsFile)) {
                                $themeOptions = json_decode(file_get_contents($themeOptionsFile), true) ?: [];
                                unset($themeOptions['herobanner']);
                                file_put_contents($themeOptionsFile, json_encode($themeOptions, JSON_PRETTY_PRINT));
                            }
                        }
                    } else {
                        $error = 'Failed to delete file.';
                    }
                } else {
                    $error = 'Invalid file.';
                }
            }
        }
    }
    
    // Handle bulk file deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_delete_files') {
        // Validate CSRF token
        if (!function_exists('validate_csrf_token') || !validate_csrf_token()) {
            $error = 'Invalid security token. Please refresh the page and try again.';
        } else {
            $filenames = isset($_POST['filenames']) ? $_POST['filenames'] : [];
            
            if (empty($filenames) || !is_array($filenames)) {
                $error = 'No files selected for deletion.';
            } else {
                $deletedCount = 0;
                $errors = [];
                
                foreach ($filenames as $filename) {
                    $filePath = $uploadsDir . '/' . $filename;
                    
                    // Check if file exists and is within uploads directory
                    if (file_exists($filePath) && strpos(realpath($filePath), realpath($uploadsDir)) === 0) {
                        if (unlink($filePath)) {
                            $deletedCount++;
                        } else {
                            $errors[] = $filename . ': Failed to delete.';
                        }
                    } else {
                        $errors[] = $filename . ': Invalid file.';
                    }
                }
                
                if ($deletedCount > 0) {
                    if (!empty($errors)) {
                        $error = "Errors: " . implode('; ', $errors);
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