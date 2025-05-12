<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StoreService;
use App\Models\StoreConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StoreController extends Controller
{
    protected $storeService;

    public function __construct(StoreService $storeService)
    {
        $this->storeService = $storeService;
    }

    public function index()
    {
        $storeConfig = StoreConfig::first();
        $storeUrl = $storeConfig ? $storeConfig->store_url : config('store.default_url');
        $lastSync = $storeConfig ? $storeConfig->last_sync : null;

        // Get store statistics
        $stats = Cache::remember('store_stats', config('store.cache_time'), function () {
            $storeDir = storage_path('app/store/' . md5(config('store.default_url')));
            $packagesDir = $storeDir . '/packages';
            
            $stats = [
                'plugins' => 0,
                'themes' => 0
            ];

            if (is_dir($packagesDir)) {
                $directories = glob($packagesDir . '/*', GLOB_ONLYDIR);
                foreach ($directories as $dir) {
                    $packageJson = $dir . '/package.json';
                    if (file_exists($packageJson)) {
                        $package = json_decode(file_get_contents($packageJson), true);
                        if ($package['type'] === 'plugin') {
                            $stats['plugins']++;
                        } else if ($package['type'] === 'theme') {
                            $stats['themes']++;
                        }
                    }
                }
            }

            return $stats;
        });

        // Get recent activity
        $activity = Cache::remember('store_activity', config('store.cache_time'), function () {
            return []; // TODO: Implement activity tracking
        });

        return view('admin.store.index', compact('storeUrl', 'lastSync', 'stats', 'activity'));
    }

    public function sync()
    {
        $storeConfig = StoreConfig::first();
        if (!$storeConfig) {
            $storeConfig = new StoreConfig();
            $storeConfig->store_url = config('store.default_url');
            $storeConfig->save();
        }

        try {
            $this->storeService->syncStore($storeConfig);
            Cache::forget('store_stats');
            Cache::forget('store_activity');
            
            return redirect()->route('admin.store.index')
                ->with('success', 'Store synchronized successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.store.index')
                ->with('error', 'Failed to synchronize store: ' . $e->getMessage());
        }
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'store_url' => 'required|url'
        ]);

        $storeConfig = StoreConfig::first();
        if (!$storeConfig) {
            $storeConfig = new StoreConfig();
        }

        $storeConfig->store_url = $request->store_url;
        $storeConfig->save();

        // Clear cache
        Cache::forget('store_stats');
        Cache::forget('store_activity');

        return redirect()->route('admin.store.index')
            ->with('success', 'Store settings updated successfully');
    }
} 