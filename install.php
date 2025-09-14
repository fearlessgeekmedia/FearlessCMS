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

// Include proper session configuration for macOS compatibility
require_once __DIR__ . '/includes/session.php';

// Ensure session is started and available
if (session_status() !== PHP_SESSION_ACTIVE) {
    error_log("Warning: Session not active, attempting to start");
    session_start();
}

// Generate CSRF token using the proper session handling
$csrf_token = null;
if (isset($_SESSION)) {
    // Generate CSRF token if not exists
    if (!isset($_SESSION['csrf_token'])) {
        // Use random_bytes() if available (PHP 7.0+), otherwise fallback
        if (function_exists('random_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        } else {
            $_SESSION['csrf_token'] = bin2hex(md5(uniqid(mt_rand(), true)));
        }
    }
    $csrf_token = $_SESSION['csrf_token'];
} else {
    // Session not available - use simple token fallback
    error_log("Warning: Session not available, using fallback CSRF protection");
    if (function_exists('random_bytes')) {
        $csrf_token = bin2hex(random_bytes(32));
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        $csrf_token = bin2hex(openssl_random_pseudo_bytes(32));
    } else {
        $csrf_token = bin2hex(md5(uniqid(mt_rand(), true)));
    }
}

// CSRF validation function
function validate_csrf_token(): bool {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        if (getenv('FCMS_DEBUG') === 'true') {
            error_log("CSRF validation failed: POST token=" . ($_POST['csrf_token'] ?? 'NOT SET') . ", SESSION token=" . ($_SESSION['csrf_token'] ?? 'NOT SET'));
        }
        return false;
    }
    // Use hash_equals() if available (PHP 5.6+), otherwise fallback to comparison
    if (function_exists('hash_equals')) {
        $valid = hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
        if (getenv('FCMS_DEBUG') === 'true') {
            error_log("CSRF validation result: " . ($valid ? 'VALID' : 'INVALID'));
        }
        return $valid;
    } else {
        // Fallback comparison (less secure against timing attacks)
        $valid = $_SESSION['csrf_token'] === $_POST['csrf_token'];
        if (getenv('FCMS_DEBUG') === 'true') {
            error_log("CSRF validation result: " . ($valid ? 'VALID' : 'INVALID'));
        }
        return $valid;
    }
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
    'npm_install_tailwind' => ['npm', 'install', 'tailwindcss@^3.4.0', '--save-dev']
];

function check_extension(string $ext): array {
    return [
        'name' => $ext,
        'loaded' => extension_loaded($ext)
    ];
}

