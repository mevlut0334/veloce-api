<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Repository Interfaces
use App\Repositories\Interfaces\UserRepositoryInterface;

// Repository Implementations
use App\Repositories\UserRepository;

// Service Interfaces
use App\Services\Interfaces\UserServiceInterface;

// Service Implementations
use App\Services\UserService;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Repository Bindings
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // Service Bindings
        $this->app->bind(UserServiceInterface::class, UserService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
