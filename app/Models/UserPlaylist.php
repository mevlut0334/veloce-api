<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class UserPlaylist extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_playlists';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_public',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'is_public' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // İlişkiler

    /**
     * Playlist sahibi kullanıcı
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Playlist'teki videolar
     */
    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'playlist_video')
            ->withPivot('order')
            ->orderBy('playlist_video.order')
            ->withTimestamps();
    }

    // Scope'lar

    /**
     * Belirli kullanıcının playlistleri
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Public playlistler
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Private playlistler
     */
    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    /**
     * Video sayısıyla birlikte
     */
    public function scopeWithVideoCount($query)
    {
        return $query->withCount('videos');
    }

    /**
     * Son güncellenenler
     */
    public function scopeRecentlyUpdated($query, int $limit = 10)
    {
        return $query->orderByDesc('updated_at')->limit($limit);
    }

    // Helper metodlar - Optimize edilmiş

    /**
     * Video sayısını al (Cache'lenebilir)
     */
    public function getVideosCount(): int
    {
        // İlişki yüklenmişse count() kullan (sorgu atmaz)
        if ($this->relationLoaded('videos')) {
            return $this->videos->count();
        }

        // withCount ile yüklenmişse
        if (isset($this->videos_count)) {
            return $this->videos_count;
        }

        // Son çare: Sorgu at
        return $this->videos()->count();
    }

    /**
     * Video ekle - Optimize edilmiş
     */
    public function addVideo(Video $video, ?int $order = null): bool
    {
        // Zaten ekli mi kontrol et
        if ($this->videos()->where('video_id', $video->id)->exists()) {
            return false;
        }

        if (is_null($order)) {
            // Tek sorguda max değeri al
            $order = (int) $this->videos()->max('playlist_video.order') + 1;
        }

        $this->videos()->attach($video->id, [
            'order' => $order,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Playlist'in updated_at'ini güncelle
        $this->touch();

        return true;
    }

    /**
     * Video çıkar
     */
    public function removeVideo(Video $video): bool
    {
        $detached = $this->videos()->detach($video->id);

        if ($detached > 0) {
            $this->touch();
            return true;
        }

        return false;
    }

    /**
     * Toplu video ekle
     */
    public function addVideos(array $videoIds): int
    {
        $maxOrder = (int) $this->videos()->max('playlist_video.order');
        $data = [];
        $now = now();

        foreach ($videoIds as $index => $videoId) {
            $data[$videoId] = [
                'order' => $maxOrder + $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // syncWithoutDetaching ile mevcut videoları koruyarak ekle
        $this->videos()->syncWithoutDetaching($data);
        $this->touch();

        return count($videoIds);
    }

    /**
     * Video sırasını güncelle
     */
    public function reorderVideo(int $videoId, int $newOrder): bool
    {
        return (bool) $this->videos()
            ->updateExistingPivot($videoId, [
                'order' => $newOrder,
                'updated_at' => now(),
            ]);
    }

    /**
     * Tüm videoları yeniden sırala
     */
    public function reorderVideos(array $videoIdsInOrder): void
    {
        $updates = [];
        $now = now();

        foreach ($videoIdsInOrder as $order => $videoId) {
            $updates[] = [
                'playlist_id' => $this->id,
                'video_id' => $videoId,
                'order' => $order + 1,
                'updated_at' => $now,
            ];
        }

        DB::table('playlist_video')
            ->where('playlist_id', $this->id)
            ->delete();

        DB::table('playlist_video')->insert($updates);

        $this->touch();
    }

    /**
     * Playlist'in ilk videosunu al
     */
    public function firstVideo(): ?Video
    {
        return $this->videos()
            ->orderBy('playlist_video.order')
            ->first();
    }

    /**
     * Video playlist'te var mı kontrol et
     */
    public function hasVideo(int $videoId): bool
    {
        // İlişki yüklenmişse collection'dan kontrol et
        if ($this->relationLoaded('videos')) {
            return $this->videos->contains('id', $videoId);
        }

        return $this->videos()->where('video_id', $videoId)->exists();
    }

    /**
     * Playlist'i temizle
     */
    public function clearVideos(): void
    {
        $this->videos()->detach();
        $this->touch();
    }

    /**
     * Playlist'i kopyala
     */
    public function duplicate(int $newUserId, ?string $newName = null): self
    {
        $newPlaylist = self::create([
            'user_id' => $newUserId,
            'name' => $newName ?? ($this->name . ' (Kopya)'),
            'description' => $this->description,
            'is_public' => false, // Kopyalar varsayılan olarak private
        ]);

        // Videoları kopyala
        $videos = $this->videos()->get(['video_id', 'order']);
        $data = [];
        $now = now();

        foreach ($videos as $video) {
            $data[$video->id] = [
                'order' => $video->pivot->order,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $newPlaylist->videos()->attach($data);

        return $newPlaylist;
    }

    // Static Helper Metodlar

    /**
     * Kullanıcının toplam playlist sayısı
     */
    public static function countForUser(int $userId): int
    {
        return static::where('user_id', $userId)->count();
    }

    /**
     * Kullanıcı erişim kontrolü
     */
    public function canAccess(int $userId): bool
    {
        return $this->is_public || $this->user_id === $userId;
    }

    // Event Hooks

    protected static function booted()
    {
        // Playlist silinirken videoları da temizle
        static::deleting(function ($playlist) {
            $playlist->videos()->detach();
        });
    }
}
