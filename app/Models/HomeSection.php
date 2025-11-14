<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class HomeSection extends Model
{
    use HasFactory;

    protected $table = 'home_sections';

    protected $fillable = [
        'title',
        'content_type',
        'content_data',
        'order',
        'is_active',
        'limit',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'content_data' => 'array',
            'order' => 'integer',
            'limit' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // Content type constants
    public const TYPE_VIDEO_IDS = 'video_ids';
    public const TYPE_CATEGORY = 'category';
    public const TYPE_TRENDING = 'trending';
    public const TYPE_RECENT = 'recent';

    public const CONTENT_TYPES = [
        self::TYPE_VIDEO_IDS,
        self::TYPE_CATEGORY,
        self::TYPE_TRENDING,
        self::TYPE_RECENT,
    ];

    // Default limits
    public const DEFAULT_LIMIT = 20;
    public const MAX_LIMIT = 50;

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Aktif section'lar
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Sıralı section'lar (order ASC)
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Sadece gerekli alanları seç (performans)
     */
    public function scopeMinimal(Builder $query): Builder
    {
        return $query->select([
            'id',
            'title',
            'content_type',
            'content_data',
            'order',
            'limit',
        ]);
    }

    /**
     * Ana sayfa için hazır scope
     */
    public function scopeForHomePage(Builder $query): Builder
    {
        return $query->active()->ordered()->minimal();
    }

    // =========================================================================
    // VIDEO GETİRME METODları
    // =========================================================================

    /**
     * Content type'a göre videoları getir
     */
    public function getVideos(): Collection
    {
        $query = Video::query()
            ->active()
            ->processed()
            ->with(['categories:id,name,slug', 'tags:id,name,slug'])
            ->select([
                'id',
                'title',
                'slug',
                'thumbnail_path',
                'duration',
                'view_count',
                'is_premium',
                'orientation',
                'created_at',
            ]);

        return match($this->content_type) {
            self::TYPE_VIDEO_IDS => $this->getVideosByIds($query),
            self::TYPE_CATEGORY => $this->getVideosByCategory($query),
            self::TYPE_TRENDING => $this->getTrendingVideos($query),
            self::TYPE_RECENT => $this->getRecentVideos($query),
            default => new Collection(),
        };
    }

    /**
     * Manuel seçilen videolar (ID sırasını koruyarak)
     */
    private function getVideosByIds(Builder $query): Collection
    {
        $videoIds = $this->content_data['video_ids'] ?? [];

        if (empty($videoIds)) {
            return new Collection();
        }

        // ID sırasını koruyarak getir - MySQL FIELD() fonksiyonu
        $idsString = implode(',', array_map('intval', $videoIds));

        return $query
            ->whereIn('id', $videoIds)
            ->orderByRaw("FIELD(id, {$idsString})")
            ->limit($this->limit ?? self::DEFAULT_LIMIT)
            ->get();
    }

    /**
     * Kategoriye göre videolar
     */
    private function getVideosByCategory(Builder $query): Collection
    {
        $categoryId = $this->content_data['category_id'] ?? null;

        if (!$categoryId) {
            return new Collection();
        }

        return $query
            ->whereHas('categories', function($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            })
            ->latest('created_at')
            ->limit($this->limit ?? self::DEFAULT_LIMIT)
            ->get();
    }

    /**
     * Trend videolar (Son X günde en çok izlenen)
     */
    private function getTrendingVideos(Builder $query): Collection
    {
        $days = $this->content_data['days'] ?? 7;

        // Son X günde oluşturulan videoları getir, view_count'a göre sırala
        return $query
            ->where('created_at', '>=', now()->subDays($days))
            ->orderByDesc('view_count')
            ->limit($this->limit ?? self::DEFAULT_LIMIT)
            ->get();
    }

    /**
     * Son eklenen videolar
     */
    private function getRecentVideos(Builder $query): Collection
    {
        return $query
            ->latest('created_at')
            ->limit($this->limit ?? self::DEFAULT_LIMIT)
            ->get();
    }

    // =========================================================================
    // HELPER METODLAR
    // =========================================================================

    /**
     * Content type'ı insan okunabilir formata çevir
     */
    public function getContentTypeLabel(): string
    {
        return match($this->content_type) {
            self::TYPE_VIDEO_IDS => 'Manuel Seçim',
            self::TYPE_CATEGORY => 'Kategori',
            self::TYPE_TRENDING => 'Trend Videolar',
            self::TYPE_RECENT => 'Son Eklenenler',
            default => 'Bilinmeyen',
        };
    }

    /**
     * Section'ın video sayısını döndür
     */
    public function getVideosCount(): int
    {
        return $this->getVideos()->count();
    }

    /**
     * Content data'dan kategori adını al (TYPE_CATEGORY için)
     */
    public function getCategoryName(): ?string
    {
        if ($this->content_type !== self::TYPE_CATEGORY) {
            return null;
        }

        $categoryId = $this->content_data['category_id'] ?? null;

        if (!$categoryId) {
            return null;
        }

        return Category::where('id', $categoryId)->value('name');
    }

    /**
     * Content type için gerekli data'nın dolu olup olmadığını kontrol et
     */
    public function hasValidContentData(): bool
    {
        return match($this->content_type) {
            self::TYPE_VIDEO_IDS => !empty($this->content_data['video_ids']),
            self::TYPE_CATEGORY => !empty($this->content_data['category_id']),
            self::TYPE_TRENDING => true, // days opsiyonel
            self::TYPE_RECENT => true, // data gerektirmiyor
            default => false,
        };
    }

    /**
     * Section'ı bir sıra yukarı taşı
     */
    public function moveUp(): bool
    {
        $previousSection = static::where('order', '<', $this->order)
            ->orderByDesc('order')
            ->first();

        if (!$previousSection) {
            return false;
        }

        // Sıraları değiştir
        $tempOrder = $this->order;
        $this->order = $previousSection->order;
        $previousSection->order = $tempOrder;

        $this->save();
        $previousSection->save();

        return true;
    }

    /**
     * Section'ı bir sıra aşağı taşı
     */
    public function moveDown(): bool
    {
        $nextSection = static::where('order', '>', $this->order)
            ->orderBy('order')
            ->first();

        if (!$nextSection) {
            return false;
        }

        // Sıraları değiştir
        $tempOrder = $this->order;
        $this->order = $nextSection->order;
        $nextSection->order = $tempOrder;

        $this->save();
        $nextSection->save();

        return true;
    }

    // =========================================================================
    // STATIC METODLAR
    // =========================================================================

    /**
     * Yeni section için bir sonraki order değerini al
     */
    public static function getNextOrder(): int
    {
        return (static::max('order') ?? 0) + 1;
    }

    /**
     * Tüm section'ların sıralamasını yeniden düzenle
     * [1, 3, 5, 7] -> [1, 2, 3, 4]
     */
    public static function reorderAll(): void
    {
        $sections = static::ordered()->get(['id', 'order']);

        $sections->each(function ($section, $index) {
            if ($section->order !== $index + 1) {
                $section->update(['order' => $index + 1]);
            }
        });
    }

    /**
     * Content type'a göre section sayısını döndür
     */
    public static function getStatistics(): array
    {
        return [
            'total' => static::count(),
            'active' => static::active()->count(),
            'video_ids' => static::where('content_type', self::TYPE_VIDEO_IDS)->count(),
            'category' => static::where('content_type', self::TYPE_CATEGORY)->count(),
            'trending' => static::where('content_type', self::TYPE_TRENDING)->count(),
            'recent' => static::where('content_type', self::TYPE_RECENT)->count(),
        ];
    }

    // =========================================================================
    // EVENT HOOKS
    // =========================================================================

    protected static function booted(): void
    {
        static::creating(function ($section) {
            // Yeni section için order değeri
            if (is_null($section->order)) {
                $section->order = static::getNextOrder();
            }

            // Default limit
            if (is_null($section->limit)) {
                $section->limit = self::DEFAULT_LIMIT;
            }

            // Default is_active
            if (is_null($section->is_active)) {
                $section->is_active = true;
            }

            // content_data boşsa boş array yap
            if (is_null($section->content_data)) {
                $section->content_data = [];
            }
        });

        static::deleting(function ($section) {
            // Section silindiğinde sıralamaları düzenle
            static::where('order', '>', $section->order)
                ->decrement('order');
        });
    }
}
