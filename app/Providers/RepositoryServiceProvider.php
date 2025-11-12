<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Repository Interfaces
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Contracts\VideoRepositoryInterface;
use App\Repositories\Contracts\HomeSliderRepositoryInterface;

// Repository Implementations
use App\Repositories\UserRepository;
use App\Repositories\VideoRepository;
use App\Repositories\HomeSliderRepository;

// Service Interfaces
use App\Services\Interfaces\UserServiceInterface;
use App\Services\Contracts\VideoServiceInterface;
use App\Services\Contracts\HomeSliderServiceInterface;

// Service Implementations
use App\Services\UserService;
use App\Services\VideoService;
use App\Services\HomeSliderService;
use App\Services\FileUploadService;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // =====================================================================
        // Repository Bindings
        // =====================================================================
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(VideoRepositoryInterface::class, VideoRepository::class);
        $this->app->bind(HomeSliderRepositoryInterface::class, HomeSliderRepository::class);

        // =====================================================================
        // Service Bindings
        // =====================================================================
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(VideoServiceInterface::class, VideoService::class);
        $this->app->bind(HomeSliderServiceInterface::class, HomeSliderService::class);

        // =====================================================================
        // Singleton Services (Tek instance kullanılacaklar)
        // =====================================================================
        $this->app->singleton(FileUploadService::class, function ($app) {
            return new FileUploadService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
