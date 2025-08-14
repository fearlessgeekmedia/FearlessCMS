<?php

// Set upload limits
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to perform this action';
    } else {
        switch ($_POST['action']) {
            case 'activate_theme':
                if (!fcms_check_permission($_SESSION['username'], 'manage_themes')) {
                    $error = 'You do not have permission to manage themes';
                    break;
                }
                if (empty($_POST['theme'])) {
                    $error = 'Theme name is required';
                    break;
                }
                $theme = $_POST['theme'] ?? '';
                $configFile = CONFIG_DIR . '/config.json';
                $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
                $config['active_theme'] = $theme;
                if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT))) {
                    header('Location: /admin?action=manage_themes&success=Theme activated successfully');
                    exit;
                } else {
                    $error = 'Failed to activate theme';
                }
                break;

            case 'save_theme_options':
                if (!fcms_check_permission($_SESSION['username'], 'manage_themes')) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'You do not have permission to manage themes']);
                    exit;
                }

                // Only handle file uploads (logo, hero banner) - let the main index.php handle other options
                $themeOptionsFile = CONFIG_DIR . '/theme_options.json';
                $themeOptions = file_exists($themeOptionsFile) ? json_decode(file_get_contents($themeOptionsFile), true) : [];
                $uploadsDir = PROJECT_ROOT . '/uploads';

                // Handle logo removal
                if (isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1') {
                    if (!empty($themeOptions['logo'])) {
                        $oldLogoPath = PROJECT_ROOT . '/' . $themeOptions['logo'];
                        if (file_exists($oldLogoPath)) {
                            unlink($oldLogoPath);
                        }
                        $themeOptions['logo'] = '';
                    }
                }

                // Handle hero banner removal
                if (isset($_POST['remove_herobanner']) && $_POST['remove_herobanner'] === '1') {
                    if (!empty($themeOptions['herobanner'])) {
                        $oldBannerPath = PROJECT_ROOT . '/' . $themeOptions['herobanner'];
                        if (file_exists($oldBannerPath)) {
                            unlink($oldBannerPath);
                        }
                        $themeOptions['herobanner'] = '';
                    }
                }

                // Ensure uploads directory exists
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }

                // Handle logo upload
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    // Remove old logo if exists
                    if (!empty($themeOptions['logo'])) {
                        $oldLogoPath = PROJECT_ROOT . '/' . $themeOptions['logo'];
                        if (file_exists($oldLogoPath)) {
                            unlink($oldLogoPath);
                        }
                    }

                    $file = $_FILES['logo'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                    if (!in_array($ext, $allowed)) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Invalid file type for logo. Allowed: jpg, jpeg, png, gif, webp, svg']);
                        exit;
                    }
                    $filename = 'logo_' . time() . '.' . $ext;
                    $target = $uploadsDir . '/' . $filename;
                    if (!move_uploaded_file($file['tmp_name'], $target)) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Failed to move uploaded logo file. Check permissions.']);
                        exit;
                    }
                    $themeOptions['logo'] = 'uploads/' . $filename;
                }

                // Handle hero banner upload
                if (isset($_FILES['herobanner'])) {
                    error_log('Hero banner upload attempt.');
                    error_log('herobanner _FILES: ' . print_r($_FILES['herobanner'], true));

                    if ($_FILES['herobanner']['error'] !== UPLOAD_ERR_OK) {
                        $errorMessages = [
                            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
                            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
                            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
                        ];
                        $err = $_FILES['herobanner']['error'];
                        $msg = $errorMessages[$err] ?? 'Unknown upload error.';
                        error_log('Hero banner upload error: ' . $msg);

                        // Return detailed error to client
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'error' => $msg,
                            'details' => [
                                'error_code' => $err,
                                'file_size' => $_FILES['herobanner']['size'] ?? 'unknown',
                                'max_size' => ini_get('upload_max_filesize')
                            ]
                        ]);
                        exit;
                    }

                    // If we get here, the upload was successful
                    $file = $_FILES['herobanner'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (!in_array($ext, $allowed)) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Invalid file type for hero banner. Allowed: jpg, jpeg, png, gif, webp']);
                        exit;
                    }

                    // Remove old banner if exists
                    if (!empty($themeOptions['herobanner'])) {
                        $oldBannerPath = PROJECT_ROOT . '/' . $themeOptions['herobanner'];
                        if (file_exists($oldBannerPath)) {
                            unlink($oldBannerPath);
                        }
                    }

                    $filename = 'herobanner_' . time() . '.' . $ext;
                    $target = $uploadsDir . '/' . $filename;
                    if (!move_uploaded_file($file['tmp_name'], $target)) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Failed to move uploaded hero banner file. Check permissions.']);
                        exit;
                    }
                    $themeOptions['herobanner'] = 'uploads/' . $filename;
                }

                // Only save if we handled file uploads, otherwise let the main index.php handle it
                if (isset($_FILES['logo']) || isset($_FILES['herobanner']) || isset($_POST['remove_logo']) || isset($_POST['remove_herobanner'])) {
                    // Save updated options
                    if (file_put_contents($themeOptionsFile, json_encode($themeOptions, JSON_PRETTY_PRINT))) {
                        header('Location: /admin/?action=manage_themes&success=1');
                        exit;
                    } else {
                        header('Location: /admin/?action=manage_themes&error=Failed+to+save+theme+options');
                        exit;
                    }
                }
                // If no file uploads, don't handle this action - let the main index.php handle it
                break;
        }
    }
}
