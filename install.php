<?php
// Set appropriate error reporting for installation
if (getenv('FCMS_DEBUG') === 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
}
ini_set('log_errors', 1);

// Include proper session configuration
require_once __DIR__ . '/includes/session.php';

// Ensure session is started and available
if (session_status() !== PHP_SESSION_ACTIVE) {
    error_log("Warning: Session not active, attempting to start");
    session_start();
}

// Generate CSRF token
if (isset($_SESSION)) {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrf_token = $_SESSION['csrf_token'];
} else {
    error_log("Warning: Session not available, using fallback CSRF protection");
    $csrf_token = bin2hex(random_bytes(32));
}

// CSRF validation function
function validate_csrf_token(): bool {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        error_log("CSRF validation failed - POST token: " . (isset($_POST['csrf_token']) ? 'set' : 'missing') . 
                  ", SESSION token: " . (isset($_SESSION['csrf_token']) ? 'set' : 'missing') .
                  ", Session ID: " . session_id());
        return false;
    }
    $result = hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    if (!$result) {
        error_log("CSRF token mismatch - POST: " . substr($_POST['csrf_token'], 0, 8) . "... vs SESSION: " . substr($_SESSION['csrf_token'], 0, 8) . "...");
    }
    return $result;
}

// Rate limiting for admin creation
function check_rate_limit(string $action, int $max_attempts = 3, int $time_window = 300): bool {
    $key = "rate_limit_{$action}";
    $now = time();

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'reset_time' => $now + $time_window];
    }

    if ($now > $_SESSION[$key]['reset_time']) {
        $_SESSION[$key] = ['count' => 0, 'reset_time' => $now + $time_window];
    }

    if ($_SESSION[$key]['count'] >= $max_attempts) {
        return false;
    }

    $_SESSION[$key]['count']++;
    return true;
}

// Minimal bootstrap for filesystem paths with validation
$projectRoot = __DIR__;
$CONFIG_DIR = getenv('FCMS_CONFIG_DIR') ?: ($projectRoot . '/config');

// Validate CONFIG_DIR is within project root for security
if (strpos(realpath($CONFIG_DIR), realpath($projectRoot)) !== 0) {
    $CONFIG_DIR = $projectRoot . '/config';
}

$ADMIN_UPLOADS_DIR = $projectRoot . '/admin/uploads';
$UPLOADS_DIR = $projectRoot . '/uploads';
$CONTENT_DIR = $projectRoot . '/content';
$SESSIONS_DIR = $projectRoot . '/sessions';
$CACHE_DIR = $projectRoot . '/cache';
$BACKUPS_DIR = $projectRoot . '/backups';
$UPDATES_DIR = $projectRoot . '/.fcms_updates';

// Command whitelist for security
$ALLOWED_COMMANDS = [
    'which' => ['which'],
    'npm' => ['npm', 'init', '-y'],
    'npm_install' => ['npm', 'install', 'fs-extra', 'handlebars', 'marked', '--save'],
    'npm_install_dev' => ['npm', 'install', 'sass', '--save-dev'],
    'npm_install_tailwind' => ['npm', 'install', 'tailwindcss@^3.4.0', '--save-dev'],
    'npx_tailwind_build' => ['npx', 'tailwindcss', '-i', './src/input.css', '-o', './public/css/output.css', '--minify']
];

function check_extension(string $ext): array {
    return [
        'name' => $ext,
        'loaded' => extension_loaded($ext)
    ];
}

function run_cmd(array $cmd, ?string $cwd = null): array {
    global $ALLOWED_COMMANDS;

    $allowed = false;
    foreach ($ALLOWED_COMMANDS as $pattern => $allowed_cmd) {
        if (array_slice($cmd, 0, count($allowed_cmd)) === $allowed_cmd) {
            $allowed = true;
            break;
        }
    }

    if (!$allowed) {
        return ['code' => 1, 'out' => '', 'err' => 'Command not allowed for security reasons'];
    }

    if ($cwd && strpos(realpath($cwd), realpath($GLOBALS['projectRoot'])) !== 0) {
        return ['code' => 1, 'out' => '', 'err' => 'Invalid working directory'];
    }

    $descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];

    $process = proc_open($cmd, $descriptorspec, $pipes, $cwd ?: null);
    if (!is_resource($process)) {
        return ['code' => 1, 'out' => '', 'err' => 'Failed to start process'];
    }

    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $code = proc_close($process);

    return ['code' => $code, 'out' => $out, 'err' => $err];
}

