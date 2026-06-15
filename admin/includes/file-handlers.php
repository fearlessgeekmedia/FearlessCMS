<?php
/**
 * File Management Handlers for FearlessCMS Admin
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($postAction) && in_array($postAction, ['upload_file', 'delete_file', 'bulk_delete_files', 'rename_file'])) {
    $action = 'files';

    if (!isLoggedIn()) {
        $error = 'You must be logged in to perform this action';
    } else {
        $uploadsDir = dirname(dirname(__DIR__)) . '/uploads';
        $allowedExts = ['jpg','jpeg','png','gif','webp','pdf','zip','svg','txt','md'];
        $maxFileSize = 10 * 1024 * 1024; // 10MB
        
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        
        switch ($postAction) {
            case 'upload_file':
                if (!empty($_FILES['files']['name'][0])) {
                    $uploadedFiles = $_FILES['files'];
                    $successCount = 0;
                    $errors = [];
                    
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
                        
                        if ($file['error'] !== UPLOAD_ERR_OK) {
                            $errors[] = $originalName . ': Upload error occurred.';
                            continue;
                        }
                        if (!in_array($ext, $allowedExts)) {
                            $errors[] = $originalName . ': File type not allowed.';
                            continue;
                        }
                        if ($file['size'] > $maxFileSize) {
                            $errors[] = $originalName . ': File is too large.';
                            continue;
                        }
                        
                        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                        $safeName = preg_replace('/_{2,}/', '_', $safeName);
                        if (strpos($safeName, '.') === 0) $safeName = 'file' . $safeName;
                        
                        $pathInfo = pathinfo($safeName);
                        $finalName = $pathInfo['filename'] . '_' . time() . '_' . $i . '.' . $pathInfo['extension'];
                        $target = $uploadsDir . '/' . $finalName;
                        
                        if (move_uploaded_file($file['tmp_name'], $target)) {
                            $successCount++;
                        } else {
                            $errors[] = $originalName . ': Failed to save file.';
                        }
                    }
                    
                    if ($successCount > 0) {
                        $success = "Successfully uploaded $successCount file(s)." . (!empty($errors) ? " Some errors occurred." : "");
                        if (!empty($errors)) $error = implode('; ', $errors);
                    } else {
                        $error = "No files were uploaded. Errors: " . implode('; ', $errors);
                    }
                }
                break;
                
            case 'delete_file':
                $filename = $_POST['filename'] ?? '';
                if (!empty($filename)) {
                    $filePath = $uploadsDir . '/' . $filename;
                    if (file_exists($filePath) && strpos(realpath($filePath), realpath($uploadsDir)) === 0) {
                        if (unlink($filePath)) {
                            $success = 'File deleted successfully.';
                        } else {
                            $error = 'Failed to delete file.';
                        }
                    } else {
                        $error = 'Invalid file.';
                    }
                }
                break;
                
            case 'bulk_delete_files':
                $filenames = $_POST['filenames'] ?? [];
                if (!empty($filenames)) {
                    $deletedCount = 0;
                    $errors = [];
                    foreach ($filenames as $filename) {
                        $filePath = $uploadsDir . '/' . $filename;
                        if (file_exists($filePath) && strpos(realpath($filePath), realpath($uploadsDir)) === 0) {
                            if (unlink($filePath)) $deletedCount++;
                            else $errors[] = $filename . ': Failed to delete.';
                        } else $errors[] = $filename . ': Invalid file.';
                    }
                    if ($deletedCount > 0) {
                        $success = "Successfully deleted $deletedCount file(s).";
                        if (!empty($errors)) $error = implode('; ', $errors);
                    } else $error = "No files were deleted. Errors: " . implode('; ', $errors);
                }
                break;
                
            case 'rename_file':
                $oldFilename = $_POST['old_filename'] ?? '';
                $newFilename = $_POST['new_filename'] ?? '';
                if (!empty($oldFilename) && !empty($newFilename)) {
                    $newFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $newFilename);
                    if (strpos($newFilename, '.') === 0) $newFilename = 'file' . $newFilename;
                    $oldPath = $uploadsDir . '/' . $oldFilename;
                    $newPath = $uploadsDir . '/' . $newFilename;
                    if (file_exists($oldPath) && strpos(realpath($oldPath), realpath($uploadsDir)) === 0) {
                        if (file_exists($newPath)) $error = 'A file with that name already exists.';
                        elseif (rename($oldPath, $newPath)) $success = 'File renamed successfully.';
                        else $error = 'Failed to rename file.';
                    } else $error = 'Invalid file.';
                }
                break;
        }
    }
}
