<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'order',
        'is_active',
        'show_on_home', // ← YENİ EKLENEN
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_on_home' => 'boolean', // ← YENİ EKLENEN
        'order' => 'integer',
    ];

    protected static function booted()
    {
        static::saved(function ($category) {
            $category->clearCache();
            static::clearHomeCache(); // ← YENİ EKLENEN
        });

        static::deleted(function ($category) {
            $category->clearCache();
            static::clearHomeCache(); // ← YENİ EKLENEN
        });
    }

    // =========================================================================
    // İLİŞKİLER
    // =========================================================================

    public function videos()
    {
        return $this->belongsToMany(Video::class, 'category_video')
            ->withTimestamps();
    }

    public function activeVideos()
    {
        return $this->belongsToMany(Video::class, 'category_video')
            ->where('videos.is_active', true)
            ->withTimestamps();
    }

    // =========================================================================
    // HELPER METODLAR
    // =========================================================================

    public function getActiveVideosCount(): int
    {
        if (isset($this->active_videos_count)) {
            return $this->active_videos_count;
        }

        return Cache::remember(
            $this->getCacheKey('active_videos_count'),
            now()->addMinutes(5),
            fn() => $this->videos()->where('is_active', true)->count()
        );
    }

    public function getVideosCount(): int
    {
        if (isset($this->videos_count)) {
            return $this->videos_count;
        }

        return Cache::remember(
            $this->getCacheKey('videos_count'),
            now()->addMinutes(5),
            fn() => $this->videos()->count()
        );
    }

    private function getCacheKey(string $suffix): string
    {
        return "category_{$this->id}_{$suffix}";
    }

    public function clearCache(): void
    {
        Cache::forget($this->getCacheKey('active_videos_count'));
        Cache::forget($this->getCacheKey('videos_count'));
    }

    // ← YENİ EKLENEN METOD
    public static function clearHomeCache(): void
    {
        Cache::forget('home_categories');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('name');
    }

    // ← YENİ EKLENEN SCOPE
    public function scopeShowOnHome(Builder $query): Builder
    {
        return $query->where('show_on_home', true);
    }

    public function scopeWithVideosCount(Builder $query): Builder
    {
        return $query->withCount('videos');
    }

    public function scopeWithActiveVideosCount(Builder $query): Builder
    {
        return $query->withCount([
            'videos as active_videos_count' => function ($query) {
                $query->where('is_active', true);
            }
        ]);
    }

    public function scopeWithAllCounts(Builder $query): Builder
    {
        return $query->withCount([
            'videos',
            'videos as active_videos_count' => function ($query) {
                $query->where('is_active', true);
            }
        ]);
    }

    public function scopeWithVideos(Builder $query): Builder
    {
        return $query->with('videos');
    }

    public function scopeWithActiveVideos(Builder $query): Builder
    {
        return $query->with(['activeVideos']);
    }

    public function scopeHasActiveVideos(Builder $query): Builder
    {
        return $query->whereHas('videos', function ($query) {
            $query->where('is_active', true);
        });
    }

    public function scopeForApi(Builder $query): Builder
    {
        return $query->select(['id', 'name', 'slug', 'icon', 'order'])
            ->active()
            ->ordered()
            ->withActiveVideosCount();
    }

    // ← YENİ EKLENEN SCOPE
    public function scopeForHomePage(Builder $query): Builder
    {
        return $query->select(['id', 'name', 'slug', 'icon', 'order'])
            ->active()
            ->showOnHome()
            ->ordered()
            ->withActiveVideosCount();
    }

    public function scopeForAdmin(Builder $query): Builder
    {
        return $query->withAllCounts()->ordered();
    }

    // ← YENİ EKLENEN STATIC METOD
    /**
     * Ana sayfa için kategorileri getir (Cache'li)
     */
    public static function getHomeCategories()
    {
        return Cache::remember(
            'home_categories',
            now()->addMinutes(30),
            fn() => static::forHomePage()->get()
        );
    }
}
