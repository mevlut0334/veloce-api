<?php

// =============================================================================
// MODEL: App\Models\HomeSection.php
// =============================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomeSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content_type',
        'content_data',
        'order',
        'is_active',
        'limit',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'content_data' => 'array',
        'order' => 'integer',
        'limit' => 'integer',
    ];

    // Content type constants
    const TYPE_VIDEO_IDS = 'video_ids';
    const TYPE_CATEGORY = 'category';
    const TYPE_TRENDING = 'trending';
    const TYPE_RECENT = 'recent';

    protected static function booted()
    {
        // Cache temizleme
        static::saved(function ($section) {
            $section->clearCache();
            static::clearAllSectionsCache();
        });

        static::deleted(function ($section) {
            $section->clearCache();
            static::clearAllSectionsCache();
        });
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

    /**
     * Sadece gerekli alanları seç
     */
    public function scopeMinimal(Builder $query): Builder
    {
        return $query->select(['id', 'title', 'content_type', 'content_data', 'limit']);
    }

    /**
     * Ana sayfa için optimize edilmiş scope
     */
    public function scopeForHomePage(Builder $query): Builder
    {
        return $query->active()->ordered()->minimal();
    }

    // =========================================================================
    // VIDEO GETİRME - OPTIMIZE EDİLMİŞ
    // =========================================================================

    /**
     * İçeriği getir (Cache'li ve optimize edilmiş)
     */
    public function getVideos(): Collection
    {
        // Cache kullan (5 dakika)
        return Cache::remember(
            $this->getCacheKey('videos'),
            now()->addMinutes(5),
            fn() => $this->fetchVideos()
        );
    }

    /**
     * Videoları getir (Cache olmadan)
     */
    protected function fetchVideos(): Collection
    {
        $query = Video::query()
            ->active()
            ->with(['categories:id,name,slug']) // Eager loading
            ->select([
                'id', 'title', 'slug', 'thumbnail',
                'duration', 'views_count', 'created_at'
            ]); // Sadece gerekli alanlar

        switch ($this->content_type) {
            case self::TYPE_VIDEO_IDS:
                return $this->getVideosByIds($query);

            case self::TYPE_CATEGORY:
                return $this->getVideosByCategory($query);

            case self::TYPE_TRENDING:
                return $this->getTrendingVideos($query);

            case self::TYPE_RECENT:
                return $this->getRecentVideos($query);

            default:
                return new Collection();
        }
    }

    /**
     * Manuel seçilen videolar (ID sırasını koruyarak)
     */
    protected function getVideosByIds(Builder $query): Collection
    {
        $videoIds = $this->content_data['video_ids'] ?? [];

        if (empty($videoIds)) {
            return new Collection();
        }

        // ID sırasını koruyarak getir - Optimize edilmiş
        $orderCase = collect($videoIds)
            ->map(fn($id, $index) => "WHEN {$id} THEN {$index}")
            ->implode(' ');

        return $query
            ->whereIn('id', $videoIds)
            ->orderByRaw("CASE id {$orderCase} END")
            ->limit($this->limit)
            ->get();
    }

    /**
     * Kategoriye göre videolar
     */
    protected function getVideosByCategory(Builder $query): Collection
    {
        $categoryId = $this->content_data['category_id'] ?? null;

        if (!$categoryId) {
            return new Collection();
        }

        // JOIN kullanarak daha performanslı
        return $query
            ->join('category_video', 'videos.id', '=', 'category_video.video_id')
            ->where('category_video.category_id', $categoryId)
            ->latest('videos.created_at')
            ->limit($this->limit)
            ->get();
    }

    /**
     * Trend videolar (Son 7 günde en çok izlenen)
     */
    protected function getTrendingVideos(Builder $query): Collection
    {
        $days = $this->content_data['days'] ?? 7;

        // Subquery ile optimize edilmiş trending hesaplama
        return $query
            ->addSelect([
                'recent_views_count' => DB::table('video_views')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('video_id', 'videos.id')
                    ->where('viewed_at', '>=', now()->subDays($days))
            ])
            ->orderByDesc('recent_views_count')
            ->orderByDesc('views_count') // Fallback sıralama
            ->limit($this->limit)
            ->get();
    }

    /**
     * Son eklenen videolar
     */
    protected function getRecentVideos(Builder $query): Collection
    {
        return $query
            ->latest('created_at')
            ->limit($this->limit)
            ->get();
    }

    // =========================================================================
    // CACHE YÖNETİMİ
    // =========================================================================

    /**
     * Cache key oluştur
     */
    private function getCacheKey(string $suffix): string
    {
        // content_data değişikliklerini cache key'e dahil et
        $dataHash = md5(json_encode($this->content_data));
        return "home_section_{$this->id}_{$this->content_type}_{$dataHash}_{$suffix}";
    }

    /**
     * Section cache'ini temizle
     */
    public function clearCache(): void
    {
        Cache::forget($this->getCacheKey('videos'));
    }

    /**
     * Tüm section cache'lerini temizle
     */
    public static function clearAllSectionsCache(): void
    {
        Cache::forget('home_sections_all');
        Cache::forget('home_sections_with_videos');
    }

    // =========================================================================
    // HELPER METODLAR
    // =========================================================================

    /**
     * Ana sayfa için tüm section'ları videolarıyla birlikte getir
     * Bu metod controller'da kullanılmalı
     */
    public static function getHomeSectionsWithVideos(): Collection
    {
        return Cache::remember(
            'home_sections_with_videos',
            now()->addMinutes(10),
            function () {
                $sections = static::forHomePage()->get();

                // Her section için videoları yükle
                return $sections->map(function ($section) {
                    $section->videos = $section->getVideos();
                    return $section;
                })->filter(function ($section) {
                    // Boş section'ları filtrele
                    return $section->videos->isNotEmpty();
                });
            }
        );
    }

    /**
     * Section'ın video sayısını döndür (Cache'li)
     */
    public function getVideosCount(): int
    {
        return $this->getVideos()->count();
    }

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
}