function run_cmd(array $cmd, ?string $cwd = null): array {
    global $ALLOWED_COMMANDS;

    // Validate command against whitelist
    $cmd_key = implode('_', $cmd);
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

    // Additional security: ensure cwd is within project root
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

// CLI mode support
if (PHP_SAPI === 'cli') {
    $options = getopt('', ['check', 'create-dirs', 'install-export-deps', 'install-tailwind', 'create-admin:', 'password:', 'password-file:']);
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
            $CONFIG_DIR,
            $ADMIN_UPLOADS_DIR,
            $UPLOADS_DIR,
            $CONTENT_DIR,
            $SESSIONS_DIR,
            $CACHE_DIR,
            $BACKUPS_DIR,
            $UPDATES_DIR,
        ] as $d) {
            $exists = is_dir($d);
            $writable = $exists ? is_writable($d) : is_writable(dirname($d));
            echo "  - {$d}: " . ($exists ? 'exists' : 'missing') . ', ' . ($writable ? 'writable' : 'not writable') . "\n";
        }
        
        // Check Node.js if available
        $node = run_cmd(['which', 'node']);
        $npm = run_cmd(['which', 'npm']);
        if ($node['code'] === 0 && $npm['code'] === 0) {
            echo "Node.js:\n";
            $nodeVersion = run_cmd(['node', '--version']);
            $npmVersion = run_cmd(['npm', '--version']);
            echo "  - node: " . trim($nodeVersion['out']) . "\n";
            echo "  - npm: " . trim($npmVersion['out']) . "\n";
            
            // Check if package.json exists and dependencies
            if (file_exists($projectRoot . '/package.json')) {
                echo "  - package.json: exists\n";
                $packageJson = json_decode(file_get_contents($projectRoot . '/package.json'), true);
                if (isset($packageJson['dependencies'])) {
                    echo "  - dependencies: " . implode(', ', array_keys($packageJson['dependencies'])) . "\n";
                }
                if (isset($packageJson['devDependencies'])) {
                    echo "  - devDependencies: " . implode(', ', array_keys($packageJson['devDependencies'])) . "\n";
                }
            } else {
                echo "  - package.json: missing\n";
            }
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
            echo "Node.js or npm not found in PATH. Please install Node.js and npm first.\n";
            $exitCode = 1;
        } else {
            if (!file_exists($projectRoot . '/package.json')) {
                $init = run_cmd(['npm', 'init', '-y'], $projectRoot);
                echo 'npm init: exit ' . $init['code'] . (trim($init['err']) ? ' (' . strip_tags($init['err']) . ')' : '') . "\n";
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
            echo "Node.js or npm not found in PATH. Please install Node.js and npm first.\n";
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
            echo "Node.js or npm not found in PATH. Please install Node.js and npm first.\n";
            $exitCode = 1;
        } else {
            // Initialize package.json if missing
            if (!file_exists($projectRoot . '/package.json')) {
                $init = run_cmd(['npm', 'init', '-y'], $projectRoot);
                echo 'npm init: exit ' . $init['code'] . (trim($init['err']) ? ' (' . strip_tags($init['err']) . ')' : '') . "\n";
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
            // Read from STDIN without echo if possible
            echo "Enter password for '{$username}': ";
            $password = trim(fgets(STDIN));
        }
        if ($username === '' || $password === '') {
            echo "Username and password are required.\n";
            exit(1);
        }
        if (!preg_match('/^[A-Za-z0-9_\-]{3,32}$/', $username)) {
            echo "Invalid username. Use 3-32 chars [A-Za-z0-9_-].\n";
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

    // Security warning for CLI users
    echo "\n⚠️  SECURITY WARNING: After installation, delete this file to prevent security vulnerabilities!\n";
    echo "   Run: rm install.php\n\n";

    exit($exitCode);
}

$action = $_POST['action'] ?? '';
$resultMessages = [];

// Validate CSRF token for all POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validate_csrf_token()) {
    $resultMessages[] = 'Invalid or expired security token. Please refresh the page and try again.';
    $action = ''; // Prevent action execution
}

if ($action === 'create_dirs') {
    $dirs = [$CONFIG_DIR, $ADMIN_UPLOADS_DIR, $UPLOADS_DIR, $CONTENT_DIR, $SESSIONS_DIR, $CACHE_DIR, $BACKUPS_DIR, $UPDATES_DIR];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                $resultMessages[] = "Created directory: $dir";
            } else {
                $resultMessages[] = "Failed to create directory: $dir";
            }
        } else {
            $resultMessages[] = "Directory exists: $dir";
        }
    }
    // Create default config files if missing by including core config (idempotent)
    $configPath = $projectRoot . '/includes/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
        $resultMessages[] = 'Initialized default configuration files.';
    }
}

if ($action === 'install_export_deps') {
    // Detect node and npm
    $node = run_cmd(['which', 'node']);
    $npm = run_cmd(['which', 'npm']);
    if ($node['code'] !== 0 || $npm['code'] !== 0) {
        $resultMessages[] = 'Node.js or npm not found in PATH. Please install Node.js and npm first.';
    } else {
        // Initialize package.json if missing
        if (!file_exists($projectRoot . '/package.json')) {
            $init = run_cmd(['npm', 'init', '-y'], $projectRoot);
            $resultMessages[] = 'npm init: exit ' . $init['code'] . (trim($init['err']) ? ' (' . htmlspecialchars($init['err']) . ')' : '');
        }
        // Install dependencies used by export.js and other tools
        $install = run_cmd(['npm', 'install', 'fs-extra', 'handlebars', 'marked', '--save'], $projectRoot);
        $resultMessages[] = 'npm install: exit ' . $install['code'];
        if (trim($install['out'])) $resultMessages[] = '<pre class="text-xs whitespace-pre-wrap">' . htmlspecialchars($install['out']) . '</pre>';
        if (trim($install['err'])) $resultMessages[] = '<pre class="text-xs text-red-700 whitespace-pre-wrap">' . htmlspecialchars($install['err']) . '</pre>';
    }
}