// ── CLI MODE ──────────────────────────────────────────────────────────
if (PHP_SAPI === 'cli') {
    $options = getopt('', ['check', 'create-dirs', 'install-export-deps', 'install-tailwind', 'install-dev-deps', 'create-admin:', 'password:', 'password-file:']);
    $exitCode = 0;

    if (isset($options['check'])) {
        echo "FearlessCMS Installer (CLI)\n";
        echo "Project root: {$projectRoot}\n";
        echo "PHP version: " . PHP_VERSION . "\n";
        echo "Extensions:\n";
        foreach (['curl','json','mbstring','phar','zip','openssl'] as $ext) {
            echo "  - {$ext}: " . (extension_loaded($ext) ? 'loaded' : 'missing') . "\n";
        }
        echo "Directories:\n";
        foreach ([
            $CONFIG_DIR, $ADMIN_UPLOADS_DIR, $UPLOADS_DIR, $CONTENT_DIR,
            $SESSIONS_DIR, $CACHE_DIR, $BACKUPS_DIR, $UPDATES_DIR,
        ] as $d) {
            $exists = is_dir($d);
            $writable = $exists ? is_writable($d) : is_writable(dirname($d));
            echo "  - {$d}: " . ($exists ? 'exists' : 'missing') . ', ' . ($writable ? 'writable' : 'not writable') . "\n";
        }
        $node = run_cmd(['which', 'node']);
        $npm = run_cmd(['which', 'npm']);
        if ($node['code'] === 0 && $npm['code'] === 0) {
            echo "Node.js: available\n";
        } else {
            echo "Node.js: not available\n";
        }
    }

    if (isset($options['create-dirs'])) {
        $dirs = [$CONFIG_DIR, $ADMIN_UPLOADS_DIR, $UPLOADS_DIR, $CONTENT_DIR, $SESSIONS_DIR, $CACHE_DIR, $BACKUPS_DIR, $UPDATES_DIR];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                if (mkdir($dir, 0755, true)) {
                    echo "Created directory: {$dir}\n";
                } else {
                    echo "Failed to create directory: {$dir}\n";
                    $exitCode = 1;
                }
            } else {
                echo "Directory exists: {$dir}\n";
            }
        }
        $configPath = $projectRoot . '/includes/config.php';
        if (file_exists($configPath)) {
            require_once $configPath;
            echo "Initialized default configuration files.\n";
        }
    }

    if (isset($options['install-export-deps'])) {
        $node = run_cmd(['which', 'node']);
        $npm = run_cmd(['which', 'npm']);
        if ($node['code'] !== 0 || $npm['code'] !== 0) {
            echo "Node.js or npm not found.\n";
            $exitCode = 1;
        } else {
            if (!file_exists($projectRoot . '/package.json')) {
                $init = run_cmd(['npm', 'init', '-y'], $projectRoot);
                echo 'npm init: exit ' . $init['code'] . "\n";
                if ($init['code'] !== 0) $exitCode = 1;
            }
            $install = run_cmd(['npm', 'install', 'fs-extra', 'handlebars', 'marked', '--save'], $projectRoot);
            echo 'npm install: exit ' . $install['code'] . "\n";
            if (trim($install['out'])) echo strip_tags($install['out']) . "\n";
            if (trim($install['err'])) echo strip_tags($install['err']) . "\n";
            if ($install['code'] !== 0) $exitCode = 1;
        }
    }

    if (isset($options['install-dev-deps'])) {
        $node = run_cmd(['which', 'node']);
        $npm = run_cmd(['which', 'npm']);
        if ($node['code'] !== 0 || $npm['code'] !== 0) {
            echo "Node.js or npm not found.\n";
            $exitCode = 1;
        } else {
            $install = run_cmd(['npm', 'install', 'sass', '--save-dev'], $projectRoot);
            echo 'SASS install: exit ' . $install['code'] . "\n";
            if (trim($install['out'])) echo strip_tags($install['out']) . "\n";
            if (trim($install['err'])) echo strip_tags($install['err']) . "\n";
            if ($install['code'] !== 0) $exitCode = 1;
        }
    }

    if (isset($options['install-tailwind'])) {
        $node = run_cmd(['which', 'node']);
        $npm = run_cmd(['which', 'npm']);
        if ($node['code'] !== 0 || $npm['code'] !== 0) {
            echo "Node.js or npm not found.\n";
            $exitCode = 1;
        } else {
            if (!file_exists($projectRoot . '/package.json')) {
                $init = run_cmd(['npm', 'init', '-y'], $projectRoot);
                echo 'npm init: exit ' . $init['code'] . "\n";
                if ($init['code'] !== 0) $exitCode = 1;
            }
            $install = run_cmd(['npm', 'install', 'tailwindcss@^3.4.0', '--save-dev'], $projectRoot);
            echo 'Tailwind CSS install: exit ' . $install['code'] . "\n";
            if (trim($install['out'])) echo strip_tags($install['out']) . "\n";
            if (trim($install['err'])) echo strip_tags($install['err']) . "\n";
            if ($install['code'] !== 0) $exitCode = 1;
        }
    }

    if (isset($options['create-admin'])) {
        $username = trim($options['create-admin']);
        $password = '';
        if (isset($options['password'])) {
            $password = (string)$options['password'];
        } elseif (isset($options['password-file'])) {
            $pf = (string)$options['password-file'];
            if (is_readable($pf)) {
                $password = rtrim(file_get_contents($pf));
            }
        } else {
            echo "Enter password for '{$username}': ";
            $password = trim(fgets(STDIN));
        }
        if ($username === '' || $password === '') {
            echo "Username and password are required.\n";
            exit(1);
        }
        if (!preg_match('/^[A-Za-z0-9_\-]{3,50}$/', $username)) {
            echo "Invalid username. Use 3-50 chars [A-Za-z0-9_-].\n";
            exit(1);
        }
        $usersFile = $CONFIG_DIR . '/users.json';
        $users = [];
        if (file_exists($usersFile)) {
            $decoded = json_decode(file_get_contents($usersFile), true);
            if (is_array($decoded)) $users = $decoded;
        }
        foreach ($users as $u) {
            if (($u['username'] ?? '') === $username) {
                echo "User already exists: {$username}\n";
                exit(1);
            }
        }
        $users[] = [
            'id' => $username,
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'administrator',
            'permissions' => []
        ];
        if (!is_dir($CONFIG_DIR)) {
            if (!mkdir($CONFIG_DIR, 0755, true)) {
                echo "Failed to create config directory\n";
                exit(1);
            }
        }
        if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT)) !== false) {
            echo "Admin account created: {$username}\n";
        } else {
            echo "Failed to write users.json\n";
            exit(1);
        }
    }

    if (empty($options)) {
        echo "Usage: php install.php [--check] [--create-dirs] [--install-export-deps] [--install-dev-deps] [--install-tailwind] [--create-admin=<username> --password=<pwd>|--password-file=<file>]\n";
    }

    echo "\n⚠️  SECURITY WARNING: After installation, delete this file!\n";
    echo "   Run: rm install.php\n\n";

    exit($exitCode);
}

