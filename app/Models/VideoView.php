<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class VideoView extends Model
{
    use HasFactory;

    // Performans: timestamps kullanmıyorsanız kapatın
    // public $timestamps = false;

    protected $fillable = [
        'video_id',
        'user_id',
        'ip_address',
        'watch_duration',
        'is_completed',
        'viewed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'viewed_at' => 'datetime',
        'watch_duration' => 'integer',
        'video_id' => 'integer',
        'user_id' => 'integer',
    ];

    // Performans: Sık kullanılan alanları index olarak işaretle
    // Migration'da bu alanlara index eklemeyi unutmayın
    protected $with = []; // Eager loading gereksizse boş bırakın

    // Performans: Varsayılan sıralama
    protected static function booted(): void
    {
        // Sık kullanılan sıralamaları varsayılan yapabilirsiniz
        // static::addGlobalScope('latest', function (Builder $builder) {
        //     $builder->latest('viewed_at');
        // });
    }

    // İlişkiler - Performans: Return type tanımlaması
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class)
            ->select(['id', 'title', 'slug']); // Sadece gerekli kolonları seç
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)
            ->select(['id', 'name', 'email']); // Sadece gerekli kolonları seç
    }

    // Performans Optimizasyonlu Scope'lar
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('viewed_at', today());
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('viewed_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereYear('viewed_at', now()->year)
                     ->whereMonth('viewed_at', now()->month);
    }

    public function scopeThisYear(Builder $query): Builder
    {
        return $query->whereYear('viewed_at', now()->year);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('is_completed', true);
    }

    // Yeni Performans Scope'ları
    public function scopeForVideo(Builder $query, int $videoId): Builder
    {
        return $query->where('video_id', $videoId);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDateRange(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('viewed_at', [$start, $end]);
    }

    // İstatistik Metodları - Cache ile optimize edilmiş
    public static function getVideoStats(int $videoId, string $period = 'all'): array
    {
        $cacheKey = "video_stats_{$videoId}_{$period}";

        return cache()->remember($cacheKey, now()->addMinutes(10), function () use ($videoId, $period) {
            $query = self::forVideo($videoId);

            // Period filtreleme
            match($period) {
                'today' => $query->today(),
                'week' => $query->thisWeek(),
                'month' => $query->thisMonth(),
                'year' => $query->thisYear(),
                default => null
            };

            return [
                'total_views' => $query->count(),
                'completed_views' => $query->clone()->completed()->count(),
                'unique_users' => $query->clone()->distinct('user_id')->count('user_id'),
                'total_watch_time' => $query->sum('watch_duration'),
                'avg_watch_duration' => $query->avg('watch_duration'),
            ];
        });
    }

    // Bulk Insert için optimize edilmiş metod
    public static function recordView(array $data): self
    {
        return self::create([
            'video_id' => $data['video_id'],
            'user_id' => $data['user_id'] ?? null,
            'ip_address' => $data['ip_address'],
            'watch_duration' => $data['watch_duration'] ?? 0,
            'is_completed' => $data['is_completed'] ?? false,
            'viewed_at' => now(),
        ]);
    }

    // Toplu görüntüleme kaydı için
    public static function bulkRecord(array $views): bool
    {
        return self::insert($views);
    }
}
