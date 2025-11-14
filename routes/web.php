<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\VideoController;
use App\Http\Controllers\Admin\HomeSliderController;
use App\Http\Controllers\Admin\HomeSectionController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Admin\SubscriptionController;

Route::get('/', function () {
    return view('welcome');
});

// =========================================================================
// ADMIN ROUTES
// =========================================================================
Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum', 'is_admin'])->group(function () {

    // Dashboard
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // =====================================================================
    // VIDEO MANAGEMENT
    // =====================================================================
    Route::prefix('videos')->name('videos.')->group(function () {
        // Özel rotalar önce (statistics, bulk)
        Route::get('/statistics/overview', [VideoController::class, 'statistics'])->name('statistics');
        Route::post('/bulk/update-status', [VideoController::class, 'bulkUpdateStatus'])->name('bulk.update-status');
        Route::post('/bulk/update-premium', [VideoController::class, 'bulkUpdatePremium'])->name('bulk.update-premium');

        // CRUD
        Route::get('/', [VideoController::class, 'index'])->name('index');
        Route::get('/create', [VideoController::class, 'create'])->name('create');
        Route::post('/', [VideoController::class, 'store'])->name('store');
        Route::get('/{video}', [VideoController::class, 'show'])->name('show');
        Route::get('/{video}/edit', [VideoController::class, 'edit'])->name('edit');
        Route::put('/{video}', [VideoController::class, 'update'])->name('update');
        Route::delete('/{video}', [VideoController::class, 'destroy'])->name('destroy');

        // Toggle işlemleri
        Route::post('/{video}/toggle-active', [VideoController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/{video}/toggle-premium', [VideoController::class, 'togglePremium'])->name('toggle-premium');
        Route::post('/{video}/regenerate-thumbnail', [VideoController::class, 'regenerateThumbnail'])->name('regenerate-thumbnail');
    });

    // =====================================================================
    // HOME SLIDER MANAGEMENT
    // =====================================================================
    Route::prefix('sliders')->name('sliders.')->group(function () {
        // Özel rotalar önce
        Route::post('/reorder', [HomeSliderController::class, 'reorder'])->name('reorder');

        // CRUD
        Route::get('/', [HomeSliderController::class, 'index'])->name('index');
        Route::get('/create', [HomeSliderController::class, 'create'])->name('create');
        Route::post('/', [HomeSliderController::class, 'store'])->name('store');
        Route::get('/{slider}/edit', [HomeSliderController::class, 'edit'])->name('edit');
        Route::put('/{slider}', [HomeSliderController::class, 'update'])->name('update');
        Route::delete('/{slider}', [HomeSliderController::class, 'destroy'])->name('destroy');

        // Toggle işlemleri
        Route::post('/{slider}/toggle-active', [HomeSliderController::class, 'toggleActive'])->name('toggle-active');
    });

    // =====================================================================
    // HOME SECTION MANAGEMENT
    // =====================================================================
    Route::prefix('home-sections')->name('home-sections.')->group(function () {
        // Özel rotalar önce
        Route::get('/statistics', [HomeSectionController::class, 'statistics'])->name('statistics');
        Route::post('/reorder', [HomeSectionController::class, 'reorder'])->name('reorder');

        // CRUD
        Route::get('/', [HomeSectionController::class, 'index'])->name('index');
        Route::post('/', [HomeSectionController::class, 'store'])->name('store');
        Route::get('/{id}', [HomeSectionController::class, 'show'])->name('show');
        Route::get('/{id}/preview', [HomeSectionController::class, 'preview'])->name('preview');
        Route::put('/{id}', [HomeSectionController::class, 'update'])->name('update');
        Route::delete('/{id}', [HomeSectionController::class, 'destroy'])->name('destroy');

        // Action işlemleri
        Route::patch('/{id}/toggle-active', [HomeSectionController::class, 'toggleActive'])->name('toggle-active');
        Route::patch('/{id}/move-up', [HomeSectionController::class, 'moveUp'])->name('move-up');
        Route::patch('/{id}/move-down', [HomeSectionController::class, 'moveDown'])->name('move-down');
    });

    // =====================================================================
    // CATEGORY MANAGEMENT
    // =====================================================================
    Route::prefix('categories')->name('categories.')->group(function () {
        // Özel rotalar önce
        Route::post('/reorder', [CategoryController::class, 'reorder'])->name('reorder');

        // CRUD
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/create', [CategoryController::class, 'create'])->name('create');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{category}', [CategoryController::class, 'show'])->name('show');
        Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');

        // Toggle işlemleri
        Route::post('/{category}/toggle-active', [CategoryController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/{category}/toggle-show-on-home', [CategoryController::class, 'toggleShowOnHome'])->name('toggle-show-on-home');
    });

    // =====================================================================
    // TAG MANAGEMENT
    // =====================================================================
    Route::prefix('tags')->name('tags.')->group(function () {
        // Özel rotalar önce (statistics, unused, cleanup, clear-cache)
        Route::get('/statistics', [TagController::class, 'statistics'])->name('statistics');
        Route::get('/unused', [TagController::class, 'unused'])->name('unused');
        Route::post('/cleanup', [TagController::class, 'cleanup'])->name('cleanup');
        Route::post('/clear-cache', [TagController::class, 'clearCache'])->name('clear-cache');

        // CRUD
        Route::get('/', [TagController::class, 'adminIndex'])->name('index');
        Route::post('/', [TagController::class, 'store'])->name('store');
        Route::put('/{tag}', [TagController::class, 'update'])->name('update');
        Route::delete('/{tag}', [TagController::class, 'destroy'])->name('destroy');

        // Toggle işlemleri
        Route::post('/{tag}/toggle-status', [TagController::class, 'toggleStatus'])->name('toggle-status');
    });

    // =====================================================================
    // SUBSCRIPTION MANAGEMENT
   // =====================================================================
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        // İstatistikler ve özel listeler önce
        Route::get('/stats', [SubscriptionController::class, 'stats'])->name('stats');
        Route::get('/revenue', [SubscriptionController::class, 'revenue'])->name('revenue');
        Route::get('/active', [SubscriptionController::class, 'active'])->name('active');
        Route::get('/expired', [SubscriptionController::class, 'expired'])->name('expired');
        Route::get('/expiring', [SubscriptionController::class, 'expiring'])->name('expiring');
        Route::get('/manual', [SubscriptionController::class, 'manual'])->name('manual');
        Route::get('/paid', [SubscriptionController::class, 'paid'])->name('paid');

        // CRUD
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::post('/', [SubscriptionController::class, 'store'])->name('store');
        Route::get('/{id}', [SubscriptionController::class, 'show'])->name('show');
        Route::put('/{id}', [SubscriptionController::class, 'update'])->name('update');
        Route::delete('/{id}', [SubscriptionController::class, 'destroy'])->name('destroy');

        // Abonelik işlemleri
        Route::post('/{id}/extend', [SubscriptionController::class, 'extend'])->name('extend');
        Route::post('/{id}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/{id}/activate', [SubscriptionController::class, 'activate'])->name('activate');
        Route::post('/{id}/renew', [SubscriptionController::class, 'renew'])->name('renew');
    });

    // =====================================================================
    // USER MANAGEMENT (İleride eklenecek)
    // =====================================================================
    // Route::resource('users', UserController::class);

    // =====================================================================
    // SETTINGS (İleride eklenecek)
    // =====================================================================
    // Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
});

// =========================================================================
// PUBLIC TAG ROUTES
// =========================================================================
Route::prefix('tags')->name('tags.')->group(function () {
    // Özel rotalar önce (popular, cloud, search)
    Route::get('/popular', [TagController::class, 'popular'])->name('popular');
    Route::get('/cloud', [TagController::class, 'cloud'])->name('cloud');
    Route::get('/search', [TagController::class, 'search'])->name('search');

    // Genel liste
    Route::get('/', [TagController::class, 'index'])->name('index');

    // Wildcard rotalar en sona
    Route::get('/{slug}', [TagController::class, 'show'])->name('show');
    Route::get('/{slug}/videos', [TagController::class, 'videos'])->name('videos');
});
