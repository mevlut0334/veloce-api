<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFavorite extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_favorites';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'video_id',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'video_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // İlişkiler

    /**
     * User ilişkisi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Video ilişkisi
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    // Scope'lar

    /**
     * Belirli bir kullanıcının favorileri
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Belirli bir videonun favorileri
     */
    public function scopeForVideo($query, int $videoId)
    {
        return $query->where('video_id', $videoId);
    }

    /**
     * Son eklenen favoriler
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }

    /**
     * İlişkilerle birlikte yükle
     */
    public function scopeWithRelations($query)
    {
        return $query->with(['user:id,name,email', 'video:id,title,slug,thumbnail']);
    }

    // Static Helper Metodlar

    /**
     * Favori ekleme/çıkarma toggle
     */
    public static function toggle(int $userId, int $videoId): bool
    {
        $favorite = static::where('user_id', $userId)
            ->where('video_id', $videoId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return false; // Favorilerden çıkarıldı
        }

        static::create([
            'user_id' => $userId,
            'video_id' => $videoId,
        ]);

        return true; // Favorilere eklendi
    }

    /**
     * Kullanıcının bir videoyu favorilere ekleyip eklemediğini kontrol et
     */
    public static function isFavorited(int $userId, int $videoId): bool
    {
        return static::where('user_id', $userId)
            ->where('video_id', $videoId)
            ->exists();
    }

    /**
     * Toplu favori kontrolü (N+1 önleme)
     */
    public static function getFavoritedVideoIds(int $userId, array $videoIds): array
    {
        return static::where('user_id', $userId)
            ->whereIn('video_id', $videoIds)
            ->pluck('video_id')
            ->toArray();
    }

    /**
     * Kullanıcının toplam favori sayısı
     */
    public static function countForUser(int $userId): int
    {
        return static::where('user_id', $userId)->count();
    }

    /**
     * Videonun toplam favorilenme sayısı
     */
    public static function countForVideo(int $videoId): int
    {
        return static::where('video_id', $videoId)->count();
    }

    // Event Hooks (Opsiyonel - Cache temizleme için)

    protected static function booted()
    {
        // Favori eklendiğinde
        static::created(function ($favorite) {
            // Cache::forget("user_favorites_{$favorite->user_id}");
            // Cache::forget("video_favorites_{$favorite->video_id}");
        });

        // Favori silindiğinde
        static::deleted(function ($favorite) {
            // Cache::forget("user_favorites_{$favorite->user_id}");
            // Cache::forget("video_favorites_{$favorite->video_id}");
        });
    }
}
