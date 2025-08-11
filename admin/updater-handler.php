<?php
// FearlessCMS Updater Handler
// Registers an "Updates" admin section and provides update functionality via GitHub ZIP download

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/plugins.php';

// Register admin section
fcms_register_admin_section('updates', [
    'label' => 'Updates',
    'menu_order' => 35,
    'render_callback' => 'fcms_render_updates_admin_page'
]);

/**
 * Get the current installed version string
 */
function fcms_get_current_version(): string {
    $versionFile = PROJECT_ROOT . '/version.php';
    if (!file_exists($versionFile)) {
        return '0.0.0';
    }

    // Load constant from file in isolated scope
    $defineVersion = static function ($file) {
        require_once $file;
        return defined('APP_VERSION') ? APP_VERSION : '0.0.0';
    };

    return (string) $defineVersion($versionFile);
}

/**
 * Get update configuration (repo and branch)
 */
function fcms_get_update_config(): array {
    $configFile = CONFIG_DIR . '/config.json';
    $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];

    $repo = $config['update_repo_url'] ?? 'https://github.com/fearlessgeekmedia/FearlessCMS.git';
    $branch = $config['update_branch'] ?? 'main';

    return [
        'repo' => $repo,
        'branch' => $branch,
    ];
}

/**
 * Fetch the latest available version from the configured repo/branch by reading version.php
 */
function fcms_fetch_latest_version(string $repoUrl, string $branch): ?string {
    // Convert to raw URL for version.php
    // Example: https://raw.githubusercontent.com/<org>/<repo>/<branch>/version.php
    $raw = $repoUrl;
    if (strpos($repoUrl, 'github.com') !== false) {
        $raw = preg_replace('#https?://github\\.com/([^/]+)/([^/]+)\\.git#', 'https://raw.githubusercontent.com/$1/$2', $repoUrl);
        if ($raw === null || $raw === $repoUrl) {
            // Try a more permissive replacement if .git not present
            $raw = preg_replace('#https?://github\\.com/([^/]+)/([^/]+)#', 'https://raw.githubusercontent.com/$1/$2', $repoUrl);
        }
        $raw = rtrim($raw, '/') . '/' . $branch . '/version.php';
    } else if (strpos($repoUrl, 'raw.githubusercontent.com') !== false) {
        // Assume it's already a raw base
        $raw = rtrim($repoUrl, '/') . '/' . $branch . '/version.php';
    } else {
        // Fallback: assume direct URL to version.php
        $raw = rtrim($repoUrl, '/') . '/version.php';
    }

    $content = fcms_http_get($raw);
    if ($content === false || $content === null) {
        return null;
    }

    if (preg_match("/define\(\s*'APP_VERSION'\s*,\s*'([^']+)'\s*\)\s*;/", $content, $m)) {
        return trim($m[1]);
    }

    return null;
}

/**
 * Simple HTTP GET using cURL with safe defaults and fallback to file_get_contents
 */
function fcms_http_get(string $url): string|false {
    // Try cURL first
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FearlessCMS-Updater/1.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body !== false && $httpCode >= 200 && $httpCode < 300) {
            return $body;
        }
    }

    // Fallback
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: FearlessCMS-Updater/1.0',
                'Accept: */*'
            ],
            'ignore_errors' => true,
            'timeout' => 30,
            'protocol_version' => '1.1'
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);
    $body = @file_get_contents($url, false, $context);
    return $body === false ? false : $body;
}

/**
 * Compare version strings that may include non-semver suffixes like 0.0.2-16
 * Returns 1 if a > b, -1 if a < b, 0 if equal
 */
function fcms_compare_versions(string $a, string $b): int {
    if ($a === $b) return 0;

    // Split into numeric chunks
    $toParts = static function (string $v): array {
        // Replace non-digits with dot, then split
        $clean = preg_replace('/[^0-9]+/', '.', $v);
        $clean = trim($clean ?? '', '.');
        if ($clean === '') return [0];
        return array_map('intval', explode('.', $clean));
    };

    $pa = $toParts($a);
    $pb = $toParts($b);
    $len = max(count($pa), count($pb));
    for ($i = 0; $i < $len; $i++) {
        $va = $pa[$i] ?? 0;
        $vb = $pb[$i] ?? 0;
        if ($va > $vb) return 1;
        if ($va < $vb) return -1;
    }
    return 0;
}

