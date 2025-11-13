<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API middleware grubuna CheckSubscriptionStatus ekle
        $middleware->api(append: [
            \App\Http\Middleware\CheckSubscriptionStatus::class,
        ]);

        // Middleware alias tanımla
        $middleware->alias([
            'check.subscription' => \App\Http\Middleware\CheckSubscriptionStatus::class,
            'is_admin' => \App\Http\Middleware\IsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
