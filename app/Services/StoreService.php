<?php

namespace App\Services;

use App\Models\StoreConfig;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Parsedown;

class StoreService
{
    protected $storePath;
    protected $parsedown;

    public function __construct()
    {
        $this->storePath = storage_path('app/store');
        $this->parsedown = new Parsedown();
    }

    public function syncStore(StoreConfig $config)
    {
        if (!File::exists($this->storePath)) {
            File::makeDirectory($this->storePath, 0755, true);
        }

        $storeUrl = $config->store_url;
        $storeDir = $this->storePath . '/' . md5($storeUrl);

        if (!File::exists($storeDir)) {
            // Clone the repository
            exec("git clone {$storeUrl} {$storeDir}");
        } else {
            // Pull latest changes
            exec("cd {$storeDir} && git pull");
        }

        $config->update(['last_sync' => now()]);
    }

    public function getFeatured()
    {
        $featuredPath = $this->storePath . '/' . md5(config('store.default_url')) . '/featured.md';
        
        if (!File::exists($featuredPath)) {
            return null;
        }

        $content = File::get($featuredPath);
        return $this->parsedown->text($content);
    }

    public function getNews()
    {
        $newsPath = $this->storePath . '/' . md5(config('store.default_url')) . '/news.md';
        
        if (!File::exists($newsPath)) {
            return null;
        }

        $content = File::get($newsPath);
        return $this->parsedown->text($content);
    }

    public function searchPackages($query, $type = null)
    {
        $storeDir = $this->storePath . '/' . md5(config('store.default_url'));
        $packagesDir = $storeDir . '/packages';
        
        if (!File::exists($packagesDir)) {
            return collect();
        }

        $packages = collect();
        $directories = File::directories($packagesDir);

        foreach ($directories as $dir) {
            $packageJson = $dir . '/package.json';
            
            if (File::exists($packageJson)) {
                $package = json_decode(File::get($packageJson), true);
                
                if ($type && $package['type'] !== $type) {
                    continue;
                }

                if (stripos($package['name'], $query) !== false || 
                    stripos($package['description'], $query) !== false) {
                    $packages->push($package);
                }
            }
        }

        return $packages;
    }

    public function installPackage($packageName, $type)
    {
        $storeDir = $this->storePath . '/' . md5(config('store.default_url'));
        $packageDir = $storeDir . '/packages/' . $packageName;
        
        if (!File::exists($packageDir)) {
            throw new \Exception("Package not found: {$packageName}");
        }

        $packageJson = $packageDir . '/package.json';
        if (!File::exists($packageJson)) {
            throw new \Exception("Invalid package: {$packageName}");
        }

        $package = json_decode(File::get($packageJson), true);
        if ($package['type'] !== $type) {
            throw new \Exception("Invalid package type: {$type}");
        }

        // Create the target directory
        $targetDir = base_path($type === 'plugin' ? 'plugins' : 'themes') . '/' . $packageName;
        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        // Copy package files
        File::copyDirectory($packageDir . '/src', $targetDir);

        // Run installation hooks if they exist
        $installHook = $packageDir . '/install.php';
        if (File::exists($installHook)) {
            require_once $installHook;
            if (function_exists('install')) {
                install();
            }
        }

        return true;
    }

    public function uninstallPackage($packageName, $type)
    {
        $targetDir = base_path($type === 'plugin' ? 'plugins' : 'themes') . '/' . $packageName;
        
        if (!File::exists($targetDir)) {
            throw new \Exception("Package not installed: {$packageName}");
        }

        // Run uninstallation hooks if they exist
        $uninstallHook = $targetDir . '/uninstall.php';
        if (File::exists($uninstallHook)) {
            require_once $uninstallHook;
            if (function_exists('uninstall')) {
                uninstall();
            }
        }

        // Remove package files
        File::deleteDirectory($targetDir);

        return true;
    }
} 