/**
 * Download a repository archive (prefers .tar.gz to avoid php-zip dependency)
 * Returns array [archivePath, type] where type is 'tar.gz' or 'zip'.
 */
function fcms_download_update_archive(string $repoUrl, string $branch, string $targetDir): array|false {
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

    $base = preg_replace('#\\.git$#', '', $repoUrl);
    $useTarGz = true; // prefer tar.gz to avoid ZipArchive
    $archiveUrl = $base;
    $type = 'tar.gz';
    if (strpos($repoUrl, 'github.com') !== false) {
        // GitHub tarball endpoint
        $archiveUrl = rtrim($base, '/') . '/archive/refs/heads/' . rawurlencode($branch) . '.tar.gz';
    } else {
        // Unknown provider: try as-is; fallback to .zip if requested
        $archiveUrl = $repoUrl;
    }

    $targetPath = rtrim($targetDir, '/').'/update.tar.gz';

    // Download
    if (function_exists('curl_init')) {
        $fp = fopen($targetPath, 'w');
        if ($fp === false) return false;
        $ch = curl_init($archiveUrl);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FearlessCMS-Updater/1.0');
        $ok = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        if ($ok === false || $http < 200 || $http >= 300) {
            @unlink($targetPath);
            // Fallback: try ZIP if tar.gz failed
            $zipUrl = (strpos($repoUrl, 'github.com') !== false)
                ? (rtrim($base, '/') . '/archive/refs/heads/' . rawurlencode($branch) . '.zip')
                : $repoUrl;
            $zipPath = rtrim($targetDir, '/').'/update.zip';
            $fp2 = fopen($zipPath, 'w');
            if ($fp2 === false) return false;
            $ch2 = curl_init($zipUrl);
            curl_setopt($ch2, CURLOPT_FILE, $fp2);
            curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch2, CURLOPT_USERAGENT, 'FearlessCMS-Updater/1.0');
            $ok2 = curl_exec($ch2);
            $http2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);
            fclose($fp2);
            if ($ok2 === false || $http2 < 200 || $http2 >= 300) {
                @unlink($zipPath);
                return false;
            }
            return [$zipPath, 'zip'];
        }
        return [$targetPath, $useTarGz ? 'tar.gz' : 'zip'];
    }

    // Fallback download without cURL
    $data = fcms_http_get($archiveUrl);
    if ($data === false) {
        // try zip fallback
        $zipUrl = (strpos($repoUrl, 'github.com') !== false)
            ? (rtrim($base, '/') . '/archive/refs/heads/' . rawurlencode($branch) . '.zip')
            : $repoUrl;
        $zipData = fcms_http_get($zipUrl);
        if ($zipData === false) return false;
        $zipPath = rtrim($targetDir, '/').'/update.zip';
        if (file_put_contents($zipPath, $zipData) === false) return false;
        return [$zipPath, 'zip'];
    }
    if (file_put_contents($targetPath, $data) === false) return false;
    return [$targetPath, 'tar.gz'];
}

/**
 * Extract downloaded archive (tar.gz preferred; zip if available)
 */
