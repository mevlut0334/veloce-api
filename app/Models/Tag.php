<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        // Slug otomatik oluştur
        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });

        // Cache temizleme
        static::saved(function ($tag) {
            $tag->clearCache();
            static::clearAllCache();
        });

        static::deleted(function ($tag) {
            $tag->clearCache();
            static::clearAllCache();
        });
    }

    // =========================================================================
    // İLİŞKİLER
    // =========================================================================

    /**
     * Tüm videolar
     */
    public function videos()
    {
        return $this->belongsToMany(Video::class, 'tag_video')
            ->withTimestamps();
    }

    /**
     * Sadece aktif videolar (Optimize edilmiş)
     */
    public function activeVideos()
    {
        return $this->belongsToMany(Video::class, 'tag_video')
            ->where('videos.is_active', true)
            ->withTimestamps();
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Sadece aktif tag'ler
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Popüler tag'ler (video sayısına göre)
     */
    public function scopePopular(Builder $query, int $limit = 20): Builder
    {
        return $query->withCount(['videos' => function ($query) {
                $query->where('is_active', true);
            }])
            ->having('videos_count', '>', 0)
            ->orderByDesc('videos_count')
            ->limit($limit);
    }

    /**
     * İsme göre arama
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('name', 'LIKE', "%{$search}%");
    }

    /**
     * Alfabetik sıralama
     */
    public function scopeAlphabetical(Builder $query): Builder
    {
        return $query->orderBy('name', 'asc');
    }

    /**
     * Video sayıları ile getir (N+1 önleme)
     */
    public function scopeWithVideosCount(Builder $query): Builder
    {
        return $query->withCount('videos');
    }

    /**
     * Aktif video sayıları ile getir (ÖNERİLEN)
     */
    public function scopeWithActiveVideosCount(Builder $query): Builder
    {
        return $query->withCount([
            'videos as active_videos_count' => function ($query) {
                $query->where('is_active', true);
            }
        ]);
    }

    /**
     * Her iki sayımı da getir
     */
    public function scopeWithAllCounts(Builder $query): Builder
    {
        return $query->withCount([
            'videos',
            'videos as active_videos_count' => function ($query) {
                $query->where('is_active', true);
            }
        ]);
    }

    /**
     * Videolar ile birlikte getir (Eager loading)
     */
    public function scopeWithVideos(Builder $query): Builder
    {
        return $query->with([
            'activeVideos' => function ($query) {
                $query->select([
                    'videos.id', 'videos.title', 'videos.slug',
                    'videos.thumbnail', 'videos.views_count'
                ])->limit(10);
            }
        ]);
    }

    /**
     * En az bir aktif videosu olan tag'ler
     */
    public function scopeHasActiveVideos(Builder $query): Builder
    {
        return $query->whereHas('videos', function ($query) {
            $query->where('is_active', true);
        });
    }

    /**
     * API için optimize edilmiş
     */
    public function scopeForApi(Builder $query): Builder
    {
        return $query->select(['id', 'name', 'slug'])
            ->active()
            ->withActiveVideosCount()
            ->alphabetical();
    }

    /**
     * Admin için optimize edilmiş
     */
    public function scopeForAdmin(Builder $query): Builder
    {
        return $query->withAllCounts()->alphabetical();
    }

    // =========================================================================
    // STATIC METODLAR (CACHE'Lİ)
    // =========================================================================

    /**
     * Popüler tag'leri getir (Cache'li)
     */
    public static function getPopularTags(int $limit = 20): Collection
    {
        return Cache::remember(
            "tags_popular_{$limit}",
            now()->addHours(6),
            fn() => static::active()
                ->withActiveVideosCount()
                ->having('active_videos_count', '>', 0)
                ->orderByDesc('active_videos_count')
                ->limit($limit)
                ->get()
        );
    }

    /**
     * Tüm aktif tag'leri getir (Cache'li)
     */
    public static function getActiveTags(): Collection
    {
        return Cache::remember(
            'tags_active_all',
            now()->addHours(12),
            fn() => static::forApi()->get()
        );
    }

    /**
     * Admin için tag'leri getir
     */
    public static function getAdminTags(): Collection
    {
        return Cache::remember(
            'tags_admin',
            now()->addMinutes(30),
            fn() => static::forAdmin()->get()
        );
    }

    /**
     * Tag bulutu için (en popüler 50)
     */
    public static function getTagCloud(): Collection
    {
        return Cache::remember(
            'tags_cloud',
            now()->addHours(6),
            fn() => static::popular(50)->get()
        );
    }

    /**
     * İsme göre tag bul veya oluştur
     */
    public static function findOrCreateByName(string $name): self
    {
        $slug = Str::slug($name);

        return static::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'is_active' => true]
        );
    }

    /**
     * Tag istatistikleri
     */
    public static function getTagStats(): array
    {
        return Cache::remember(
            'tag_stats',
            now()->addMinutes(30),
            function () {
                $tags = static::withAllCounts()->get();

                return [
                    'total_tags' => $tags->count(),
                    'active_tags' => $tags->where('is_active', true)->count(),
                    'tags_with_videos' => $tags->where('videos_count', '>', 0)->count(),
                    'total_video_tag_relations' => $tags->sum('videos_count'),
                    'most_used' => $tags->sortByDesc('active_videos_count')
                        ->take(10)
                        ->map(fn($tag) => [
                            'name' => $tag->name,
                            'count' => $tag->active_videos_count
                        ])
                        ->values()
                        ->toArray()
                ];
            }
        );
    }

    // =========================================================================
    // HELPER METODLAR
    // =========================================================================

    /**
     * Video sayısını döndür (Cache'li veya withCount)
     */
    public function getVideosCount(): int
    {
        // Eğer withCount ile yüklenmişse
        if (isset($this->videos_count)) {
            return $this->videos_count;
        }

        return Cache::remember(
            $this->getCacheKey('videos_count'),
            now()->addMinutes(10),
            fn() => $this->videos()->count()
        );
    }

    /**
     * Aktif video sayısını döndür (Cache'li veya withCount)
     */
    public function getActiveVideosCount(): int
    {
        // Eğer withCount ile yüklenmişse
        if (isset($this->active_videos_count)) {
            return $this->active_videos_count;
        }

        return Cache::remember(
            $this->getCacheKey('active_videos_count'),
            now()->addMinutes(10),
            fn() => $this->videos()->where('is_active', true)->count()
        );
    }

    /**
     * Tag URL'sini döndür
     */
    public function getUrl(): string
    {
        return route('tag.show', $this->slug);
    }

    /**
     * En çok izlenen videoları getir
     */
    public function getMostViewedVideos(int $limit = 10): Collection
    {
        return Cache::remember(
            $this->getCacheKey("most_viewed_{$limit}"),
            now()->addHours(1),
            fn() => $this->activeVideos()
                ->select([
                    'videos.id', 'videos.title', 'videos.slug',
                    'videos.thumbnail', 'videos.views_count', 'videos.duration'
                ])
                ->orderByDesc('videos.views_count')
                ->limit($limit)
                ->get()
        );
    }

    // =========================================================================
    // CACHE YÖNETİMİ
    // =========================================================================

    /**
     * Cache key oluştur
     */
    private function getCacheKey(string $suffix): string
    {
        return "tag_{$this->id}_{$suffix}";
    }

    /**
     * Tag'a ait cache'leri temizle
     */
    public function clearCache(): void
    {
        Cache::forget($this->getCacheKey('videos_count'));
        Cache::forget($this->getCacheKey('active_videos_count'));

        // Most viewed cache'lerini temizle
        foreach ([10, 20, 50] as $limit) {
            Cache::forget($this->getCacheKey("most_viewed_{$limit}"));
        }
    }

    /**
     * Tüm tag cache'lerini temizle
     */
    public static function clearAllCache(): void
    {
        $keys = [
            'tags_popular_20',
            'tags_popular_50',
            'tags_active_all',
            'tags_admin',
            'tags_cloud',
            'tag_stats',
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}
