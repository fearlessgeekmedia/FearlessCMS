<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Minimal bootstrap for filesystem paths
$projectRoot = __DIR__;
$CONFIG_DIR = getenv('FCMS_CONFIG_DIR') ?: ($projectRoot . '/config');
$ADMIN_UPLOADS_DIR = $projectRoot . '/admin/uploads';
$UPLOADS_DIR = $projectRoot . '/uploads';
$CONTENT_DIR = $projectRoot . '/content';
$SESSIONS_DIR = $projectRoot . '/sessions';
$CACHE_DIR = $projectRoot . '/cache';
$BACKUPS_DIR = $projectRoot . '/backups';
$UPDATES_DIR = $projectRoot . '/.fcms_updates';

function check_extension(string $ext): array {
    return [
        'name' => $ext,
        'loaded' => extension_loaded($ext)
    ];
}

function run_cmd(array $cmd, ?string $cwd = null): array {
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
    $options = getopt('', ['check', 'create-dirs', 'install-export-deps', 'create-admin:', 'password:', 'password-file:']);
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
    }

    if (isset($options['create-dirs'])) {
        $dirs = [$CONFIG_DIR, $ADMIN_UPLOADS_DIR, $UPLOADS_DIR, $CONTENT_DIR, $SESSIONS_DIR, $CACHE_DIR, $BACKUPS_DIR, $UPDATES_DIR];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                if (@mkdir($dir, 0755, true)) {
                    echo "Created directory: {$dir}\n";
                } else {
                    echo "Failed to create directory: {$dir}\n";
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
        } else {
            if (!file_exists($projectRoot . '/package.json')) {
                $init = run_cmd(['npm', 'init', '-y'], $projectRoot);
                echo 'npm init: exit ' . $init['code'] . (trim($init['err']) ? ' (' . strip_tags($init['err']) . ')' : '') . "\n";
            }
            $install = run_cmd(['npm', 'install', 'fs-extra', 'handlebars', 'marked', '--save'], $projectRoot);
            echo 'npm install: exit ' . $install['code'] . "\n";
            if (trim($install['out'])) echo strip_tags($install['out']) . "\n";
            if (trim($install['err'])) echo strip_tags($install['err']) . "\n";
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
            $decoded = json_decode(@file_get_contents($usersFile), true);
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
        if (!is_dir($CONFIG_DIR)) @mkdir($CONFIG_DIR, 0755, true);
        if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT)) !== false) {
            echo "Admin account created: {$username}\n";
        } else {
            echo "Failed to write users.json\n";
            exit(1);
        }
    }

    if (empty($options)) {
        echo "Usage: php install.php [--check] [--create-dirs] [--install-export-deps] [--create-admin=<username> --password=<pwd>|--password-file=<file>]\n";
    }
    exit($exitCode);
}

$action = $_POST['action'] ?? '';
$resultMessages = [];

