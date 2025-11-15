<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// =========================================================================
// PUBLIC TAG ROUTES
// =========================================================================
use App\Http\Controllers\Api\TagController;

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

// =========================================================================
// NOT: Admin route'ları artık routes/api.php'de
// Filament admin paneli: /admin (otomatik register)
// API admin route'ları: /api/admin/*
// =========================================================================
