<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Admin\VideoController;
use App\Http\Controllers\Admin\HomeSliderController;
use App\Http\Controllers\Admin\HomeSectionController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Admin\SubscriptionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Burada uygulamanızın API route'ları tanımlanır.
| Login olmadan sadece register ve login endpoint'lerine erişilebilir.
|
*/

// ============================================================================
// PUBLIC ROUTES (Login olmadan erişilebilir)
// ============================================================================

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// ============================================================================
// PROTECTED ROUTES (Login zorunlu - Abonelik gerekmez)
// ============================================================================

Route::middleware(['auth:sanctum'])->group(function () {

    // ------------------------------------------------------------------------
    // Auth Routes (Profil ve çıkış işlemleri)
    // ------------------------------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });

    // ------------------------------------------------------------------------
    // User CRUD Routes (Genel içerikler - Tüm login olmuş kullanıcılar erişebilir)
    // ------------------------------------------------------------------------
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);          // Tüm kullanıcıları listele
        Route::post('/', [UserController::class, 'store']);         // Yeni kullanıcı oluştur
        Route::get('/{id}', [UserController::class, 'show']);       // Tek kullanıcı detayı
        Route::put('/{id}', [UserController::class, 'update']);     // Kullanıcı güncelle
        Route::delete('/{id}', [UserController::class, 'delete']);  // Kullanıcı sil
    });
});

// ============================================================================
// PREMIUM ROUTES (Login + Abonelik zorunlu)
// ============================================================================

Route::middleware(['auth:sanctum', 'check.subscription'])->group(function () {

    // ------------------------------------------------------------------------
    // Premium içerikler için route'lar buraya eklenecek
    // ------------------------------------------------------------------------
    Route::prefix('premium')->group(function () {
        // Örnek: Premium özellikler
        // Route::get('/content', [PremiumController::class, 'index']);
        // Route::get('/exclusive', [PremiumController::class, 'exclusive']);
    });
});

// ============================================================================
// ADMIN API ROUTES (Login + Admin yetkisi zorunlu)
// ============================================================================