// ── WEB MODE ──────────────────────────────────────────────────────────

// Determine current step
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($step < 1 || $step > 5) $step = 1;

$action = $_POST['action'] ?? '';
$resultMessages = [];
$errorMessages = [];

// Validate CSRF token for all POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validate_csrf_token()) {
    $errorMessages[] = 'Invalid or expired security token. Please refresh the page and try again.';
    $action = '';
}

// ── Handle POST actions ──

if ($action === 'create_dirs') {
    $dirs = [$CONFIG_DIR, $ADMIN_UPLOADS_DIR, $UPLOADS_DIR, $CONTENT_DIR, $SESSIONS_DIR, $CACHE_DIR, $BACKUPS_DIR, $UPDATES_DIR, $projectRoot . '/public/css'];
    $allOk = true;
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                $resultMessages[] = "Created: " . basename($dir);
            } else {
                $errorMessages[] = "Failed to create: " . basename($dir);
                $allOk = false;
            }
        }
    }
    $configPath = $projectRoot . '/includes/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    }
    if ($allOk && empty($errorMessages)) {
        $resultMessages[] = 'All directories created successfully.';
    }
}

if ($action === 'install_export_deps') {
    $node = run_cmd(['which', 'node']);
    $npm = run_cmd(['which', 'npm']);
    if ($node['code'] !== 0 || $npm['code'] !== 0) {
        $errorMessages[] = 'Node.js or npm not found. Please install Node.js first.';
    } else {
        if (!file_exists($projectRoot . '/package.json')) {
            $init = run_cmd(['npm', 'init', '-y'], $projectRoot);
            if ($init['code'] !== 0) $errorMessages[] = 'npm init failed.';
        }
        $install = run_cmd(['npm', 'install', 'fs-extra', 'handlebars', 'marked', '--save'], $projectRoot);
        if ($install['code'] === 0) {
            $resultMessages[] = 'Node dependencies installed successfully.';
        } else {
            $errorMessages[] = 'npm install failed: ' . htmlspecialchars(trim($install['err']));
        }
    }
}