if ($action === 'create_dirs') {
    $dirs = [$CONFIG_DIR, $ADMIN_UPLOADS_DIR, $UPLOADS_DIR, $CONTENT_DIR, $SESSIONS_DIR, $CACHE_DIR, $BACKUPS_DIR, $UPDATES_DIR];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            if (@mkdir($dir, 0755, true)) {
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
        // Install dependencies used by export.js
        $install = run_cmd(['npm', 'install', 'fs-extra', 'handlebars', 'marked', '--save'], $projectRoot);
        $resultMessages[] = 'npm install: exit ' . $install['code'];
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

$directories = [
    ['path' => $CONFIG_DIR,        'exists' => is_dir($CONFIG_DIR),        'writable' => is_dir($CONFIG_DIR) ? is_writable($CONFIG_DIR) : is_writable(dirname($CONFIG_DIR))],
    ['path' => $ADMIN_UPLOADS_DIR, 'exists' => is_dir($ADMIN_UPLOADS_DIR), 'writable' => is_dir($ADMIN_UPLOADS_DIR) ? is_writable($ADMIN_UPLOADS_DIR) : is_writable(dirname($ADMIN_UPLOADS_DIR))],
    ['path' => $UPLOADS_DIR,       'exists' => is_dir($UPLOADS_DIR),       'writable' => is_dir($UPLOADS_DIR) ? is_writable($UPLOADS_DIR) : is_writable(dirname($UPLOADS_DIR))],
    ['path' => $CONTENT_DIR,       'exists' => is_dir($CONTENT_DIR),       'writable' => is_dir($CONTENT_DIR) ? is_writable($CONTENT_DIR) : is_writable(dirname($CONTENT_DIR))],
    ['path' => $SESSIONS_DIR,      'exists' => is_dir($SESSIONS_DIR),      'writable' => is_dir($SESSIONS_DIR) ? is_writable($SESSIONS_DIR) : is_writable(dirname($SESSIONS_DIR))],
    ['path' => $CACHE_DIR,         'exists' => is_dir($CACHE_DIR),         'writable' => is_dir($CACHE_DIR) ? is_writable($CACHE_DIR) : is_writable(dirname($CACHE_DIR))],
    ['path' => $BACKUPS_DIR,       'exists' => is_dir($BACKUPS_DIR),       'writable' => is_dir($BACKUPS_DIR) ? is_writable($BACKUPS_DIR) : is_writable(dirname($BACKUPS_DIR))],
    ['path' => $UPDATES_DIR,       'exists' => is_dir($UPDATES_DIR),       'writable' => is_dir($UPDATES_DIR) ? is_writable($UPDATES_DIR) : is_writable(dirname($UPDATES_DIR))],
];

$hasNode = run_cmd(['which', 'node']);
$hasNpm  = run_cmd(['which', 'npm']);

// Admin user state
$usersFile = $CONFIG_DIR . '/users.json';
$existingAdmins = [];
if (file_exists($usersFile)) {
    $usersData = json_decode(@file_get_contents($usersFile), true);
    if (is_array($usersData)) {
        foreach ($usersData as $u) {
            if (($u['role'] ?? '') === 'administrator') {
                $existingAdmins[] = $u['username'] ?? '';
            }
        }
    }
}

if ($action === 'create_admin') {
    $username = trim($_POST['admin_user'] ?? '');
    $password = (string)($_POST['admin_pass'] ?? '');
    $confirm  = (string)($_POST['admin_pass_confirm'] ?? '');

    if ($username === '' || $password === '' || $confirm === '') {
        $resultMessages[] = 'All fields are required to create the admin account.';
    } elseif ($password !== $confirm) {
        $resultMessages[] = 'Passwords do not match.';
    } elseif (!preg_match('/^[A-Za-z0-9_\-]{3,32}$/', $username)) {
        $resultMessages[] = 'Username must be 3-32 characters and contain only letters, numbers, underscores, or dashes.';
    } else {
        $users = [];
        if (file_exists($usersFile)) {
            $decoded = json_decode(@file_get_contents($usersFile), true);
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
                'permissions' => []
            ];
            if (!is_dir($CONFIG_DIR)) @mkdir($CONFIG_DIR, 0755, true);
            if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT)) !== false) {
                $resultMessages[] = 'Admin account created successfully.';
                $existingAdmins[] = $username;
            } else {
                $resultMessages[] = 'Failed to write users.json.';
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
<script src="https://cdn.tailwindcss.com"></script>
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
                    <input type="hidden" name="action" value="create_dirs">
                    <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Create/Verify Directories</button>
                </form>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-3">Export Tool (Optional)</h2>
                <p class="text-sm text-gray-600 mb-2">The static site exporter (<code>export.js</code>) uses Node.js packages: <code>fs-extra</code>, <code>handlebars</code>, <code>marked</code>.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                    <div class="bg-gray-50 p-4 rounded">
                        <div class="text-sm text-gray-500">node</div>
                        <div class="font-mono"><?php echo htmlspecialchars(trim($hasNode['out']) ?: 'not found'); ?></div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded">
                        <div class="text-sm text-gray-500">npm</div>
                        <div class="font-mono"><?php echo htmlspecialchars(trim($hasNpm['out']) ?: 'not found'); ?></div>
                    </div>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="install_export_deps">
                    <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Install Node Dependencies</button>
                </form>
                <p class="text-xs text-gray-600 mt-2">If Node.js is not available, run locally:<br>
                <code>npm install fs-extra handlebars marked</code></p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-3">Create Admin Account</h2>
                <?php if (!empty($existingAdmins)): ?>
                    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded mb-3">
                        Administrator account exists: <span class="font-mono"><?php echo htmlspecialchars(implode(', ', $existingAdmins)); ?></span>
                    </div>
                <?php else: ?>
                    <form method="POST" class="space-y-3 max-w-md">
                        <input type="hidden" name="action" value="create_admin">
                        <div>
                            <label class="block mb-1">Username</label>
                            <input name="admin_user" class="w-full px-3 py-2 border rounded" required pattern="[A-Za-z0-9_\-]{3,32}">
                        </div>
                        <div>
                            <label class="block mb-1">Password</label>
                            <input type="password" name="admin_pass" class="w-full px-3 py-2 border rounded" required>
                        </div>
                        <div>
                            <label class="block mb-1">Confirm Password</label>
                            <input type="password" name="admin_pass_confirm" class="w-full px-3 py-2 border rounded" required>
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
                </ul>
            </section>
        </div>
    </div>
</body>
</html>
