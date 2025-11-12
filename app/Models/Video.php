<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Jobs\ProcessVideoUpload;
use App\Jobs\ProcessThumbnailUpload;
use App\Jobs\OptimizeVideo;
use App\Jobs\GenerateThumbnail;

class Video extends Model
{
    use HasFactory;

    protected $table = 'videos';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'video_path',
        'thumbnail_path',
        'duration',
        'orientation',
        'is_premium',
        'is_active',
        'view_count',
        'favorite_count',
    ];

    protected function casts(): array
    {
        return [
            'is_premium' => 'boolean',
            'is_active' => 'boolean',
            'view_count' => 'integer',
            'favorite_count' => 'integer',
            'duration' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected $appends = [];

    public const ORIENTATION_HORIZONTAL = 'horizontal';
    public const ORIENTATION_VERTICAL = 'vertical';

    // =========================================================================
    // JOB DISPATCH METODLARI - YENİ
    // =========================================================================

    /**
     * Video upload job'unu başlat
     */
    public function dispatchVideoUpload(string $tempVideoPath, string $targetFolder = 'videos/processed'): void
    {
        ProcessVideoUpload::dispatch($this, $tempVideoPath, $targetFolder);
    }

    /**
     * Thumbnail upload job'unu başlat
     */
    public function dispatchThumbnailUpload(string $tempThumbnailPath, string $targetFolder = 'thumbnails/processed'): void
    {
        ProcessThumbnailUpload::dispatch($this, $tempThumbnailPath, $targetFolder);
    }

    /**
     * Video optimizasyon job'unu başlat
     */
    public function dispatchOptimization(): void
    {
        OptimizeVideo::dispatch($this);
    }

    /**
     * Otomatik thumbnail oluşturma job'unu başlat
     */
    public function dispatchThumbnailGeneration(int $timeInSeconds = 2): void
    {
        GenerateThumbnail::dispatch($this, $timeInSeconds);
    }

    /**
     * Tüm işlemleri sırayla başlat (chain)
     */
    public function dispatchAllProcessing(string $tempVideoPath, ?string $tempThumbnailPath = null): void
    {
        // Video upload
        ProcessVideoUpload::dispatch($this, $tempVideoPath)
            ->chain([
                // Video upload tamamlandıktan sonra optimize et
                new OptimizeVideo($this),
            ]);

        // Eğer thumbnail manuel yüklendiyse
        if ($tempThumbnailPath) {
            ProcessThumbnailUpload::dispatch($this, $tempThumbnailPath);
        } else {
            // Yoksa otomatik oluştur (video upload'tan sonra)
            GenerateThumbnail::dispatch($this)->delay(now()->addSeconds(30));
        }
    }

    // =========================================================================
    // İLİŞKİLER
    // =========================================================================

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_video')
            ->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'tag_video')
            ->withTimestamps();
    }

    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(UserPlaylist::class, 'playlist_video')
            ->withPivot('order')
            ->withTimestamps();
    }

    public function views(): HasMany
    {
        return $this->hasMany(VideoView::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_favorites')
            ->withTimestamps();
    }

    // =========================================================================
    // ACCESSOR METODLAR
    // =========================================================================

    public function videoUrl(): string
    {
        return Storage::url($this->video_path);
    }

    public function thumbnailUrl(): string
    {
        return Storage::url($this->thumbnail_path);
    }

    public function formattedDuration(): string
    {
        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    public function scopeFree($query)
    {
        return $query->where('is_premium', false);
    }

    public function scopeHorizontal($query)
    {
        return $query->where('orientation', self::ORIENTATION_HORIZONTAL);
    }

    public function scopeVertical($query)
    {
        return $query->where('orientation', self::ORIENTATION_VERTICAL);
    }

    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderByDesc('view_count')->limit($limit);
    }

    public function scopeMostFavorited($query, int $limit = 10)
    {
        return $query->orderByDesc('favorite_count')->limit($limit);
    }

    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }

    public function scopeInCategory($query, int $categoryId)
    {
        return $query->whereHas('categories', function($q) use ($categoryId) {
            $q->where('categories.id', $categoryId);
        });
    }

    public function scopeWithTag($query, int $tagId)
    {
        return $query->whereHas('tags', function($q) use ($tagId) {
            $q->where('tags.id', $tagId);
        });
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeWithRelations($query)
    {
        return $query->with([
            'categories:id,name,slug',
            'tags:id,name,slug'
        ]);
    }

    public function scopeWithCounts($query)
    {
        return $query->withCount(['views', 'favoritedBy']);
    }

    // =========================================================================
    // HELPER METODLAR
    // =========================================================================

    public function incrementViewCount(): bool
    {
        return $this->increment('view_count', 1, ['updated_at' => $this->updated_at]);
    }

    public function incrementFavoriteCount(): bool
    {
        return $this->increment('favorite_count', 1, ['updated_at' => $this->updated_at]);
    }

    public function decrementFavoriteCount(): bool
    {
        if ($this->favorite_count > 0) {
            return $this->decrement('favorite_count', 1, ['updated_at' => $this->updated_at]);
        }
        return false;
    }

    public function isFavoritedBy(?int $userId = null): bool
    {
        if (!$userId) {
            return false;
        }

        if ($this->relationLoaded('favoritedBy')) {
            return $this->favoritedBy->contains('id', $userId);
        }

        return $this->favoritedBy()->where('user_id', $userId)->exists();
    }

    public function canBeAccessedBy(?User $user = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->is_premium) {
            return true;
        }

        return $user && $user->isSubscriber();
    }

    public function generateSlug(): string
    {
        $slug = Str::slug($this->title);
        $count = static::where('slug', 'like', "{$slug}%")->count();

        return $count > 0 ? "{$slug}-{$count}" : $slug;
    }

    public function addCategory(int $categoryId): void
    {
        $this->categories()->syncWithoutDetaching([$categoryId]);
    }

    public function addTag(int $tagId): void
    {
        $this->tags()->syncWithoutDetaching([$tagId]);
    }

    public function syncCategories(array $categoryIds): void
    {
        $this->categories()->sync($categoryIds);
    }

    public function syncTags(array $tagIds): void
    {
        $this->tags()->sync($tagIds);
    }

    public function deleteFiles(): bool
    {
        $success = true;

        if ($this->video_path && Storage::exists($this->video_path)) {
            $success = Storage::delete($this->video_path) && $success;
        }

        if ($this->thumbnail_path && Storage::exists($this->thumbnail_path)) {
            $success = Storage::delete($this->thumbnail_path) && $success;
        }

        return $success;
    }

    // =========================================================================
    // STATIC METODLAR
    // =========================================================================

    public static function findBySlugOrFail(string $slug): self
    {
        return static::bySlug($slug)->firstOrFail();
    }

    public static function getPopularVideos(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->popular($limit)
            ->withRelations()
            ->get();
    }

    public static function getStatistics(): array
    {
        return [
            'total' => static::count(),
            'active' => static::active()->count(),
            'premium' => static::premium()->count(),
            'free' => static::free()->count(),
            'horizontal' => static::horizontal()->count(),
            'vertical' => static::vertical()->count(),
            'total_views' => static::sum('view_count'),
            'total_favorites' => static::sum('favorite_count'),
        ];
    }

    public static function updateViewCounts(array $videoIdViewCounts): void
    {
        foreach ($videoIdViewCounts as $videoId => $count) {
            static::where('id', $videoId)
                ->increment('view_count', $count, ['updated_at' => \DB::raw('updated_at')]);
        }
    }

    public function getSimilarVideos(int $limit = 6): \Illuminate\Database\Eloquent\Collection
    {
        $categoryIds = $this->categories->pluck('id');

        return static::active()
            ->where('id', '!=', $this->id)
            ->where('orientation', $this->orientation)
            ->when($categoryIds->isNotEmpty(), function($query) use ($categoryIds) {
                $query->whereHas('categories', function($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                });
            })
            ->withRelations()
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    // =========================================================================
    // EVENT HOOKS
    // =========================================================================

    protected static function booted()
    {
        static::creating(function ($video) {
            if (!$video->slug) {
                $video->slug = $video->generateSlug();
            }

            if (is_null($video->view_count)) {
                $video->view_count = 0;
            }

            if (is_null($video->favorite_count)) {
                $video->favorite_count = 0;
            }

            // Job kullanırken varsayılan olarak inactive başlat
            if (is_null($video->is_active)) {
                $video->is_active = false;
            }
        });

        static::updating(function ($video) {
            if ($video->isDirty('title') && !$video->isDirty('slug')) {
                $video->slug = $video->generateSlug();
            }
        });

        static::deleting(function ($video) {
            $video->deleteFiles();
            $video->categories()->detach();
            $video->tags()->detach();
            $video->playlists()->detach();
            $video->favoritedBy()->detach();
        });
    }
}
