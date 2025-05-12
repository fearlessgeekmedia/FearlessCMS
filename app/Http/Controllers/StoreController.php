<?php

namespace App\Http\Controllers;

use App\Services\StoreService;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    protected $storeService;

    public function __construct(StoreService $storeService)
    {
        $this->storeService = $storeService;
    }

    public function index()
    {
        $featured = $this->storeService->getFeatured();
        $news = $this->storeService->getNews();

        return view('store.index', compact('featured', 'news'));
    }

    public function featured()
    {
        $featured = $this->storeService->getFeatured();
        return view('store.featured', compact('featured'));
    }

    public function news()
    {
        $news = $this->storeService->getNews();
        return view('store.news', compact('news'));
    }

    public function browse(Request $request)
    {
        $query = $request->get('q', '');
        $type = $request->get('type');
        
        $packages = $this->storeService->searchPackages($query, $type);
        
        return view('store.browse', compact('packages', 'query', 'type'));
    }

    public function install(Request $request, $package)
    {
        try {
            $type = $request->input('type');
            $this->storeService->installPackage($package, $type);
            
            return response()->json([
                'message' => "Package {$package} installed successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function uninstall(Request $request, $package)
    {
        try {
            $type = $request->input('type');
            $this->storeService->uninstallPackage($package, $type);
            
            return response()->json([
                'message' => "Package {$package} uninstalled successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
} 