function fcms_extract_archive(string $archivePath, string $type, string $destDir): bool {
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
    if ($type === 'tar.gz') {
        if (!class_exists('PharData')) {
            return false;
        }
        try {
            $phar = new PharData($archivePath);
            // Decompress to .tar
            $tarPath = preg_replace('/\.gz$/', '', $archivePath);
            if (!file_exists($tarPath)) {
                $phar->decompress();
            }
            $tar = new PharData($tarPath);
            $tar->extractTo($destDir, null, true);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    // ZIP fallback
    if ($type === 'zip') {
        if (!class_exists('ZipArchive')) return false;
        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) return false;
        $ok = $zip->extractTo($destDir);
        $zip->close();
        return (bool) $ok;
    }
    return false;
}

/**
 * Recursively copy directory contents, skipping excluded relative paths
 */
function fcms_recursive_copy(string $src, string $dst, array $excludeRelative): bool {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $rel = trim(str_replace($src, '', $item->getPathname()), DIRECTORY_SEPARATOR);
        $rel = $rel === '' ? '' : str_replace('\\', '/', $rel);

        foreach ($excludeRelative as $ex) {
            if ($rel === '' && rtrim($ex, '/') === '') continue;
            if ($rel === '' && $ex !== '') continue;
            // If the relative path begins with an excluded path, skip
            if ($ex !== '' && str_starts_with($rel, rtrim($ex, '/'))) {
                continue 2;
            }
        }

        $target = $dst . DIRECTORY_SEPARATOR . $rel;
        if ($item->isDir()) {
            if (!is_dir($target)) {
                if (!mkdir($target, 0755, true) && !is_dir($target)) return false;
            }
        } else {
            if (!is_dir(dirname($target))) {
                if (!mkdir(dirname($target), 0755, true)) return false;
            }
            if (!copy($item->getPathname(), $target)) return false;
        }
    }
    return true;
}

/**
 * Create a backup ZIP of current installation (core files only)
 */
function fcms_create_backup(string $backupZipPath): bool {
    if (!class_exists('ZipArchive')) return false;

    $excludeTop = [
        'content',
        'config',
        'uploads',
        'admin/uploads',
        'sessions',
        'cache',
        '.git'
    ];

    $zip = new ZipArchive();
    if ($zip->open($backupZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }

    $root = rtrim(PROJECT_ROOT, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        $path = $file->getPathname();
        $rel = str_replace($root, '', $path);
        $relUnix = str_replace('\\', '/', $rel);

        // Exclude directories/files
        $skip = false;
        foreach ($excludeTop as $ex) {
            if ($ex === '') continue;
            if (str_starts_with($relUnix, rtrim($ex, '/') . '/')) { $skip = true; break; }
            if ($relUnix === $ex) { $skip = true; break; }
        }
        if ($skip) continue;

        if ($file->isDir()) {
            $zip->addEmptyDir($relUnix);
        } else {
            $zip->addFile($path, $relUnix);
        }
    }

    $zip->close();
    return true;
}

/**
 * Perform the update process
 */
function fcms_perform_update(string $branch, bool $doBackup, array &$error, bool $dryRun = false, ?array &$report = null): bool {
    $cfg = fcms_get_update_config();
    $repo = $cfg['repo'];

    // Prepare temp paths
    $workDir = PROJECT_ROOT . '/.fcms_updates';
    $extractDir = $workDir . '/extracted';
    if (!is_dir($workDir)) mkdir($workDir, 0755, true);
    if (is_dir($extractDir)) {
        // Clean up previous
        fcms_rrmdir($extractDir);
    }

    if ($doBackup && !$dryRun) {
        $backupDir = PROJECT_ROOT . '/backups';
        if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
        $backupZip = $backupDir . '/fcms_backup_' . date('Ymd_His') . '.zip';
        if (!fcms_create_backup($backupZip)) {
            $error[] = 'Failed to create backup ZIP.';
            return false;
        }
    }

    $download = fcms_download_update_archive($repo, $branch, $workDir);
    if ($download === false) {
        $error[] = 'Failed to download update archive.';
        return false;
    }
    [$archivePath, $type] = $download;

    if (!fcms_extract_archive($archivePath, $type, $extractDir)) {
        $error[] = 'Failed to extract update archive.';
        return false;
    }

    // Find top-level extracted directory (GitHub adds a root folder)
    $entries = array_values(array_filter(scandir($extractDir), function ($n) use ($extractDir) {
        return $n !== '.' && $n !== '..' && is_dir($extractDir . '/' . $n);
    }));
    if (empty($entries)) {
        $error[] = 'Extracted ZIP did not contain expected folder.';
        return false;
    }
    $srcRoot = $extractDir . '/' . $entries[0];

    // Exclude user data and environment-specific directories
    $exclude = [
        'content',
        'config',
        'uploads',
        'admin/uploads',
        'sessions',
        'cache',
        '.git'
    ];

    if ($dryRun) {
        // Build report of changes without copying
        $changed = [];
        $created = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcRoot, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $item) {
            $rel = trim(str_replace($srcRoot, '', $item->getPathname()), DIRECTORY_SEPARATOR);
            $rel = $rel === '' ? '' : str_replace('\\', '/', $rel);
            // Exclude
            $skip = false;
            foreach ($exclude as $ex) {
                if ($ex !== '' && str_starts_with($rel, rtrim($ex, '/'))) { $skip = true; break; }
            }
            if ($skip) continue;
            if ($item->isDir()) continue;
            $target = PROJECT_ROOT . DIRECTORY_SEPARATOR . $rel;
            if (!file_exists($target)) {
                $created[] = $rel;
            } else {
                $srcHash = @md5_file($item->getPathname()) ?: null;
                $dstHash = @md5_file($target) ?: null;
                if ($srcHash !== $dstHash) {
                    $changed[] = $rel;
                }
            }
        }
        $report = [
            'created' => $created,
            'changed' => $changed,
        ];
        // Clean up extracted files only (keep archive for debugging)
        fcms_rrmdir($extractDir);
        return true;
    }

    if (!fcms_recursive_copy($srcRoot, PROJECT_ROOT, $exclude)) {
        $error[] = 'Failed to copy updated files.';
        return false;
    }

    // Clean up
    @unlink($archivePath);
    fcms_rrmdir($extractDir);

    return true;
}