if ($action === 'install_dev_deps') {
    // Detect node and npm
    $node = run_cmd(['which', 'node']);
    $npm = run_cmd(['which', 'npm']);
    if ($node['code'] !== 0 || $npm['code'] !== 0) {
        $resultMessages[] = 'Node.js or npm not found in PATH. Please install Node.js and npm first.';
    } else {
        // Install SASS compiler as dev dependency
        $install = run_cmd(['npm', 'install', 'sass', '--save-dev'], $projectRoot);
        $resultMessages[] = 'SASS install: exit ' . $install['code'];
        if (trim($install['out'])) $resultMessages[] = '<pre class="text-xs whitespace-pre-wrap">' . htmlspecialchars($install['out']) . '</pre>';
        if (trim($install['err'])) $resultMessages[] = '<pre class="text-xs text-red-700 whitespace-pre-wrap">' . htmlspecialchars($install['err']) . '</pre>';
    }
}

if ($action === 'install_tailwind') {
    // Detect node and npm
    $node = run_cmd(['which', 'node']);
    $npm = run_cmd(['which', 'npm']);
    if ($node['code'] !== 0 || $npm['code'] !== 0) {
        $resultMessages[] = 'Node.js or npm not found in PATH. Please install Node.js and npm first.';
    } else {
        // Initialize package.json if missing
        if (!file_exists($projectRoot . '/package.json')) {
            $init = run_cmd(['npm', 'init', '-y'], $projectRoot);
            $resultMessages[] = 'npm init: exit ' . $init['code'] . (trim($init['err']) ? ' (' . htmlspecialchars($init['err']) . ')' : '');
        }
        // Install Tailwind CSS as dev dependency
        $install = run_cmd(['npm', 'install', 'tailwindcss@^3.4.0', '--save-dev'], $projectRoot);
        $resultMessages[] = 'Tailwind CSS install: exit ' . $install['code'];
        if (trim($install['out'])) $resultMessages[] = '<pre class="text-xs whitespace-pre-wrap">' . htmlspecialchars($install['out']) . '</pre>';
        if (trim($install['err'])) $resultMessages[] = '<pre class="text-xs text-red-700 whitespace-pre-wrap">' . htmlspecialchars($install['err']) . '</pre>';
    }
}

// Environment checks
$phpVersionOk = version_compare(PHP_VERSION, '8.0.0', '>=');
$extensions = [
    check_extension('curl'),
    check_extension('json'),
    check_extension('mbstring'),
    check_extension('phar'), // preferred for updater
    check_extension('zip'),  // optional fallback
    check_extension('openssl'),
];

// Optimize directory checks by doing them once
$directories = [];
$dirs_to_check = [
    $CONFIG_DIR,
    $ADMIN_UPLOADS_DIR,
    $UPLOADS_DIR,
    $CONTENT_DIR,
    $SESSIONS_DIR,
    $CACHE_DIR,
    $BACKUPS_DIR,
    $UPDATES_DIR,
];

foreach ($dirs_to_check as $dir) {
    $exists = is_dir($dir);
    $writable = $exists ? is_writable($dir) : is_writable(dirname($dir));
    $directories[] = [
        'path' => $dir,
        'exists' => $exists,
        'writable' => $writable
    ];
}

$hasNode = run_cmd(['which', 'node']);
$hasNpm  = run_cmd(['which', 'npm']);

// Admin user state
$usersFile = $CONFIG_DIR . '/users.json';
$existingAdmins = [];
if (file_exists($usersFile)) {
    $usersData = json_decode(file_get_contents($usersFile), true);
    if (is_array($usersData)) {
        foreach ($usersData as $u) {
            if (($u['role'] ?? '') === 'administrator') {
                $existingAdmins[] = $u['username'] ?? '';
            }
        }
    }
}

