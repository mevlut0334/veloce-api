<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'resolution',
        'file_size',
        'is_premium',
        'is_active',
        'is_processed',
        'view_count',
        'favorite_count',
    ];

    protected function casts(): array
    {
        return [
            'is_premium' => 'boolean',
            'is_active' => 'boolean',
            'is_processed' => 'boolean',
            'view_count' => 'integer',
            'favorite_count' => 'integer',
            'duration' => 'integer',
            'file_size' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // Resource'larda otomatik kullanılacak accessor'lar
    protected $appends = ['duration_human', 'video_url', 'thumbnail_url', 'file_size_human'];

    // Orientation sabitleri
    public const ORIENTATION_HORIZONTAL = 0; // Database değeri
    public const ORIENTATION_VERTICAL = 1;   // Database değeri

    public const ORIENTATION_HORIZONTAL_STRING = 'horizontal'; // API/Form değeri
    public const ORIENTATION_VERTICAL_STRING = 'vertical';     // API/Form değeri

    // Video limitleri
    public const MAX_DURATION_SECONDS = 600; // 10 dakika
    public const MAX_FILE_SIZE_MB = 2048; // 2GB
    public const MAX_FILE_SIZE_BYTES = self::MAX_FILE_SIZE_MB * 1024 * 1024;

    // =========================================================================
    // ORIENTATION ATTRIBUTE (Laravel 12 Syntax)
    // =========================================================================

    /**
     * Orientation attribute
     * GET: Database'den integer alır, string döner ("horizontal" veya "vertical")
     * SET: String veya integer alır, database'e integer kaydeder (0 veya 1)
     */
    protected function orientation(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value === self::ORIENTATION_VERTICAL
                ? self::ORIENTATION_VERTICAL_STRING
                : self::ORIENTATION_HORIZONTAL_STRING,
            set: fn ($value) => is_string($value)
                ? ($value === self::ORIENTATION_VERTICAL_STRING ? self::ORIENTATION_VERTICAL : self::ORIENTATION_HORIZONTAL)
                : (int) $value,
        );
    }

    // =========================================================================
    // ACCESSOR METODLARI - Resource'larda kullanılacak
    // =========================================================================

    /**
     * Tam video URL'i döndürür
     */
    protected function videoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->video_path ? Storage::url($this->video_path) : null,
        );
    }

    /**
     * Tam thumbnail URL'i döndürür
     */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->thumbnail_path ? Storage::url($this->thumbnail_path) : null,
        );
    }

    /**
     * İnsan okunabilir süre formatı: "5:30" veya "1:23:45"
     */
    protected function durationHuman(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->duration) {
                    return '0:00';
                }

                $hours = floor($this->duration / 3600);
                $minutes = floor(($this->duration % 3600) / 60);
                $seconds = $this->duration % 60;

                if ($hours > 0) {
                    return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
                }

                return sprintf('%d:%02d', $minutes, $seconds);
            }
        );
    }

    /**
     * İnsan okunabilir dosya boyutu: "15.5 MB"
     */
    protected function fileSizeHuman(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->file_size) {
                    return null;
                }

                $units = ['B', 'KB', 'MB', 'GB'];
                $size = $this->file_size;
                $unit = 0;

                while ($size >= 1024 && $unit < count($units) - 1) {
                    $size /= 1024;
                    $unit++;
                }

                return round($size, 2) . ' ' . $units[$unit];
            }
        );
    }

    // =========================================================================
    // JOB DISPATCH METODLARI
    // =========================================================================

    public function dispatchVideoUpload(string $tempVideoPath, string $targetFolder = 'videos/processed'): void
    {
        ProcessVideoUpload::dispatch($this, $tempVideoPath, $targetFolder);
    }

    public function dispatchThumbnailUpload(string $tempThumbnailPath, string $targetFolder = 'thumbnails/processed'): void
    {
        ProcessThumbnailUpload::dispatch($this, $tempThumbnailPath, $targetFolder);
    }

    public function dispatchOptimization(): void
    {
        OptimizeVideo::dispatch($this);
    }

    public function dispatchThumbnailGeneration(int $timeInSeconds = 2): void
    {
        GenerateThumbnail::dispatch($this, $timeInSeconds);
    }

    public function dispatchAllProcessing(string $tempVideoPath, ?string $tempThumbnailPath = null): void
    {
        ProcessVideoUpload::dispatch($this, $tempVideoPath)
            ->chain([
                new OptimizeVideo($this),
            ]);

        if ($tempThumbnailPath) {
            ProcessThumbnailUpload::dispatch($this, $tempThumbnailPath);
        } else {
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
    // ESKİ METODLAR KALDIRILDI - Artık Attribute olarak çalışıyorlar
    // $video->video_url, $video->thumbnail_url, $video->duration_human kullanın
    // =========================================================================

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

    /**
     * Yatay videolar (integer ile karşılaştırma)
     */
    public function scopeHorizontal($query)
    {
        return $query->where('orientation', self::ORIENTATION_HORIZONTAL);
    }

    /**
     * Dikey videolar (integer ile karşılaştırma)
     */
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

    public function scopeProcessed($query)
    {
        return $query->where('is_processed', true);
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

    /**
     * Orientation helper - RAW database değerini kontrol eder
     * NOT: Attribute üzerinden değil, getRawOriginal() kullanılmalı
     */
    public function isHorizontal(): bool
    {
        return $this->getRawOriginal('orientation') === self::ORIENTATION_HORIZONTAL;
    }

    /**
     * Orientation helper - RAW database değerini kontrol eder
     * NOT: Attribute üzerinden değil, getRawOriginal() kullanılmalı
     */
    public function isVertical(): bool
    {
        return $this->getRawOriginal('orientation') === self::ORIENTATION_VERTICAL;
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
            ->where('orientation', $this->getRawOriginal('orientation')) // RAW integer kullan
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

            if (is_null($video->is_active)) {
                $video->is_active = false;
            }

            if (is_null($video->is_processed)) {
                $video->is_processed = false;
            }

            // Orientation default: horizontal
            if (is_null($video->getRawOriginal('orientation'))) {
                $video->orientation = self::ORIENTATION_HORIZONTAL;
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
