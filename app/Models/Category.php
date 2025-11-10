<?php

// =============================================================================
// MODEL: App\Models\Category.php
// =============================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    // Varsayılan sıralama
    protected static function booted()
    {
        // Cache temizleme
        static::saved(function ($category) {
            $category->clearCache();
        });

        static::deleted(function ($category) {
            $category->clearCache();
        });
    }

    // =========================================================================
    // İLİŞKİLER
    // =========================================================================

    /**
     * Kategoriye ait tüm videolar
     */
    public function videos()
    {
        return $this->belongsToMany(Video::class, 'category_video')
            ->withTimestamps();
    }

    /**
     * Kategoriye ait sadece aktif videolar (Optimize edilmiş ilişki)
     */
    public function activeVideos()
    {
        return $this->belongsToMany(Video::class, 'category_video')
            ->where('videos.is_active', true)
            ->withTimestamps();
    }

    // =========================================================================
    // HELPER METODLAR
    // =========================================================================

    /**
     * Aktif video sayısını döndürür
     * NOT: Liste görünümlerinde mutlaka withActiveVideosCount() scope'u ile kullanın
     */
    public function getActiveVideosCount(): int
    {
        // Eğer withCount ile yüklenmişse, direkt kullan (N+1 önleme)
        if (isset($this->active_videos_count)) {
            return $this->active_videos_count;
        }

        // Cache ile performans artışı (5 dakika)
        return Cache::remember(
            $this->getCacheKey('active_videos_count'),
            now()->addMinutes(5),
            fn() => $this->videos()->where('is_active', true)->count()
        );
    }

    /**
     * Toplam video sayısını döndürür
     */
    public function getVideosCount(): int
    {
        if (isset($this->videos_count)) {
            return $this->videos_count;
        }

        return Cache::remember(
            $this->getCacheKey('videos_count'),
            now()->addMinutes(5),
            fn() => $this->videos()->count()
        );
    }

    /**
     * Cache key oluştur
     */
    private function getCacheKey(string $suffix): string
    {
        return "category_{$this->id}_{$suffix}";
    }

    /**
     * Kategoriye ait cache'leri temizle
     */
    public function clearCache(): void
    {
        Cache::forget($this->getCacheKey('active_videos_count'));
        Cache::forget($this->getCacheKey('videos_count'));
    }

    // =========================================================================
    // SCOPES (N+1 Önleme & Performans İyileştirme)
    // =========================================================================

    /**
     * Sadece aktif kategoriler
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Sıralı getir
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /**
     * Toplam video sayısı ile getir (N+1 önleme)
     * Kullanım: Category::withVideosCount()->get()
     */
    public function scopeWithVideosCount(Builder $query): Builder
    {
        return $query->withCount('videos');
    }

    /**
     * Aktif video sayısı ile getir (N+1 önleme) - ÖNERİLEN
     * Kullanım: Category::withActiveVideosCount()->get()
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
     * Kullanım: Category::withVideos()->get()
     */
    public function scopeWithVideos(Builder $query): Builder
    {
        return $query->with('videos');
    }

    /**
     * Sadece aktif videolar ile birlikte getir (Eager loading)
     * Kullanım: Category::withActiveVideos()->get()
     */
    public function scopeWithActiveVideos(Builder $query): Builder
    {
        return $query->with(['activeVideos']);
    }

    /**
     * En az bir aktif videosu olan kategoriler
     */
    public function scopeHasActiveVideos(Builder $query): Builder
    {
        return $query->whereHas('videos', function ($query) {
            $query->where('is_active', true);
        });
    }

    /**
     * API için optimize edilmiş scope
     * Sadece gerekli alanları ve ilişkileri yükler
     */
    public function scopeForApi(Builder $query): Builder
    {
        return $query->select(['id', 'name', 'slug', 'icon', 'order'])
            ->active()
            ->ordered()
            ->withActiveVideosCount();
    }

    /**
     * Admin paneli için optimize edilmiş scope
     */
    public function scopeForAdmin(Builder $query): Builder
    {
        return $query->withAllCounts()->ordered();
    }
}

// =============================================================================
// MIGRATION: database/migrations/xxxx_create_categories_table.php
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // Uzunluk limiti performans için
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('icon', 255)->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Performans için indexler
            $table->index('is_active');
            $table->index('order');
            $table->index(['is_active', 'order']); // Composite index (en çok kullanılan sorgu)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};

// =============================================================================
// KULLANIM ÖRNEKLERİ
// =============================================================================

/*

// ❌ YANLIŞ - N+1 Problemi
$categories = Category::all();
foreach ($categories as $category) {
    echo $category->getActiveVideosCount(); // Her kategori için ayrı sorgu!
}

// ✅ DOĞRU - Optimize Edilmiş
$categories = Category::withActiveVideosCount()->get();
foreach ($categories as $category) {
    echo $category->active_videos_count; // Tek sorguda alındı!
}

// ✅ API için
$categories = Category::forApi()->get();

// ✅ Admin panel için
$categories = Category::forAdmin()->paginate(20);

// ✅ Aktif kategoriler, aktif video sayıları ile
$categories = Category::active()
    ->withActiveVideosCount()
    ->ordered()
    ->get();

// ✅ En az bir aktif videosu olan kategoriler
$categories = Category::hasActiveVideos()
    ->withActiveVideosCount()
    ->get();

// ✅ Videolar ile birlikte (Eager loading)
$category = Category::with('activeVideos')->find(1);

// ✅ Tek kategori için
$category = Category::withActiveVideosCount()->find(1);
echo $category->active_videos_count;

// ✅ Sadece belirli alanları çek (Select optimizasyonu)
$categories = Category::select(['id', 'name', 'slug'])
    ->active()
    ->get();

*/
