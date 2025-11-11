<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;

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

Route::middleware(['auth:sanctum', 'is_subscriber'])->group(function () {

    // ------------------------------------------------------------------------
    // Premium içerikler için route'lar buraya eklenecek
    // ------------------------------------------------------------------------
    Route::prefix('premium')->group(function () {
        // Örnek: Premium özellikler
        // Route::get('/content', [PremiumController::class, 'index']);
        // Route::get('/exclusive', [PremiumController::class, 'exclusive']);
    });
});