if ($action === 'create_admin') {
    // Rate limiting for admin creation
    if (!check_rate_limit('create_admin', 3, 300)) {
        $resultMessages[] = 'Too many attempts to create admin account. Please wait 5 minutes before trying again.';
    } else {
        $username = trim($_POST['admin_user'] ?? '');
        $password = (string)($_POST['admin_pass'] ?? '');
        $confirm  = (string)($_POST['admin_pass_confirm'] ?? '');

        if ($username === '' || $password === '' || $confirm === '') {
            $resultMessages[] = 'All fields are required to create the admin account.';
        } elseif ($password !== $confirm) {
            $resultMessages[] = 'Passwords do not match.';
        } elseif (!preg_match('/^[A-Za-z0-9_\-]{3,32}$/', $username)) {
            $resultMessages[] = 'Username must be 3-32 characters and contain only letters, numbers, underscores, or dashes.';
        } elseif (strlen($password) < 8) {
            $resultMessages[] = 'Password must be at least 8 characters long.';
        } else {
            $users = [];
            if (file_exists($usersFile)) {
                $decoded = json_decode(file_get_contents($usersFile), true);
                if (is_array($decoded)) $users = $decoded;
            }
            // Prevent duplicates
            foreach ($users as $u) {
                if (($u['username'] ?? '') === $username) {
                    $resultMessages[] = 'A user with that username already exists.';
                    $username = '';
                    break;
                }
            }
            if ($username !== '') {
                $users[] = [
                    'id' => $username,
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => 'administrator',
                    'permissions' => [],
                    'created_at' => date('Y-m-d H:i:s')
                ];
                if (!is_dir($CONFIG_DIR)) {
                    if (!mkdir($CONFIG_DIR, 0755, true)) {
                        $resultMessages[] = 'Failed to create config directory.';
                        $username = '';
                    }
                }
                if ($username !== '' && file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT)) !== false) {
                    $resultMessages[] = 'Admin account created successfully.';
                    $existingAdmins[] = $username;
                } else {
                    $resultMessages[] = 'Failed to write users.json.';
                }
            }
        }
    }
}

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FearlessCMS Installer</title>
<link href="/public/css/output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="max-w-4xl mx-auto my-10 bg-white shadow rounded p-6">
        <h1 class="text-2xl font-bold mb-6">FearlessCMS Installer</h1>

        <?php if (!empty($resultMessages)): ?>
            <div class="mb-6 space-y-2">
                <?php foreach ($resultMessages as $msg): ?>
                    <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-2 rounded"><?php echo $msg; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="space-y-8">
            <section>
                <h2 class="text-xl font-semibold mb-3">Environment</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded">
                        <div class="text-sm text-gray-500">PHP Version</div>
                        <div class="mt-1 font-mono"><?php echo htmlspecialchars(PHP_VERSION); ?></div>
                        <div class="mt-1 <?php echo $phpVersionOk ? 'text-green-700' : 'text-red-700'; ?>">
                            <?php echo $phpVersionOk ? 'OK (>= 8.0)' : 'Requires PHP 8.0+'; ?>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded">
                        <div class="text-sm text-gray-500">Project Root</div>
                        <div class="mt-1 font-mono break-all"><?php echo htmlspecialchars($projectRoot); ?></div>
                    </div>
                </div>

                <div class="mt-4">
                    <h3 class="font-medium mb-2">PHP Extensions</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        <?php foreach ($extensions as $ext): ?>
                            <div class="px-3 py-2 rounded <?php echo $ext['loaded'] ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'; ?>">
                                <?php echo htmlspecialchars($ext['name']); ?>: <?php echo $ext['loaded'] ? 'Loaded' : 'Missing'; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-xs text-gray-600 mt-2">Updater prefers <code>phar</code> (tar.gz). <code>zip</code> is optional fallback.</p>
                </div>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-3">Directories & Permissions</h2>
                <div class="space-y-2">
                    <?php foreach ($directories as $d): ?>
                        <div class="flex items-center justify-between px-3 py-2 bg-gray-50 rounded border">
                            <div class="font-mono text-sm break-all"><?php echo htmlspecialchars($d['path']); ?></div>
                            <div class="text-sm">
                                <span class="mr-3 <?php echo $d['exists'] ? 'text-green-700' : 'text-red-700'; ?>"><?php echo $d['exists'] ? 'exists' : 'missing'; ?></span>
                                <span class="<?php echo $d['writable'] ? 'text-green-700' : 'text-red-700'; ?>"><?php echo $d['writable'] ? 'writable' : 'not writable'; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form method="POST" class="mt-3">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="action" value="create_dirs">
                    <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Create/Verify Directories</button>
                </form>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-3">Export Tool & Development Dependencies (Optional)</h2>
                <p class="text-sm text-gray-600 mb-2">The static site exporter (<code>export.js</code>) and other tools use Node.js packages: <code>fs-extra</code>, <code>handlebars</code>, <code>marked</code>.</p>
                <p class="text-xs text-gray-500 mb-3">These dependencies enable static site generation, template processing, and content conversion capabilities.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                    <div class="bg-gray-50 p-4 rounded">
                        <div class="text-sm text-gray-500">node</div>
                        <div class="font-mono"><?php echo htmlspecialchars(trim($hasNode['out']) ?: 'not found'); ?></div>
                        <?php if (trim($hasNode['out'])): ?>
                            <?php 
                            // Get actual Node.js version by running node --version
                            $nodeVersionCmd = run_cmd(['node', '--version']);
                            $nodeVersion = trim($nodeVersionCmd['out']);
                            $nodeVersionOk = version_compare($nodeVersion, '14.14.0', '>=');
                            ?>
                            <div class="mt-1 text-xs <?php echo $nodeVersionOk ? 'text-green-700' : 'text-red-700'; ?>">
                                <?php echo $nodeVersionOk ? 'OK (>= 14.14)' : 'Requires Node.js 14.14+'; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="bg-gray-50 p-4 rounded">
                        <div class="text-sm text-gray-500">npm</div>
                        <div class="font-mono"><?php echo htmlspecialchars(trim($hasNpm['out']) ?: 'not found'); ?></div>
                    </div>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="action" value="install_export_deps">
                    <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Install Node Dependencies</button>
                </form>
                <p class="text-xs text-gray-600 mt-2">If Node.js is not available, run locally:<br>
                <code>npm install fs-extra handlebars marked</code></p>
                
                <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded">
                    <h4 class="font-medium text-blue-800 mb-2">Dependency Details:</h4>
                    <ul class="text-xs text-blue-700 space-y-1">
                        <li><strong>fs-extra:</strong> Enhanced file system operations for export tool</li>
                        <li><strong>handlebars:</strong> Template engine for dynamic content generation</li>
                        <li><strong>marked:</strong> Markdown parser (for legacy content support)</li>
                    </ul>
                    <p class="text-xs text-blue-600 mt-2">These packages enable the export tool to process templates, handle file operations, and convert content formats.</p>
                </div>
                
                <div class="mt-4">
                    <h3 class="font-medium mb-2">Optional Development Dependencies</h3>
                    <p class="text-sm text-gray-600 mb-2">For theme development with SASS/SCSS support:</p>
                    <form method="POST" class="mb-3">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="install_dev_deps">
                        <button class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">Install SASS Compiler</button>
                    </form>
                    <p class="text-xs text-gray-600 mb-3">Installs <code>sass</code> as a development dependency for compiling SASS/SCSS files.</p>
                    
                    <p class="text-sm text-gray-600 mb-2">For modern CSS framework with Tailwind CSS:</p>
                    <form method="POST" class="mb-3">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="install_tailwind">
                        <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Install Tailwind CSS</button>
                    </form>
                    <p class="text-xs text-gray-600 mb-3">Installs <code>tailwindcss@^3.4.0</code> as a development dependency for utility-first CSS framework.</p>
                
                <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded">
                    <h4 class="font-medium text-yellow-800 mb-2">Installation Process:</h4>
                    <ul class="text-xs text-yellow-700 space-y-1">
                        <li>Creates <code>package.json</code> if it doesn't exist</li>
                        <li>Installs production dependencies to <code>node_modules/</code></li>
                        <li>Updates <code>package-lock.json</code> with exact versions</li>
                        <li>Dependencies are available via <code>npx</code> or <code>node_modules/.bin/</code></li>
                    </ul>
                </div>
                
                <div class="mt-3 p-3 bg-gray-50 border border-gray-200 rounded">
                    <h4 class="font-medium text-gray-800 mb-2">Common Development Commands:</h4>
                    <ul class="text-xs text-gray-700 space-y-1">
                        <li><code>npx sass themes/your-theme/assets/style.scss themes/your-theme/assets/style.css</code> - Compile SASS to CSS</li>
                        <li><code>npx sass --watch themes/your-theme/assets/</code> - Watch and auto-compile SASS files</li>
                        <li><code>node export.js</code> - Generate static site export</li>
                        <li><code>npm run build</code> - Build project (if scripts defined in package.json)</li>
                    </ul>
                    <p class="text-xs text-gray-600 mt-2">These commands help with theme development, content export, and project building workflows.</p>
                
                <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded">
                    <h4 class="font-medium text-red-800 mb-2">Troubleshooting:</h4>
                    <ul class="text-xs text-red-700 space-y-1">
                        <li><strong>Permission denied:</strong> Ensure npm has write access to project directory</li>
                        <li><strong>Network errors:</strong> Check internet connection and npm registry access</li>
                        <li><strong>Version conflicts:</strong> Delete <code>node_modules/</code> and <code>package-lock.json</code>, then reinstall</li>
                        <li><strong>Node.js not found:</strong> Install Node.js from <a href="https://nodejs.org" class="underline">nodejs.org</a></li>
                    </ul>
                </div>
                </div>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-3">Create Admin Account</h2>
                <?php if (!empty($existingAdmins)): ?>
                    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded mb-3">
                        Administrator account exists: <span class="font-mono"><?php echo htmlspecialchars(implode(', ', $existingAdmins)); ?></span>
                    </div>
                <?php else: ?>
                    <form method="POST" class="space-y-3 max-w-md">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="create_admin">
                        <div>
                            <label class="block mb-1">Username</label>
                            <input name="admin_user" class="w-full px-3 py-2 border rounded" required pattern="[A-Za-z0-9_\-]{3,32}" title="3-32 characters, letters, numbers, underscores, or dashes only">
                        </div>
                        <div>
                            <label class="block mb-1">Password</label>
                            <input type="password" name="admin_pass" class="w-full px-3 py-2 border rounded" required minlength="8" title="Minimum 8 characters">
                        </div>
                        <div>
                            <label class="block mb-1">Confirm Password</label>
                            <input type="password" name="admin_pass_confirm" class="w-full px-3 py-2 border rounded" required minlength="8" title="Minimum 8 characters">
                        </div>
                        <button class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">Create Admin</button>
                    </form>
                <?php endif; ?>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-3">Next Steps</h2>
                <ul class="list-disc ml-6 text-sm text-gray-700 space-y-1">
                    <li>Visit <code>/admin/</code> to log in and configure your site.</li>
                    <li>Use the Updates section in Mission Control to keep FearlessCMS up to date.</li>
                    <li>Use the Store to browse and install plugins/themes (if enabled).</li>
                    <li>Run <code>node export.js</code> to generate a static version of your site (requires Node.js dependencies).</li>
                    <li>Use <code>npx sass</code> to compile SASS/SCSS files for custom themes (requires SASS dependency).</li>
                </ul>
            </section>

            <section class="bg-blue-50 border border-blue-200 rounded p-4">
                <h2 class="text-xl font-semibold mb-3 text-blue-800">🖥️ Command Line Interface</h2>
                <div class="text-blue-700 space-y-2">
                    <p><strong>CLI Usage:</strong> This installer also supports command-line operation for automation and server environments.</p>
                    <div class="bg-white border border-blue-300 rounded p-3 mt-3">
                        <p class="font-mono text-sm text-blue-800">Available CLI commands:</p>
                        <ul class="list-disc ml-6 text-sm mt-2 space-y-1">
                            <li><code>php install.php --check</code> - Check system requirements</li>
                            <li><code>php install.php --create-dirs</code> - Create required directories</li>
                            <li><code>php install.php --install-export-deps</code> - Install Node.js dependencies</li>
                            <li><code>php install.php --install-dev-deps</code> - Install development dependencies</li>
                            <li><code>php install.php --install-tailwind</code> - Install Tailwind CSS</li>
                            <li><code>php install.php --create-admin=username --password=password</code> - Create admin user</li>
                        </ul>
                    </div>
                    <p class="text-sm mt-3"><strong>Example:</strong> <code>php install.php --check --create-dirs --install-export-deps</code></p>
                </div>
            </section>

            <section class="bg-red-50 border border-red-200 rounded p-4">
                <h2 class="text-xl font-semibold mb-3 text-red-800">⚠️ Security Warning</h2>
                <div class="text-red-700 space-y-2">
                    <p><strong>IMPORTANT:</strong> After completing the installation, you must delete this file for security reasons.</p>
                    <p>The installer file contains administrative functions that could be exploited by attackers if left accessible.</p>
                    <div class="bg-white border border-red-300 rounded p-3 mt-3">
                        <p class="font-mono text-sm text-red-800">Delete this file using one of these methods:</p>
                        <ul class="list-disc ml-6 text-sm mt-2 space-y-1">
                            <li><strong>SSH/Command Line:</strong> <code>rm install.php</code></li>
                            <li><strong>File Manager:</strong> Delete <code>install.php</code> from your web directory</li>
                            <li><strong>FTP:</strong> Remove <code>install.php</code> from your server</li>
                        </ul>
                    </div>
                    <p class="text-sm mt-3"><strong>Note:</strong> Once deleted, you cannot re-run the installer. If you need to reinstall, you'll need to upload the installer file again.</p>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
