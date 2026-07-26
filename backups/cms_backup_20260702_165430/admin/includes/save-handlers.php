<?php
/**
 * Content and Theme Options Handlers for FearlessCMS Admin
 */

// Handle POST requests for saving content
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($postAction) && $postAction === 'save_content') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to edit files';
    } elseif (!validate_csrf_token()) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $oldFileName = $_POST['path'] ?? '';
        $newSlug = $_POST['new_slug'] ?? '';
        $fileName = $newSlug;
        $content = $_POST['text_content'] ?? $_POST['editor_content'] ?? $_POST['content'] ?? '';
        $pageTitle = $_POST['title'] ?? '';
        $parentPage = $_POST['parent'] ?? '';
        $template = $_POST['template'] ?? 'page-with-sidebar';
        $editorMode = $_POST['editor_mode'] ?? 'easy';

        if (empty($fileName) || !preg_match('/^[a-zA-Z0-9_\-\/]+$/', $fileName)) {
            $error = 'Invalid filename';
        } else {
            require_once PROJECT_ROOT . '/includes/DemoModeManager.php';
            $demoManager = new DemoModeManager();
            $isDemoUser = $demoManager->isDemoUser();
            $contentDir = $isDemoUser ? $demoManager->getDemoContentDir() : CONTENT_DIR;
            
            if ($isDemoUser) {
                $result = $demoManager->createDemoContentFile($fileName, $pageTitle, $content, [
                    'editor_mode' => $editorMode,
                    'template' => $template
                ]);
                
                if ($result) {
                    $redirectPath = str_replace('.md', '', $fileName);
                    header('Location: ?action=edit_content&path=' . urlencode($redirectPath) . '&saved=1');
                    exit;
                } else {
                    $error = 'Failed to save demo content';
                }
            } else {
                if (isset($_SESSION['username']) && $_SESSION['username'] === 'demo') {
                    $error = 'Security error: Demo users cannot create real content.';
                } else {
                    $oldFilePath = $contentDir . '/' . $oldFileName . '.md';
                    $newFilePath = $contentDir . '/' . $fileName . '.md';
                    
                    if ($oldFileName !== $fileName && file_exists($oldFilePath)) {
                        if (file_exists($newFilePath)) {
                            $error = 'A page with that URL slug already exists.';
                        } else {
                            $dir = dirname($newFilePath);
                            if (!is_dir($dir)) mkdir($dir, 0755, true);
                            rename($oldFilePath, $newFilePath);
                        }
                    } else {
                        $newFilePath = $oldFilePath;
                    }
                    
                    $filePath = $newFilePath;
                    $dir = dirname($filePath);
                    if (!is_dir($dir)) mkdir($dir, 0755, true);

                    $hasFrontmatter = preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $content, $matches);
                    if ($hasFrontmatter) {
                        $metadata = json_decode($matches[1], true) ?: [];
                        $metadata['title'] = $pageTitle;
                        $metadata['editor_mode'] = $editorMode;
                        $metadata['template'] = $template;
                        if (!empty($parentPage)) $metadata['parent'] = $parentPage;
                        elseif (isset($metadata['parent'])) unset($metadata['parent']);
                        $newFrontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
                        $content = preg_replace('/^<!--\s*json\s*.*?\s*-->/s', $newFrontmatter, $content);
                    } else {
                        $metadata = ['title' => $pageTitle, 'editor_mode' => $editorMode, 'template' => $template];
                        if (!empty($parentPage)) $metadata['parent'] = $parentPage;
                        $newFrontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
                        $content = $newFrontmatter . "\n\n" . $content;
                    }

                    if (file_put_contents($filePath, $content) !== false) {
                        if (isset($cacheManager) && method_exists($cacheManager, 'clearCache')) {
                            $cacheManager->clearCache();
                        }
                        
                        $redirectPath = str_replace('.md', '', $fileName);
                        $redirectUrl = '?action=edit_content&path=' . urlencode($redirectPath) . '&saved=1&_t=' . time();
                        
                        if (!headers_sent()) {
                            header('Location: ' . $redirectUrl);
                            exit;
                        } else {
                            echo '<script>window.location.href = "' . addslashes($redirectUrl) . '";</script>';
                            exit;
                        }
                    } else {
                        $error = 'Failed to save file';
                    }
                }
            }
        }
    }
}

// Handle theme options form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($postAction) && $postAction === 'save_theme_options') {
    if (!isLoggedIn()) {
        $error = 'You must be logged in to edit theme options';
    } else {
        $themeOptionsFile = CONFIG_DIR . '/theme_options.json';
        $themeOptions = file_exists($themeOptionsFile) ? json_decode(file_get_contents($themeOptionsFile), true) : [];

        $themeOptions['author_name'] = $_POST['author_name'] ?? '';
        $themeOptions['author_avatar'] = $_POST['author_avatar'] ?? '';
        $themeOptions['avatar_size'] = $_POST['avatar_size'] ?? 'size-m';
        $themeOptions['avatar_first'] = isset($_POST['avatar_first']);
        $themeOptions['user'] = $_POST['user'] ?? 'user';
        $themeOptions['hostname'] = $_POST['hostname'] ?? 'localhost';
        $themeOptions['footer_html'] = $_POST['footer_html'] ?? '';
        $themeOptions['color_scheme'] = $_POST['color_scheme'] ?? 'blue';

        $socialLinks = [];
        if (isset($_POST['social_name']) && is_array($_POST['social_name'])) {
            for ($i = 0; $i < count($_POST['social_name']); $i++) {
                if (!empty($_POST['social_name'][$i]) && !empty($_POST['social_url'][$i])) {
                    $socialLinks[] = [
                        'name' => $_POST['social_name'][$i],
                        'url' => $_POST['social_url'][$i],
                        'icon' => $_POST['social_icon'][$i] ?? '',
                        'target' => $_POST['social_target'][$i] ?? '',
                        'aria' => $_POST['social_aria'][$i] ?? '',
                        'rel' => $_POST['social_rel'][$i] ?? ''
                    ];
                }
            }
        }
        $themeOptions['social_links'] = $socialLinks;

        if (file_put_contents($themeOptionsFile, json_encode($themeOptions, JSON_PRETTY_PRINT))) {
            $success = 'Theme options updated successfully!';
        } else {
            $error = 'Failed to update theme options';
        }
    }
}