if ($action === 'install_dev_deps') {
    $node = run_cmd(['which', 'node']);
    $npm = run_cmd(['which', 'npm']);
    if ($node['code'] !== 0 || $npm['code'] !== 0) {
        $errorMessages[] = 'Node.js or npm not found.';
    } else {
        $install = run_cmd(['npm', 'install', 'sass', '--save-dev'], $projectRoot);
        if ($install['code'] === 0) {
            $resultMessages[] = 'SASS compiler installed successfully.';
        } else {
            $errorMessages[] = 'SASS install failed: ' . htmlspecialchars(trim($install['err']));
        }
    }
}

if ($action === 'install_tailwind') {
    $node = run_cmd(['which', 'node']);
    $npm = run_cmd(['which', 'npm']);
    if ($node['code'] !== 0 || $npm['code'] !== 0) {
        $errorMessages[] = 'Node.js or npm not found.';
    } else {
        if (!file_exists($projectRoot . '/package.json')) {
            $init = run_cmd(['npm', 'init', '-y'], $projectRoot);
            if ($init['code'] !== 0) $errorMessages[] = 'npm init failed.';
        }
        $install = run_cmd(['npm', 'install', 'tailwindcss@^3.4.0', '--save-dev'], $projectRoot);
        if ($install['code'] === 0) {
            // Build the CSS so login/admin pages are styled
            if (!is_dir($projectRoot . '/public/css')) {
                mkdir($projectRoot . '/public/css', 0755, true);
            }
            $build = run_cmd(['npx', 'tailwindcss', '-i', './src/input.css', '-o', './public/css/output.css', '--minify'], $projectRoot);
            if ($build['code'] === 0) {
                $resultMessages[] = 'Tailwind CSS installed and CSS built successfully.';
            } else {
                $resultMessages[] = 'Tailwind CSS installed.';
                $errorMessages[] = 'CSS build failed: ' . htmlspecialchars(trim($build['err']));
            }
        } else {
            $errorMessages[] = 'Tailwind install failed: ' . htmlspecialchars(trim($install['err']));
        }
    }
}

// Admin user state
$usersFile = $CONFIG_DIR . '/users.json';
$existingAdmins = [];
if (file_exists($usersFile)) {
    $usersData = json_decode(file_get_contents($usersFile), true);
    if (is_array($usersData)) {
        foreach ($usersData as $u) {
            if (in_array($u['role'] ?? '', ['administrator', 'admin'])) {
                $existingAdmins[] = $u['username'] ?? '';
            }
        }
    }
}

if ($action === 'create_admin') {
    if (!check_rate_limit('create_admin', 3, 300)) {
        $errorMessages[] = 'Too many attempts. Please wait 5 minutes.';
    } else {
        $username = trim($_POST['admin_user'] ?? '');
        $password = (string)($_POST['admin_pass'] ?? '');
        $confirm  = (string)($_POST['admin_pass_confirm'] ?? '');

        if ($username === '' || $password === '' || $confirm === '') {
            $errorMessages[] = 'All fields are required.';
        } elseif ($password !== $confirm) {
            $errorMessages[] = 'Passwords do not match.';
        } elseif (!preg_match('/^[A-Za-z0-9_\-]{3,50}$/', $username)) {
            $errorMessages[] = 'Username must be 3-50 characters (letters, numbers, underscores, dashes).';
        } elseif (strlen($password) < 8) {
            $errorMessages[] = 'Password must be at least 8 characters.';
        } else {
            $users = [];
            if (file_exists($usersFile)) {
                $decoded = json_decode(file_get_contents($usersFile), true);
                if (is_array($decoded)) $users = $decoded;
            }
            $duplicate = false;
            foreach ($users as $u) {
                if (($u['username'] ?? '') === $username) {
                    $errorMessages[] = 'A user with that username already exists.';
                    $duplicate = true;
                    break;
                }
            }
            if (!$duplicate) {
                $users[] = [
                    'id' => $username,
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => 'administrator',
                    'permissions' => [],
                    'created_at' => date('Y-m-d H:i:s')
                ];
                if (!is_dir($CONFIG_DIR)) {
                    mkdir($CONFIG_DIR, 0755, true);
                }
                if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT)) !== false) {
                    $resultMessages[] = 'Admin account created successfully!';
                    $existingAdmins[] = $username;
                } else {
                    $errorMessages[] = 'Failed to write users.json.';
                }
            }
        }
    }
}

// ── Gather environment data ──

$phpVersionOk = version_compare(PHP_VERSION, '8.0.0', '>=');
$extensions = [
    check_extension('curl'),
    check_extension('json'),
    check_extension('mbstring'),
    check_extension('phar'),
    check_extension('zip'),
    check_extension('openssl'),
];
$allExtensionsOk = true;
foreach ($extensions as $ext) {
    if (!$ext['loaded'] && $ext['name'] !== 'zip') {
        $allExtensionsOk = false;
    }
}