Route::prefix('admin')->name('api.admin.')->middleware(['auth:sanctum', 'is_admin'])->group(function () {

    // =====================================================================
    // VIDEO MANAGEMENT API
    // =====================================================================
    Route::prefix('videos')->name('videos.')->group(function () {
        Route::get('/statistics/overview', [VideoController::class, 'statistics'])->name('statistics');
        Route::post('/bulk/update-status', [VideoController::class, 'bulkUpdateStatus'])->name('bulk.update-status');
        Route::post('/bulk/update-premium', [VideoController::class, 'bulkUpdatePremium'])->name('bulk.update-premium');

        Route::get('/', [VideoController::class, 'index'])->name('index');
        Route::post('/', [VideoController::class, 'store'])->name('store');
        Route::get('/{video}', [VideoController::class, 'show'])->name('show');
        Route::put('/{video}', [VideoController::class, 'update'])->name('update');
        Route::delete('/{video}', [VideoController::class, 'destroy'])->name('destroy');

        Route::post('/{video}/toggle-active', [VideoController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/{video}/toggle-premium', [VideoController::class, 'togglePremium'])->name('toggle-premium');
        Route::post('/{video}/regenerate-thumbnail', [VideoController::class, 'regenerateThumbnail'])->name('regenerate-thumbnail');
    });

    // =====================================================================
    // HOME SLIDER MANAGEMENT API
    // =====================================================================
    Route::prefix('sliders')->name('sliders.')->group(function () {
        Route::post('/reorder', [HomeSliderController::class, 'reorder'])->name('reorder');

        Route::get('/', [HomeSliderController::class, 'index'])->name('index');
        Route::post('/', [HomeSliderController::class, 'store'])->name('store');
        Route::get('/{slider}', [HomeSliderController::class, 'show'])->name('show');
        Route::put('/{slider}', [HomeSliderController::class, 'update'])->name('update');
        Route::delete('/{slider}', [HomeSliderController::class, 'destroy'])->name('destroy');

        Route::post('/{slider}/toggle-active', [HomeSliderController::class, 'toggleActive'])->name('toggle-active');
    });

    // =====================================================================
    // HOME SECTION MANAGEMENT API
    // =====================================================================
    Route::prefix('home-sections')->name('home-sections.')->group(function () {
        Route::get('/statistics', [HomeSectionController::class, 'statistics'])->name('statistics');
        Route::post('/reorder', [HomeSectionController::class, 'reorder'])->name('reorder');

        Route::get('/', [HomeSectionController::class, 'index'])->name('index');
        Route::post('/', [HomeSectionController::class, 'store'])->name('store');
        Route::get('/{id}', [HomeSectionController::class, 'show'])->name('show');
        Route::get('/{id}/preview', [HomeSectionController::class, 'preview'])->name('preview');
        Route::put('/{id}', [HomeSectionController::class, 'update'])->name('update');
        Route::delete('/{id}', [HomeSectionController::class, 'destroy'])->name('destroy');

        Route::patch('/{id}/toggle-active', [HomeSectionController::class, 'toggleActive'])->name('toggle-active');
        Route::patch('/{id}/move-up', [HomeSectionController::class, 'moveUp'])->name('move-up');
        Route::patch('/{id}/move-down', [HomeSectionController::class, 'moveDown'])->name('move-down');
    });

    // =====================================================================
    // CATEGORY MANAGEMENT API
    // =====================================================================
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::post('/reorder', [CategoryController::class, 'reorder'])->name('reorder');

        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{category}', [CategoryController::class, 'show'])->name('show');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');

        Route::post('/{category}/toggle-active', [CategoryController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/{category}/toggle-show-on-home', [CategoryController::class, 'toggleShowOnHome'])->name('toggle-show-on-home');
    });

    // =====================================================================
    // TAG MANAGEMENT API
    // =====================================================================
    Route::prefix('tags')->name('tags.')->group(function () {
        Route::get('/statistics', [TagController::class, 'statistics'])->name('statistics');
        Route::get('/unused', [TagController::class, 'unused'])->name('unused');
        Route::post('/cleanup', [TagController::class, 'cleanup'])->name('cleanup');
        Route::post('/clear-cache', [TagController::class, 'clearCache'])->name('clear-cache');

        Route::get('/', [TagController::class, 'adminIndex'])->name('index');
        Route::post('/', [TagController::class, 'store'])->name('store');
        Route::put('/{tag}', [TagController::class, 'update'])->name('update');
        Route::delete('/{tag}', [TagController::class, 'destroy'])->name('destroy');

        Route::post('/{tag}/toggle-status', [TagController::class, 'toggleStatus'])->name('toggle-status');
    });

    // =====================================================================
    // SUBSCRIPTION MANAGEMENT API
    // =====================================================================
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/stats', [SubscriptionController::class, 'stats'])->name('stats');
        Route::get('/revenue', [SubscriptionController::class, 'revenue'])->name('revenue');
        Route::get('/active', [SubscriptionController::class, 'active'])->name('active');
        Route::get('/expired', [SubscriptionController::class, 'expired'])->name('expired');
        Route::get('/expiring', [SubscriptionController::class, 'expiring'])->name('expiring');
        Route::get('/manual', [SubscriptionController::class, 'manual'])->name('manual');
        Route::get('/paid', [SubscriptionController::class, 'paid'])->name('paid');

        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::post('/', [SubscriptionController::class, 'store'])->name('store');
        Route::get('/{id}', [SubscriptionController::class, 'show'])->name('show');
        Route::put('/{id}', [SubscriptionController::class, 'update'])->name('update');
        Route::delete('/{id}', [SubscriptionController::class, 'destroy'])->name('destroy');

        Route::post('/{id}/extend', [SubscriptionController::class, 'extend'])->name('extend');
        Route::post('/{id}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/{id}/activate', [SubscriptionController::class, 'activate'])->name('activate');
        Route::post('/{id}/renew', [SubscriptionController::class, 'renew'])->name('renew');
    });
});
