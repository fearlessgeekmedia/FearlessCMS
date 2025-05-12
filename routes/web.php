<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StoreController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_middleware'),
    'verified'
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Store Routes
    Route::prefix('store')->name('store.')->group(function () {
        Route::get('/', [StoreController::class, 'index'])->name('index');
        Route::get('/featured', [StoreController::class, 'featured'])->name('featured');
        Route::get('/news', [StoreController::class, 'news'])->name('news');
        Route::get('/browse', [StoreController::class, 'browse'])->name('browse');
        Route::post('/install/{package}', [StoreController::class, 'install'])->name('install');
        Route::post('/uninstall/{package}', [StoreController::class, 'uninstall'])->name('uninstall');
    });

    // Admin Store Routes
    Route::middleware(['auth', 'verified'])->prefix('admin/store')->name('admin.store.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\StoreController::class, 'index'])->name('index');
        Route::post('/sync', [App\Http\Controllers\Admin\StoreController::class, 'sync'])->name('sync');
        Route::put('/settings', [App\Http\Controllers\Admin\StoreController::class, 'updateSettings'])->name('settings');
    });
}); 