<?php

// =============================================================================
// MODEL: App\Models\HomeSlider.php
// =============================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

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

    // Accessor'ları append etme (performans için)
    protected $appends = [];

    protected static function booted()
    {
        // Cache temizleme
        static::saved(function ($slider) {
            static::clearAllCache();
        });

        static::deleted(function ($slider) {
            static::clearAllCache();
        });
    }

    // =========================================================================
    // İLİŞKİLER
    // =========================================================================

    /**
     * İlişkili video
     */
    public function video()
    {
        return $this->belongsTo(Video::class);
    }

    // =========================================================================
    // ACCESSORS & MUTATORS
    // =========================================================================

    /**
     * Resim URL'sini döndür
     * NOT: Accessor append edilmediği için performans kaybı yok
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        // CDN varsa kullan
        if (config('filesystems.cdn_url')) {
            return config('filesystems.cdn_url') . '/' . $this->image_path;
        }

        return Storage::url($this->image_path);
    }

    /**
     * Tam resim yolu (optimizasyon için)
     */
    public function getFullImageUrl(): ?string
    {
        return $this->image_url;
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Sadece aktif slider'lar
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
        return $query->orderBy('order', 'asc');
    }

    /**
     * Video ile birlikte getir (Eager loading)
     */
    public function scopeWithVideo(Builder $query): Builder
    {
        return $query->with([
            'video' => function ($query) {
                $query->select([
                    'id', 'title', 'slug', 'thumbnail',
                    'duration', 'views_count'
                ])->active();
            }
        ]);
    }

    /**
     * Sadece video olan slider'lar
     */
    public function scopeHasVideo(Builder $query): Builder
    {
        return $query->whereNotNull('video_id')
            ->whereHas('video', function ($query) {
                $query->active();
            });
    }

    /**
     * Ana sayfa için optimize edilmiş scope
     */
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

    /**
     * Admin için optimize edilmiş scope
     */
    public function scopeForAdmin(Builder $query): Builder
    {
        return $query->ordered()->withVideo();
    }

    // =========================================================================
    // STATIC METODLAR (CACHE'Lİ)
    // =========================================================================

    /**
     * Ana sayfa slider'larını cache'li olarak getir
     * Bu metod controller'da kullanılmalı
     */
    public static function getHomeSliders(): Collection
    {
        return Cache::remember(
            'home_sliders_active',
            now()->addMinutes(30), // 30 dakika cache
            function () {
                return static::forHomePage()->get()->map(function ($slider) {
                    // Image URL'yi önceden hesapla
                    $slider->cached_image_url = $slider->image_url;
                    return $slider;
                });
            }
        );
    }

    /**
     * Admin panel için slider'ları getir (Cache'siz)
     */
    public static function getAdminSliders(): Collection
    {
        return static::forAdmin()->get();
    }

    // =========================================================================
    // CACHE YÖNETİMİ
    // =========================================================================

    /**
     * Tüm slider cache'lerini temizle
     */
    public static function clearAllCache(): void
    {
        Cache::forget('home_sliders_active');
    }

    // =========================================================================
    // HELPER METODLAR
    // =========================================================================

    /**
     * Slider'ın video linkini döndür
     */
    public function getVideoUrl(): ?string
    {
        if (!$this->video) {
            return null;
        }

        return route('video.show', $this->video->slug);
    }

    /**
     * Slider'ın aktif ve geçerli olup olmadığını kontrol et
     */
    public function isValid(): bool
    {
        return $this->is_active &&
               $this->image_path &&
               Storage::exists($this->image_path);
    }

    /**
     * Resim dosyasını sil
     */
    public function deleteImage(): bool
    {
        if ($this->image_path && Storage::exists($this->image_path)) {
            return Storage::delete($this->image_path);
        }
        return false;
    }
}

// =============================================================================
// MIGRATION: database/migrations/xxxx_create_home_sliders_table.php
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_sliders', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('image_path', 255);
            $table->foreignId('video_id')->nullable()
                  ->constrained('videos')
                  ->nullOnDelete(); // Video silinirse null yap
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Performans için indexler
            $table->index(['is_active', 'order']); // Composite index (en önemli)
            $table->index('video_id'); // Foreign key için
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_sliders');
    }
};

// =============================================================================
// KULLANIM ÖRNEKLERİ
// =============================================================================

/*

// ❌ YANLIŞ - N+1 problemi ve cache yok
$sliders = HomeSlider::active()->ordered()->get();
foreach ($sliders as $slider) {
    echo $slider->video->title; // N+1!
    echo $slider->image_url; // Her seferinde Storage::url çağrısı
}

// ✅ DOĞRU - Cache'li ve optimize edilmiş
$sliders = HomeSlider::getHomeSliders();
foreach ($sliders as $slider) {
    echo $slider->video?->title; // Eager loaded
    echo $slider->cached_image_url; // Önceden hesaplanmış
}

// ✅ Controller kullanımı
class HomeController extends Controller
{
    public function index()
    {
        $sliders = HomeSlider::getHomeSliders();

        return view('home', compact('sliders'));
    }
}

// ✅ Blade kullanımı
<div class="slider">
    @foreach($sliders as $slider)
        <div class="slide">
            <img src="{{ $slider->cached_image_url }}" alt="{{ $slider->title }}">
            <div class="content">
                <h2>{{ $slider->title }}</h2>
                <p>{{ $slider->description }}</p>
                @if($slider->video)
                    <a href="{{ $slider->getVideoUrl() }}">
                        İzle
                    </a>
                @endif
            </div>
        </div>
    @endforeach
</div>

// ✅ Admin Controller
class AdminSliderController extends Controller
{
    public function index()
    {
        $sliders = HomeSlider::getAdminSliders();
        return view('admin.sliders.index', compact('sliders'));
    }

    public function store(Request $request)
    {
        $slider = HomeSlider::create($validated);
        // Cache otomatik temizleniyor (booted event)

        return redirect()->back();
    }

    public function destroy(HomeSlider $slider)
    {
        $slider->deleteImage(); // Resmi sil
        $slider->delete(); // Cache otomatik temizleniyor

        return redirect()->back();
    }
}

// ✅ API endpoint
Route::get('/api/sliders', function () {
    return response()->json([
        'sliders' => HomeSlider::getHomeSliders()
    ]);
});

// ✅ Manuel cache temizleme (gerekirse)
HomeSlider::clearAllCache();

// ✅ Sadece video olan slider'lar
$slidersWithVideo = HomeSlider::active()
    ->ordered()
    ->hasVideo()
    ->withVideo()
    ->get();

// ✅ Geçerli slider'ları kontrol et
$sliders = HomeSlider::all();
$validSliders = $sliders->filter(fn($slider) => $slider->isValid());

*/
