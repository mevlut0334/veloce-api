<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Video extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'videos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
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

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [];

    /**
     * Orientation sabitleri
     */
    public const ORIENTATION_HORIZONTAL = 'horizontal';
    public const ORIENTATION_VERTICAL = 'vertical';

    // İlişkiler

    /**
     * Video kategorileri
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_video')
            ->withTimestamps();
    }

    /**
     * Video etiketleri
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'tag_video')
            ->withTimestamps();
    }

    /**
     * Video playlistleri
     */
    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(UserPlaylist::class, 'playlist_video')
            ->withPivot('order')
            ->withTimestamps();
    }

    /**
     * Video görüntülenmeleri
     */
    public function views(): HasMany
    {
        return $this->hasMany(VideoView::class);
    }

    /**
     * Favoriye ekleyen kullanıcılar
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_favorites')
            ->withTimestamps();
    }

    // Accessor metodlar - Optimize edilmiş

    /**
     * Video URL'i
     */
    public function videoUrl(): string
    {
        return Storage::url($this->video_path);
    }

    /**
     * Thumbnail URL'i
     */
    public function thumbnailUrl(): string
    {
        return Storage::url($this->thumbnail_path);
    }

    /**
     * Formatlanmış süre (HH:MM:SS)
     */
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

    // Scope'lar - Optimize edilmiş

    /**
     * Aktif videolar
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Premium videolar
     */
    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    /**
     * Ücretsiz videolar
     */
    public function scopeFree($query)
    {
        return $query->where('is_premium', false);
    }

    /**
     * Yatay videolar
     */
    public function scopeHorizontal($query)
    {
        return $query->where('orientation', self::ORIENTATION_HORIZONTAL);
    }

    /**
     * Dikey videolar
     */
    public function scopeVertical($query)
    {
        return $query->where('orientation', self::ORIENTATION_VERTICAL);
    }

    /**
     * Popüler videolar (görüntülenme sayısına göre)
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderByDesc('view_count')->limit($limit);
    }

    /**
     * En çok favorilenler
     */
    public function scopeMostFavorited($query, int $limit = 10)
    {
        return $query->orderByDesc('favorite_count')->limit($limit);
    }

    /**
     * Son eklenenler
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }

    /**
     * Belirli kategorideki videolar
     */
    public function scopeInCategory($query, int $categoryId)
    {
        return $query->whereHas('categories', function($q) use ($categoryId) {
            $q->where('categories.id', $categoryId);
        });
    }

    /**
     * Belirli etikete sahip videolar
     */
    public function scopeWithTag($query, int $tagId)
    {
        return $query->whereHas('tags', function($q) use ($tagId) {
            $q->where('tags.id', $tagId);
        });
    }

    /**
     * Arama (başlık ve açıklama)
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /**
     * Slug'a göre bul
     */
    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    /**
     * İlişkilerle birlikte yükle (N+1 önleme)
     */
    public function scopeWithRelations($query)
    {
        return $query->with([
            'categories:id,name,slug',
            'tags:id,name,slug'
        ]);
    }

    /**
     * Sayılarla birlikte yükle
     */
    public function scopeWithCounts($query)
    {
        return $query->withCount(['views', 'favoritedBy']);
    }

    // Helper metodlar - Optimize edilmiş

    /**
     * Görüntülenme sayısını artır (Tek sorgu, timestamp güncelleme yok)
     */
    public function incrementViewCount(): bool
    {
        return $this->increment('view_count', 1, ['updated_at' => $this->updated_at]);
    }

    /**
     * Favori sayısını artır
     */
    public function incrementFavoriteCount(): bool
    {
        return $this->increment('favorite_count', 1, ['updated_at' => $this->updated_at]);
    }

    /**
     * Favori sayısını azalt
     */
    public function decrementFavoriteCount(): bool
    {
        if ($this->favorite_count > 0) {
            return $this->decrement('favorite_count', 1, ['updated_at' => $this->updated_at]);
        }
        return false;
    }

    /**
     * Kullanıcı bu videoyu favorilemiş mi? (Optimize)
     */
    public function isFavoritedBy(?int $userId = null): bool
    {
        if (!$userId) {
            return false;
        }

        // İlişki yüklenmişse collection'dan kontrol et
        if ($this->relationLoaded('favoritedBy')) {
            return $this->favoritedBy->contains('id', $userId);
        }

        return $this->favoritedBy()->where('user_id', $userId)->exists();
    }

    /**
     * Kullanıcının bu videoya erişimi var mı?
     */
    public function canBeAccessedBy(?User $user = null): bool
    {
        // Aktif değilse erişim yok
        if (!$this->is_active) {
            return false;
        }

        // Ücretsizse herkes erişebilir
        if (!$this->is_premium) {
            return true;
        }

        // Premium ise kullanıcı aboneliği kontrol et
        return $user && $user->isSubscriber();
    }

    /**
     * Video slug'ı oluştur
     */
    public function generateSlug(): string
    {
        $slug = Str::slug($this->title);
        $count = static::where('slug', 'like', "{$slug}%")->count();

        return $count > 0 ? "{$slug}-{$count}" : $slug;
    }

    /**
     * Kategori ekle
     */
    public function addCategory(int $categoryId): void
    {
        $this->categories()->syncWithoutDetaching([$categoryId]);
    }

    /**
     * Etiket ekle
     */
    public function addTag(int $tagId): void
    {
        $this->tags()->syncWithoutDetaching([$tagId]);
    }

    /**
     * Toplu kategori güncelle
     */
    public function syncCategories(array $categoryIds): void
    {
        $this->categories()->sync($categoryIds);
    }

    /**
     * Toplu etiket güncelle
     */
    public function syncTags(array $tagIds): void
    {
        $this->tags()->sync($tagIds);
    }

    /**
     * Video dosyalarını sil
     */
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

    // Static Helper Metodlar

    /**
     * Slug'a göre bul veya hata fırlat
     */
    public static function findBySlugOrFail(string $slug): self
    {
        return static::bySlug($slug)->firstOrFail();
    }

    /**
     * Popüler videoları getir (cache'lenebilir)
     */
    public static function getPopularVideos(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->popular($limit)
            ->withRelations()
            ->get();
    }

    /**
     * İstatistikler
     */
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

    /**
     * Toplu görüntülenme güncelleme (Cron job için)
     */
    public static function updateViewCounts(array $videoIdViewCounts): void
    {
        foreach ($videoIdViewCounts as $videoId => $count) {
            static::where('id', $videoId)
                ->increment('view_count', $count, ['updated_at' => \DB::raw('updated_at')]);
        }
    }

    /**
     * Benzer videoları bul
     */
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

    // Event Hooks

    protected static function booted()
    {
        // Oluşturulurken slug oluştur
        static::creating(function ($video) {
            if (!$video->slug) {
                $video->slug = $video->generateSlug();
            }

            // Varsayılan değerler
            if (is_null($video->view_count)) {
                $video->view_count = 0;
            }

            if (is_null($video->favorite_count)) {
                $video->favorite_count = 0;
            }
        });

        // Güncellenirken başlık değiştiyse slug'ı güncelle
        static::updating(function ($video) {
            if ($video->isDirty('title') && !$video->isDirty('slug')) {
                $video->slug = $video->generateSlug();
            }
        });

        // Silinirken dosyaları da sil
        static::deleting(function ($video) {
            $video->deleteFiles();

            // Pivot kayıtları temizle
            $video->categories()->detach();
            $video->tags()->detach();
            $video->playlists()->detach();
            $video->favoritedBy()->detach();
        });
    }
}
