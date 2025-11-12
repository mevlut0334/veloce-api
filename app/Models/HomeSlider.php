<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Jobs\ProcessSliderImageUpload;

class HomeSlider extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image_path',
        'video_id',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'video_id' => 'integer',
    ];

    protected $appends = [];

    protected static function booted()
    {
        static::saved(function ($slider) {
            static::clearAllCache();
        });

        static::deleted(function ($slider) {
            static::clearAllCache();
        });
    }

    // =========================================================================
    // JOB DISPATCH METODLARI - YENİ
    // =========================================================================

    /**
     * Slider image upload job'unu başlat
     */
    public function dispatchImageUpload(string $tempImagePath, string $targetFolder = 'sliders/processed'): void
    {
        ProcessSliderImageUpload::dispatch($this, $tempImagePath, $targetFolder);
    }

    // =========================================================================
    // İLİŞKİLER
    // =========================================================================

    public function video()
    {
        return $this->belongsTo(Video::class);
    }

    // =========================================================================
    // ACCESSORS & MUTATORS
    // =========================================================================

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        if (config('filesystems.cdn_url')) {
            return config('filesystems.cdn_url') . '/' . $this->image_path;
        }

        return Storage::url($this->image_path);
    }

    public function getFullImageUrl(): ?string
    {
        return $this->image_url;
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
        return $query->orderBy('order', 'asc');
    }

    public function scopeWithVideo(Builder $query): Builder
    {
        return $query->with([
            'video' => function ($query) {
                $query->select([
                    'id', 'title', 'slug', 'thumbnail_path',
                    'duration', 'view_count'
                ])->active();
            }
        ]);
    }

    public function scopeHasVideo(Builder $query): Builder
    {
        return $query->whereNotNull('video_id')
            ->whereHas('video', function ($query) {
                $query->active();
            });
    }

    public function scopeForHomePage(Builder $query): Builder
    {
        return $query
            ->select([
                'id', 'title', 'description', 'image_path',
                'video_id', 'order'
            ])
            ->active()
            ->ordered()
            ->withVideo();
    }

    public function scopeForAdmin(Builder $query): Builder
    {
        return $query->ordered()->withVideo();
    }

    // =========================================================================
    // STATIC METODLAR (CACHE'Lİ)
    // =========================================================================

    public static function getHomeSliders(): Collection
    {
        return Cache::remember(
            'home_sliders_active',
            now()->addMinutes(30),
            function () {
                return static::forHomePage()->get()->map(function ($slider) {
                    $slider->cached_image_url = $slider->image_url;
                    return $slider;
                });
            }
        );
    }

    public static function getAdminSliders(): Collection
    {
        return static::forAdmin()->get();
    }

    // =========================================================================
    // CACHE YÖNETİMİ
    // =========================================================================

    public static function clearAllCache(): void
    {
        Cache::forget('home_sliders_active');
    }

    // =========================================================================
    // HELPER METODLAR
    // =========================================================================

    public function getVideoUrl(): ?string
    {
        if (!$this->video) {
            return null;
        }

        return route('video.show', $this->video->slug);
    }

    public function isValid(): bool
    {
        return $this->is_active &&
               $this->image_path &&
               Storage::exists($this->image_path);
    }

    public function deleteImage(): bool
    {
        if ($this->image_path && Storage::exists($this->image_path)) {
            return Storage::delete($this->image_path);
        }
        return false;
    }
}