// =============================================================================
// MIGRATION: database/migrations/xxxx_create_home_sections_table.php
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->enum('content_type', ['video_ids', 'category', 'trending', 'recent'])
                  ->default('recent');
            $table->json('content_data')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('limit')->default(10);
            $table->timestamps();

            // Performans için indexler
            $table->index(['is_active', 'order']); // Composite index
            $table->index('content_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_sections');
    }
};

// =============================================================================
// KULLANIM ÖRNEKLERİ
// =============================================================================

/*

// ❌ YANLIŞ - N+1 ve cache yok
$sections = HomeSection::active()->ordered()->get();
foreach ($sections as $section) {
    $videos = $section->getVideos(); // Her seferinde sorgu atıyor!
}

// ✅ DOĞRU - Tek cache'li çağrı
$sections = HomeSection::getHomeSectionsWithVideos();
foreach ($sections as $section) {
    foreach ($section->videos as $video) {
        echo $video->title;
    }
}

// ✅ Controller kullanımı
class HomeController extends Controller
{
    public function index()
    {
        $sections = HomeSection::getHomeSectionsWithVideos();

        return view('home', compact('sections'));
    }
}

// ✅ Blade kullanımı
@foreach($sections as $section)
    <div class="section">
        <h2>{{ $section->title }}</h2>
        <div class="videos">
            @foreach($section->videos as $video)
                <div class="video-card">
                    <img src="{{ $video->thumbnail }}">
                    <h3>{{ $video->title }}</h3>
                </div>
            @endforeach
        </div>
    </div>
@endforeach

// ✅ Cache temizleme (Admin panelinde section güncellendiğinde)
$section->update($data);
$section->clearCache(); // Otomatik çalışıyor ama manuel de çağırabilirsiniz

// ✅ Tüm cache'i temizleme
HomeSection::clearAllSectionsCache();

// ✅ API endpoint
Route::get('/api/home', function () {
    return response()->json([
        'sections' => HomeSection::getHomeSectionsWithVideos()
    ]);
});

*/
