<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Repository Interfaces
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Contracts\VideoRepositoryInterface;
use App\Repositories\Contracts\HomeSliderRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\TagRepositoryInterface;

// Repository Implementations
use App\Repositories\UserRepository;
use App\Repositories\VideoRepository;
use App\Repositories\HomeSliderRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\TagRepository;

// Service Interfaces
use App\Services\Interfaces\UserServiceInterface;
use App\Services\Contracts\VideoServiceInterface;
use App\Services\Contracts\HomeSliderServiceInterface;
use App\Services\Contracts\CategoryServiceInterface;
use App\Services\Contracts\TagServiceInterface;

// Service Implementations
use App\Services\UserService;
use App\Services\VideoService;
use App\Services\HomeSliderService;
use App\Services\CategoryService;
use App\Services\TagService;
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
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(TagRepositoryInterface::class, TagRepository::class);

        // =====================================================================
        // Service Bindings
        // =====================================================================
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(VideoServiceInterface::class, VideoService::class);
        $this->app->bind(HomeSliderServiceInterface::class, HomeSliderService::class);
        $this->app->bind(CategoryServiceInterface::class, CategoryService::class);
        $this->app->bind(TagServiceInterface::class, TagService::class);

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