$directories = [];
$dirs_to_check = [$CONFIG_DIR, $ADMIN_UPLOADS_DIR, $UPLOADS_DIR, $CONTENT_DIR, $SESSIONS_DIR, $CACHE_DIR, $BACKUPS_DIR, $UPDATES_DIR];
$allDirsOk = true;
foreach ($dirs_to_check as $dir) {
    $exists = is_dir($dir);
    $writable = $exists ? is_writable($dir) : is_writable(dirname($dir));
    $directories[] = ['path' => $dir, 'exists' => $exists, 'writable' => $writable];
    if (!$exists || !$writable) $allDirsOk = false;
}

$hasNode = run_cmd(['which', 'node']);
$hasNpm  = run_cmd(['which', 'npm']);

// Step labels for the progress bar
$steps = [
    1 => 'Environment',
    2 => 'Directories',
    3 => 'Admin Account',
    4 => 'Dependencies',
    5 => 'Finish',
];

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install FearlessCMS</title>
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background: #0f172a;
        color: #e2e8f0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
    }
    a { color: #60a5fa; text-decoration: none; }
    a:hover { text-decoration: underline; }
    code {
        background: rgba(255,255,255,0.08);
        padding: 0.15em 0.4em;
        border-radius: 4px;
        font-size: 0.9em;
        font-family: 'Fira Code', 'Cascadia Code', 'JetBrains Mono', monospace;
    }

    .installer {
        width: 100%;
        max-width: 640px;
    }

    /* Logo / Header */
    .header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .header h1 {
        font-size: 1.75rem;
        font-weight: 700;
        color: #f8fafc;
        letter-spacing: -0.02em;
    }
    .header h1 span { color: #60a5fa; }
    .header p {
        color: #94a3b8;
        margin-top: 0.25rem;
        font-size: 0.9rem;
    }

    /* Step indicator */
    .steps {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0;
        margin-bottom: 2rem;
    }
    .step-dot {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        font-weight: 600;
        flex-shrink: 0;
        transition: all 0.2s;
    }
    .step-dot.completed {
        background: #22c55e;
        color: #fff;
    }
    .step-dot.active {
        background: #3b82f6;
        color: #fff;
        box-shadow: 0 0 0 4px rgba(59,130,246,0.25);
    }
    .step-dot.upcoming {
        background: #1e293b;
        color: #64748b;
        border: 2px solid #334155;
    }
    .step-line {
        width: 40px;
        height: 2px;
        flex-shrink: 0;
    }
    .step-line.done { background: #22c55e; }
    .step-line.pending { background: #334155; }

    .step-labels {
        display: flex;
        justify-content: space-between;
        margin-top: 0.5rem;
        padding: 0 0.25rem;
    }
    .step-label {
        font-size: 0.7rem;
        color: #64748b;
        text-align: center;
        width: 76px;
    }
    .step-label.active { color: #93c5fd; }

    /* Card */
    .card {
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 12px;
        padding: 2rem;
    }
    .card h2 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #f1f5f9;
        margin-bottom: 1.25rem;
    }

    /* Messages */
    .msg {
        padding: 0.75rem 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }
    .msg-success { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.3); color: #86efac; }
    .msg-error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5; }

    /* Check items */
    .check-list { list-style: none; }
    .check-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.6rem 0;
        border-bottom: 1px solid #334155;
        font-size: 0.9rem;
    }
    .check-item:last-child { border-bottom: none; }
    .badge {
        display: inline-block;
        padding: 0.15em 0.6em;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .badge-ok { background: rgba(34,197,94,0.15); color: #4ade80; }
    .badge-warn { background: rgba(234,179,8,0.15); color: #facc15; }
    .badge-fail { background: rgba(239,68,68,0.15); color: #f87171; }

    /* Dir list */
    .dir-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0.75rem;
        background: #0f172a;
        border-radius: 6px;
        margin-bottom: 0.5rem;
        font-size: 0.85rem;
        font-family: 'Fira Code', monospace;
    }
    .dir-status {
        display: flex;
        gap: 0.75rem;
        font-family: -apple-system, BlinkMacSystemFont, sans-serif;
    }

    /* Forms */
    label {
        display: block;
        font-size: 0.85rem;
        font-weight: 500;
        color: #cbd5e1;
        margin-bottom: 0.35rem;
    }
    input[type="text"],
    input[type="password"] {
        width: 100%;
        padding: 0.6rem 0.75rem;
        background: #0f172a;
        border: 1px solid #475569;
        border-radius: 8px;
        color: #e2e8f0;
        font-size: 0.95rem;
        outline: none;
        transition: border-color 0.2s;
    }
    input[type="text"]:focus,
    input[type="password"]:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
    }
    .form-group { margin-bottom: 1rem; }

    /* Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.5rem;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.15s;
        text-decoration: none;
    }
    .btn-primary { background: #3b82f6; color: #fff; }
    .btn-primary:hover { background: #2563eb; text-decoration: none; }
    .btn-success { background: #22c55e; color: #fff; }
    .btn-success:hover { background: #16a34a; text-decoration: none; }
    .btn-secondary { background: #334155; color: #e2e8f0; }
    .btn-secondary:hover { background: #475569; text-decoration: none; }
    .btn-outline {
        background: transparent;
        color: #94a3b8;
        border: 1px solid #475569;
    }
    .btn-outline:hover { background: #334155; color: #e2e8f0; text-decoration: none; }

    .btn-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1.5rem;
    }
    .btn-row-right {
        display: flex;
        justify-content: flex-end;
        margin-top: 1.5rem;
    }

    /* Dep section */
    .dep-group {
        background: #0f172a;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .dep-group h3 {
        font-size: 0.95rem;
        font-weight: 600;
        color: #e2e8f0;
        margin-bottom: 0.5rem;
    }
    .dep-group p {
        font-size: 0.8rem;
        color: #94a3b8;
        margin-bottom: 0.75rem;
        line-height: 1.5;
    }

    /* Finish */
    .finish-icon {
        font-size: 3rem;
        text-align: center;
        margin-bottom: 1rem;
    }
    .finish-list {
        list-style: none;
        margin-top: 1rem;
    }
    .finish-list li {
        padding: 0.5rem 0;
        border-bottom: 1px solid #334155;
        font-size: 0.9rem;
    }
    .finish-list li:last-child { border-bottom: none; }

    .warning-box {
        background: rgba(239,68,68,0.08);
        border: 1px solid rgba(239,68,68,0.25);
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1.5rem;
    }
    .warning-box h3 {
        color: #f87171;
        font-size: 0.95rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    .warning-box p {
        color: #fca5a5;
        font-size: 0.85rem;
        line-height: 1.5;
    }

    /* Loading overlay */
    .loading-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15,23,42,0.85);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 1.25rem;
    }
    .loading-overlay.active { display: flex; }
    .loading-text {
        color: #e2e8f0;
        font-size: 1rem;
        font-weight: 600;
    }
    .loading-subtext {
        color: #94a3b8;
        font-size: 0.8rem;
    }
    .progress-track {
        width: 320px;
        max-width: 80vw;
        height: 8px;
        background: #1e293b;
        border-radius: 9999px;
        overflow: hidden;
    }
    .progress-bar {
        height: 100%;
        width: 0%;
        background: linear-gradient(90deg, #3b82f6, #60a5fa);
        border-radius: 9999px;
        animation: progress-indeterminate 2s ease-in-out infinite;
    }
    @keyframes progress-indeterminate {
        0% { width: 0%; margin-left: 0%; }
        50% { width: 60%; margin-left: 20%; }
        100% { width: 0%; margin-left: 100%; }
    }
</style>
</head>
<body>

<div class="installer">
    <div class="header">
        <h1>Fearless<span>CMS</span></h1>
        <p>Installation Wizard</p>
    </div>

    <!-- Step indicator -->
    <div class="steps">
        <?php foreach ($steps as $num => $label): ?>
            <?php if ($num > 1): ?>
                <div class="step-line <?php echo $num <= $step ? 'done' : 'pending'; ?>"></div>
            <?php endif; ?>
            <div class="step-dot <?php echo $num < $step ? 'completed' : ($num === $step ? 'active' : 'upcoming'); ?>">
                <?php echo $num < $step ? '✓' : $num; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="step-labels">
        <?php foreach ($steps as $num => $label): ?>
            <div class="step-label <?php echo $num === $step ? 'active' : ''; ?>"><?php echo $label; ?></div>
        <?php endforeach; ?>
    </div>

    <div class="card">

    <?php if (!empty($resultMessages) || !empty($errorMessages)): ?>
        <?php foreach ($resultMessages as $msg): ?>
            <div class="msg msg-success"><?php echo $msg; ?></div>
        <?php endforeach; ?>
        <?php foreach ($errorMessages as $msg): ?>
            <div class="msg msg-error"><?php echo $msg; ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($step === 1): // ── STEP 1: Environment ── ?>
        <h2>Environment Check</h2>

        <div class="check-item" style="border-bottom: 1px solid #334155; padding-bottom: 0.75rem; margin-bottom: 0.5rem;">
            <span>PHP Version</span>
            <span>
                <code><?php echo htmlspecialchars(PHP_VERSION); ?></code>
                <span class="badge <?php echo $phpVersionOk ? 'badge-ok' : 'badge-fail'; ?>">
                    <?php echo $phpVersionOk ? '≥ 8.0 ✓' : 'Requires 8.0+'; ?>
                </span>
            </span>
        </div>

        <h3 style="font-size:0.9rem; font-weight:600; color:#94a3b8; margin: 1rem 0 0.5rem; text-transform:uppercase; letter-spacing:0.05em;">Extensions</h3>
        <ul class="check-list">
            <?php foreach ($extensions as $ext): ?>
                <li class="check-item">
                    <span><code><?php echo htmlspecialchars($ext['name']); ?></code></span>
                    <span class="badge <?php echo $ext['loaded'] ? 'badge-ok' : ($ext['name'] === 'zip' ? 'badge-warn' : 'badge-fail'); ?>">
                        <?php echo $ext['loaded'] ? 'Loaded' : ($ext['name'] === 'zip' ? 'Optional' : 'Missing'); ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
        <p style="font-size:0.75rem; color:#64748b; margin-top:0.75rem;">
            <code>phar</code> is preferred for the updater. <code>zip</code> is an optional fallback.
        </p>

        <div class="btn-row-right">
            <a href="?step=2" class="btn btn-primary">Continue →</a>
        </div>

    <?php elseif ($step === 2): // ── STEP 2: Directories ── ?>
        <h2>Directories &amp; Permissions</h2>

        <?php foreach ($directories as $d): ?>
            <div class="dir-item">
                <span><?php echo htmlspecialchars(basename($d['path'])); ?>/</span>
                <div class="dir-status">
                    <span class="badge <?php echo $d['exists'] ? 'badge-ok' : 'badge-fail'; ?>">
                        <?php echo $d['exists'] ? 'exists' : 'missing'; ?>
                    </span>
                    <span class="badge <?php echo $d['writable'] ? 'badge-ok' : 'badge-fail'; ?>">
                        <?php echo $d['writable'] ? 'writable' : 'not writable'; ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (!$allDirsOk): ?>
            <form method="POST" action="?step=2" style="margin-top: 1rem;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="create_dirs">
                <button type="submit" class="btn btn-success">Create Directories</button>
            </form>
        <?php endif; ?>

        <div class="btn-row">
            <a href="?step=1" class="btn btn-outline">← Back</a>
            <a href="?step=3" class="btn btn-primary">Continue →</a>
        </div>

    <?php elseif ($step === 3): // ── STEP 3: Admin Account ── ?>
        <h2>Create Admin Account</h2>

        <?php if (!empty($existingAdmins)): ?>
            <div class="msg msg-success">
                Admin account already exists: <strong><?php echo htmlspecialchars(implode(', ', $existingAdmins)); ?></strong>
            </div>
            <p style="font-size:0.85rem; color:#94a3b8;">You can skip this step or create an additional admin.</p>
        <?php endif; ?>

        <form method="POST" action="?step=3">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="create_admin">

            <div class="form-group">
                <label for="admin_user">Username</label>
                <input type="text" id="admin_user" name="admin_user" required
                       pattern="[A-Za-z0-9_\-]{3,50}"
                       title="3-50 characters: letters, numbers, underscores, dashes"
                       placeholder="e.g. admin">
            </div>

            <div class="form-group">
                <label for="admin_pass">Password</label>
                <input type="password" id="admin_pass" name="admin_pass" required
                       minlength="8" placeholder="Minimum 8 characters">
            </div>

            <div class="form-group">
                <label for="admin_pass_confirm">Confirm Password</label>
                <input type="password" id="admin_pass_confirm" name="admin_pass_confirm" required
                       minlength="8" placeholder="Re-enter password">
            </div>

            <button type="submit" class="btn btn-success">Create Account</button>
        </form>

        <div class="btn-row">
            <a href="?step=2" class="btn btn-outline">← Back</a>
            <a href="?step=4" class="btn btn-primary"><?php echo !empty($existingAdmins) ? 'Continue →' : 'Skip →'; ?></a>
        </div>

    <?php elseif ($step === 4): // ── STEP 4: Dependencies ── ?>
        <h2>Dependencies</h2>

        <?php
        $nodeAvailable = ($hasNode['code'] === 0 && $hasNpm['code'] === 0);
        $cssBuilt = file_exists($projectRoot . '/public/css/output.css');
        ?>

        <?php if (!$nodeAvailable): ?>
            <div class="msg msg-error">
                Node.js/npm not found. Install Node.js to use these features.
            </div>
        <?php endif; ?>

        <div class="dep-group" style="<?php echo !$cssBuilt ? 'border: 1px solid rgba(59,130,246,0.4);' : ''; ?>">
            <h3>Tailwind CSS <?php if (!$cssBuilt): ?><span class="badge badge-warn">Required</span><?php else: ?><span class="badge badge-ok">Built</span><?php endif; ?></h3>
            <p>Builds the CSS used by the login page and admin panel.<?php if (!$cssBuilt): ?> <strong style="color:#facc15;">Install this to style the admin interface.</strong><?php endif; ?></p>
            <form method="POST" action="?step=4" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="install_tailwind">
                <button type="submit" class="btn <?php echo !$cssBuilt ? 'btn-primary' : 'btn-secondary'; ?>" <?php echo !$nodeAvailable ? 'disabled' : ''; ?>><?php echo $cssBuilt ? 'Rebuild CSS' : 'Install & Build CSS'; ?></button>
            </form>
        </div>

        <p style="font-size:0.8rem; color:#64748b; margin-bottom:0.75rem; margin-top:1.5rem;">The following are optional and can be installed later:</p>

        <div class="dep-group">
            <h3>Export Tool Dependencies</h3>
            <p>Packages for the static site exporter: <code>fs-extra</code>, <code>handlebars</code>, <code>marked</code>.</p>
            <form method="POST" action="?step=4" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="install_export_deps">
                <button type="submit" class="btn btn-secondary" <?php echo !$nodeAvailable ? 'disabled' : ''; ?>>Install Export Deps</button>
            </form>
        </div>

        <div class="dep-group">
            <h3>SASS Compiler</h3>
            <p>For theme development with SCSS support.</p>
            <form method="POST" action="?step=4" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="install_dev_deps">
                <button type="submit" class="btn btn-secondary" <?php echo !$nodeAvailable ? 'disabled' : ''; ?>>Install SASS</button>
            </form>
        </div>

        <div class="btn-row">
            <a href="?step=3" class="btn btn-outline">← Back</a>
            <a href="?step=5" class="btn btn-primary">Continue →</a>
        </div>

    <?php elseif ($step === 5): // ── STEP 5: Finish ── ?>
        <div class="finish-icon">🐺</div>
        <h2 style="text-align:center;">Installation Complete</h2>
        <p style="text-align:center; color:#94a3b8; font-size:0.9rem; margin-bottom:1.25rem;">
            FearlessCMS is ready to use. Here's what to do next:
        </p>

        <ul class="finish-list">
            <li>📋 Visit <a href="/admin/"><code>/admin/</code></a> to log in and configure your site.</li>
            <li>🎨 Use the Themes section to customize your site's look.</li>
            <li>🧩 Use the Store to browse and install plugins &amp; themes.</li>
            <li>📤 Use <strong>Export Site</strong> in the Dashboard for a static version.</li>
            <li>🔄 Use Updates in Mission Control to keep FearlessCMS current.</li>
        </ul>

        <div class="warning-box">
            <h3>⚠️ Security: Delete This File</h3>
            <p>
                After installation, delete <code>install.php</code> to prevent unauthorized access.<br>
                Run: <code>rm install.php</code>
            </p>
        </div>

        <div style="text-align:center; margin-top:1.5rem;">
            <a href="/admin/" class="btn btn-primary">Go to Mission Control →</a>
        </div>

    <?php endif; ?>

    </div><!-- .card -->

    <!-- CLI hint -->
    <p style="text-align:center; margin-top:1.5rem; font-size:0.75rem; color:#475569;">
        CLI available: <code>php install.php --help</code>
    </p>
</div>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-text" id="loadingText">Installing…</div>
    <div class="progress-track"><div class="progress-bar"></div></div>
    <div class="loading-subtext" id="loadingSubtext">This may take a moment</div>
</div>

<script>
(function() {
    var labels = {
        install_tailwind: ['Installing Tailwind CSS & building styles…', 'This may take up to a minute'],
        install_export_deps: ['Installing export dependencies…', 'Downloading packages from npm'],
        install_dev_deps: ['Installing SASS compiler…', 'Downloading packages from npm'],
        create_dirs: ['Creating directories…', 'Setting up file structure'],
        create_admin: ['Creating admin account…', 'Almost instant']
    };

    document.querySelectorAll('form[method="POST"]').forEach(function(form) {
        form.addEventListener('submit', function() {
            var action = form.querySelector('input[name="action"]');
            if (!action) return;
            var key = action.value;
            var overlay = document.getElementById('loadingOverlay');
            var text = document.getElementById('loadingText');
            var sub = document.getElementById('loadingSubtext');
            if (labels[key]) {
                text.textContent = labels[key][0];
                sub.textContent = labels[key][1];
            }
            overlay.classList.add('active');
        });
    });
})();
</script>

</body>
</html>