/**
 * Recursively remove directory
 */
function fcms_rrmdir(string $dir): void {
    if (!is_dir($dir)) return;
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($ri as $file) {
        $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
    }
    @rmdir($dir);
}

/**
 * Render the Updates admin page and handle actions
 */
function fcms_render_updates_admin_page() {
    if (!isLoggedIn()) {
        echo '<p class="text-red-600">You must be logged in.</p>';
        return;
    }

    // Permission: only users with 'manage_updates' can access
    $username = $_SESSION['username'] ?? '';
    if (!fcms_check_permission($username, 'manage_updates')) {
        echo '<div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded mb-4">You do not have permission to manage updates.</div>';
        return;
    }

    $cfg = fcms_get_update_config();
    $current = fcms_get_current_version();
    $latest = fcms_fetch_latest_version($cfg['repo'], $cfg['branch']);
    $hasUpdate = $latest ? (fcms_compare_versions($latest, $current) > 0) : false;

    // Handle POST actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sub = $_POST['subaction'] ?? '';
        if ($sub === 'perform_update') {
            $branch = $_POST['branch'] ?? $cfg['branch'];
            $doBackup = isset($_POST['create_backup']);
            $dryRun = isset($_POST['dry_run']);
            $errors = [];
            $report = [];
            $ok = fcms_perform_update($branch, $doBackup, $errors, $dryRun, $report);
            if ($ok) {
                if ($dryRun) {
                    echo '<div class="bg-blue-100 border border-blue-400 text-blue-800 px-4 py-3 rounded mb-4">Dry run completed. No files were changed.</div>';
                } else {
                    echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">Update completed successfully. Please refresh the page.</div>';
                }
                // Recompute versions after update
                $current = fcms_get_current_version();
                $latest = fcms_fetch_latest_version($cfg['repo'], $branch);
                $hasUpdate = $latest ? (fcms_compare_versions($latest, $current) > 0) : false;
                if ($dryRun) {
                    echo '<div class="mt-4">'
                        . '<h4 class="font-semibold mb-2">Planned changes</h4>'
                        . '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">'
                        . '<div class="bg-gray-50 p-3 rounded"><div class="text-sm text-gray-600 mb-1">Files to be created</div><pre class="text-xs whitespace-pre-wrap">' 
                        . htmlspecialchars(implode("\n", $report['created'] ?? []))
                        . '</pre></div>'
                        . '<div class="bg-gray-50 p-3 rounded"><div class="text-sm text-gray-600 mb-1">Files to be changed</div><pre class="text-xs whitespace-pre-wrap">' 
                        . htmlspecialchars(implode("\n", $report['changed'] ?? []))
                        . '</pre></div>'
                        . '</div>'
                        . '</div>';
                }
            } else {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">' . htmlspecialchars(implode(' ', $errors)) . '</div>';
            }
        } elseif ($sub === 'save_settings') {
            // Save repo/branch settings to config.json
            $repo = trim($_POST['repo'] ?? $cfg['repo']);
            $branch = trim($_POST['branch'] ?? $cfg['branch']);
            $configFile = CONFIG_DIR . '/config.json';
            $conf = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
            $conf['update_repo_url'] = $repo;
            $conf['update_branch'] = $branch;
            file_put_contents($configFile, json_encode($conf, JSON_PRETTY_PRINT));
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">Update settings saved.</div>';
            $cfg = ['repo' => $repo, 'branch' => $branch];
            $latest = fcms_fetch_latest_version($repo, $branch);
            $hasUpdate = $latest ? (fcms_compare_versions($latest, $current) > 0) : false;
        }
    }

    // Render UI
    ?>
    <div class="space-y-8">
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Status</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 p-4 rounded">
                    <div class="text-sm text-gray-500">Current Version</div>
                    <div class="text-2xl font-bold"><?php echo htmlspecialchars($current); ?></div>
                </div>
                <div class="bg-gray-50 p-4 rounded">
                    <div class="text-sm text-gray-500">Latest Available</div>
                    <div class="text-2xl font-bold"><?php echo htmlspecialchars($latest ?? 'Unknown'); ?></div>
                </div>
                <div class="bg-gray-50 p-4 rounded">
                    <div class="text-sm text-gray-500">Update</div>
                    <div class="text-2xl font-bold">
                        <?php if ($latest === null): ?>
                            <span class="text-yellow-600">Could not check</span>
                        <?php elseif ($hasUpdate): ?>
                            <span class="text-green-600">Available</span>
                        <?php else: ?>
                            <span class="text-gray-600">Up to date</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Update Settings</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="updates">
                <input type="hidden" name="subaction" value="save_settings">
                <div>
                    <label class="block mb-1">Repository URL</label>
                    <input type="text" name="repo" value="<?php echo htmlspecialchars($cfg['repo']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded">
                    <div class="text-xs text-gray-500 mt-1">Default: https://github.com/fearlessgeekmedia/FearlessCMS.git</div>
                </div>
                <div>
                    <label class="block mb-1">Branch</label>
                    <input type="text" name="branch" value="<?php echo htmlspecialchars($cfg['branch']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded">
                </div>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Save Settings</button>
            </form>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Perform Update</h3>
            <form method="POST" onsubmit="return confirm('Proceed with update? It will backup core files and replace CMS core while preserving your content, config, uploads, and sessions.');">
                <input type="hidden" name="action" value="updates">
                <input type="hidden" name="subaction" value="perform_update">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block mb-1">Branch</label>
                        <input type="text" name="branch" value="<?php echo htmlspecialchars($cfg['branch']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded">
                    </div>
                    <div class="flex items-center mt-6">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="create_backup" checked class="mr-2">
                            Create backup before updating
                        </label>
                    </div>
                    <div class="flex items-center mt-6">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="dry_run" class="mr-2">
                            Dry run (no changes)
                        </label>
                    </div>
                    <div class="mt-6">
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700" <?php echo $hasUpdate ? '' : 'disabled'; ?>>
                            <?php echo $hasUpdate ? 'Update to ' . htmlspecialchars($latest ?? '') : 'No Updates Available'; ?>
                        </button>
                    </div>
                </div>
            </form>
            <p class="text-xs text-gray-500 mt-4">Excluded from update: <code>content/</code>, <code>config/</code>, <code>uploads/</code>, <code>admin/uploads/</code>, <code>sessions/</code>, <code>cache/</code>, <code>.git/</code></p>
        </div>
    </div>
    <?php
}
