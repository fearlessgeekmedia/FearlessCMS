<?php

namespace App\Http\Controllers;

use App\Models\StoreConfig;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $storeConfig = StoreConfig::first();
        $storeUrl = $storeConfig ? $storeConfig->store_url : config('store.default_url');

        return view('dashboard', compact('storeUrl'));
    }
} 