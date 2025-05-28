<?php

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
                    if (in_array($ext, $allowed)) {
                        $filename = 'logo_' . time() . '.' . $ext;
                        $target = $uploadsDir . '/' . $filename;
                        if (move_uploaded_file($file['tmp_name'], $target)) {
                            $themeOptions['logo'] = 'uploads/' . $filename;
                        }
                    }
                }

                // Handle hero banner upload
                if (isset($_FILES['herobanner']) && $_FILES['herobanner']['error'] === UPLOAD_ERR_OK) {
                    // Remove old banner if exists
                    if (!empty($themeOptions['herobanner'])) {
                        $oldBannerPath = PROJECT_ROOT . '/' . $themeOptions['herobanner'];
                        if (file_exists($oldBannerPath)) {
                            unlink($oldBannerPath);
                        }
                    }

                    $file = $_FILES['herobanner'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (in_array($ext, $allowed)) {
                        $filename = 'herobanner_' . time() . '.' . $ext;
                        $target = $uploadsDir . '/' . $filename;
                        if (move_uploaded_file($file['tmp_name'], $target)) {
                            $themeOptions['herobanner'] = 'uploads/' . $filename;
                        }
                    }
                }

                // Save updated options
                if (file_put_contents($themeOptionsFile, json_encode($themeOptions, JSON_PRETTY_PRINT))) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true]);
                    exit;
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Failed to save theme options']);
                    exit;
                }
                break;
        }
    }
} 