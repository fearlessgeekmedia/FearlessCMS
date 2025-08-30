<?php
/**
 * FearlessCMS Updater Handler
 * Clean version that only contains logic functions
 */

// Ensure we're in admin context
if (!defined('ADMIN_CONTEXT')) {
    require_once dirname(__DIR__) . '/includes/auth.php';
    require_once dirname(__DIR__) . '/includes/config.php';
    require_once dirname(__DIR__) . '/includes/session.php';
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Simple CSRF token generation and validation
function generate_updater_csrf_token() {
    if (!isset($_SESSION['updater_csrf_token'])) {
        $_SESSION['updater_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['updater_csrf_token'];
}

function validate_updater_csrf_token($token) {
    return isset($_SESSION['updater_csrf_token']) && 
           hash_equals($_SESSION['updater_csrf_token'], $token);
}

// Function to check available version from repository
function get_available_version($repo_url, $branch = 'main') {
    if (empty($repo_url)) {
        return ['error' => 'Repository not configured'];
    }
    
    try {
        // Use the known working git path on NixOS
        $git_cmd = '/run/current-system/sw/bin/git';
        
        // Verify git exists and is executable
        if (!file_exists($git_cmd) || !is_executable($git_cmd)) {
            error_log("Git not found at expected path: $git_cmd");
            return ['error' => 'Git command not found at expected location. Please ensure git is installed.'];
        }
        
        error_log("Using git command: $git_cmd");
        
        // Create a temporary directory for checking
        $temp_dir = sys_get_temp_dir() . '/fcms_version_check_' . uniqid();
        if (!mkdir($temp_dir, 0755, true)) {
            return ['error' => 'Failed to create temporary directory'];
        }
        
        // Clone the repository to check version using full path to git
        $cmd = sprintf('%s -c http.sslVerify=false clone --depth 1 --branch %s %s %s 2>&1', 
                      escapeshellarg($git_cmd), 
                      escapeshellarg($branch), 
                      escapeshellarg($repo_url), 
                      escapeshellarg($temp_dir));
        
        error_log("Executing git command: $cmd");
        
        $output = [];
        $return_code = 0;
        exec($cmd, $output, $return_code);
        
        error_log("Git clone output: " . print_r($output, true));
        error_log("Git clone return code: $return_code");
        
        if ($return_code !== 0) {
            // Clean up temp dir
            if (is_dir($temp_dir)) {
                exec('rm -rf ' . escapeshellarg($temp_dir));
            }
            return ['error' => 'Failed to clone repository: ' . implode(' ', $output)];
        }
        
        // Check if version.php exists and read the version
        $version_file = $temp_dir . '/version.php';
        if (!file_exists($version_file)) {
            // Clean up temp dir
            if (is_dir($temp_dir)) {
                exec('rm -rf ' . escapeshellarg($temp_dir));
            }
            return ['error' => 'Version file not found in repository'];
        }
        
        $content = file_get_contents($version_file);
        if (preg_match("/define\('APP_VERSION', '([^']+)'/", $content, $matches)) {
            $version = $matches[1];
            error_log("Found version: $version");
            
            // Clean up
            exec('rm -rf ' . escapeshellarg($temp_dir));
            
            return ['version' => $version];
        } else {
            // Clean up temp dir
            if (is_dir($temp_dir)) {
                exec('rm -rf ' . escapeshellarg($temp_dir));
            }
            return ['error' => 'Version not found in version.php'];
        }
        
    } catch (Exception $e) {
        // Clean up temp dir if it exists
        if (isset($temp_dir) && is_dir($temp_dir)) {
            exec('rm -rf ' . escapeshellarg($temp_dir));
        }
        error_log("Exception in get_available_version: " . $e->getMessage());
        return ['error' => 'Error checking version: ' . $e->getMessage()];
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'updates') {
    
    // Debug logging
    error_log("Updater POST request received");
    error_log("POST data: " . print_r($_POST, true));
    error_log("Session data: " . print_r($_SESSION, true));
    error_log("CSRF token in POST: " . ($_POST['updater_csrf_token'] ?? 'NOT SET'));
    error_log("CSRF token in session: " . ($_SESSION['updater_csrf_token'] ?? 'NOT SET'));
    
    // Validate updater internal CSRF token first
    if (!isset($_POST['updater_csrf_token']) || !validate_updater_csrf_token($_POST['updater_csrf_token'])) {
        error_log("Updater CSRF validation failed");
        error_log("POST token: " . ($_POST['updater_csrf_token'] ?? 'NOT SET'));
        error_log("Session token: " . ($_SESSION['updater_csrf_token'] ?? 'NOT SET'));
        $_SESSION['error'] = 'Invalid security token. Please refresh the page and try again.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    
    error_log("Updater CSRF validation passed");
    
    $subaction = $_POST['subaction'] ?? '';
    
    if ($subaction === 'save_settings') {
        // Save update settings
        $repo = trim($_POST['repo'] ?? '');
        $branch = trim($_POST['branch'] ?? 'main');
        
        if (empty($repo)) {
            $_SESSION['error'] = 'Repository URL is required.';
        } else {
            // Save to config
            $configFile = CONFIG_DIR . '/config.json';
            $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
            $config['update_repo_url'] = $repo;
            $config['update_branch'] = $branch;
            
            if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT))) {
                $_SESSION['success'] = 'Update settings saved successfully.';
            } else {
                $_SESSION['error'] = 'Failed to save update settings.';
            }
        }
        
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
        
    } elseif ($subaction === 'perform_update') {
        // Call the bash updater script
        $branch = trim($_POST['branch'] ?? 'main');
        $createBackup = isset($_POST['create_backup']);
        $dryRun = isset($_POST['dry_run']);
        
        try {
            // Build the command for the bash updater
            $updateScript = dirname(__DIR__) . '/update.sh';
            
            if (!file_exists($updateScript)) {
                throw new Exception('Update script not found. Please ensure update.sh exists in the CMS root directory.');
            }
            
            if (!is_executable($updateScript)) {
                throw new Exception('Update script is not executable. Please run: chmod +x update.sh');
            }
            
            // Build command arguments
            $cmd = escapeshellarg($updateScript);
            
            if ($dryRun) {
                $cmd .= ' --dry-run';
            }
            
            if (!$createBackup) {
                $cmd .= ' --no-backup';
            }
            
            // Get custom repo/branch if set
            $configFile = CONFIG_DIR . '/config.json';
            $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
            $repo = $config['update_repo_url'] ?? '';
            $defaultBranch = $config['update_branch'] ?? 'main';
            
            if (!empty($repo)) {
                $cmd .= ' -r ' . escapeshellarg($repo);
            }
            
            if ($branch !== $defaultBranch) {
                $cmd .= ' -b ' . escapeshellarg($branch);
            }
            
            error_log("Executing bash updater command: " . $cmd);
            
            // Execute the bash updater
            $output = [];
            $returnCode = 0;
            
            exec($cmd . ' 2>&1', $output, $returnCode);
            
            error_log("Bash updater return code: " . $returnCode);
            error_log("Bash updater output: " . print_r($output, true));
            
            if ($returnCode === 0) {
                $_SESSION['success'] = 'System updated successfully!';
                $_SESSION['update_output'] = $output;
            } else {
                $errorMsg = 'Update failed with return code: ' . $returnCode;
                if (!empty($output)) {
                    $errorMsg .= '. Output: ' . implode(' ', $output);
                }
                throw new Exception($errorMsg);
            }
            
        } catch (Exception $e) {
            error_log("Update error: " . $e->getMessage());
            $_SESSION['error'] = 'Update failed: ' . $e->getMessage();
        }
        
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
        
    } elseif ($subaction === 'check_version') {
        // Check for available version updates
        $configFile = CONFIG_DIR . '/config.json';
        $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
        $repo = $config['update_repo_url'] ?? '';
        $branch = $config['update_branch'] ?? 'main';
        
        if (empty($repo)) {
            $_SESSION['error'] = 'Repository not configured. Please configure the update repository first.';
        } else {
            $version_result = get_available_version($repo, $branch);
            if (isset($version_result['version'])) {
                $_SESSION['success'] = 'Version check completed. Available version: ' . $version_result['version'];
            } else {
                $_SESSION['error'] = 'Version check failed: ' . ($version_result['error'] ?? 'Unknown error');
            }
        }
        
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}
?>
