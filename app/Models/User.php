<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    const CACHE_SUBSCRIPTION_TTL = 10;
    const CACHE_STATS_TTL = 30;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'is_active',
        'is_admin',
        'last_activity_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [];

    protected $casts = [
        'password' => 'hashed',
        'is_active' => 'boolean',
        'is_admin' => 'boolean',
        'last_activity_at' => 'datetime',
    ];

    // ============================================
    // FILAMENT PANEL ERİŞİM KONTROLÜ
    // ============================================

    /**
     * Filament admin paneline erişim kontrolü
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Sadece admin ve aktif kullanıcılar girebilir
        return $this->is_admin && $this->is_active;
    }

    // ============================================
    // İLİŞKİLER - Optimize Edilmiş
    // ============================================

    // ============================================
// İLİŞKİLER - Optimize Edilmiş
// ============================================

public function userSubscriptions(): HasMany
{
    return $this->hasMany(UserSubscription::class);
}

public function subscription(): HasOne
{
    return $this->hasOne(UserSubscription::class)
        ->latest('created_at');
}

public function activeSubscription(): HasOne
{
    return $this->hasOne(UserSubscription::class)
        ->where('status', 'active')
        ->where('expires_at', '>', now());
}

public function payments(): HasMany
{
    return $this->hasMany(Payment::class);
}

public function playlists(): HasMany
{
    return $this->hasMany(UserPlaylist::class);
}

public function favorites(): BelongsToMany
{
    return $this->belongsToMany(Video::class, 'user_favorites')
        ->withTimestamps();
}

public function views(): HasMany
{
    return $this->hasMany(VideoView::class);
}

    // ============================================
    // SCOPE'LAR - Performans Optimizasyonlu
    // ============================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('is_admin', true);
    }

    public function scopeSubscribers(Builder $query): Builder
    {
        return $query->whereExists(function ($q) {
            $q->select(\DB::raw(1))
                ->from('user_subscriptions')
                ->whereColumn('user_subscriptions.user_id', 'users.id')
                ->where('status', 'active')
                ->where('expires_at', '>', now());
        });
    }

    public function scopeNonSubscribers(Builder $query): Builder
    {
        return $query->whereNotExists(function ($q) {
            $q->select(\DB::raw(1))
                ->from('user_subscriptions')
                ->whereColumn('user_subscriptions.user_id', 'users.id')
                ->where('status', 'active')
                ->where('expires_at', '>', now());
        });
    }

    public function scopeSubscribersWithData(Builder $query): Builder
    {
        return $query->join('user_subscriptions', function ($join) {
            $join->on('user_subscriptions.user_id', '=', 'users.id')
                ->where('user_subscriptions.status', 'active')
                ->where('user_subscriptions.expires_at', '>', now());
        })->select('users.*', 'user_subscriptions.expires_at as subscription_expires_at');
    }

    public function scopeRecentActivity(Builder $query, int $days = 30): Builder
    {
        return $query->where('last_activity_at', '>', now()->subDays($days));
    }

    /**
     * Bu ay abone olanlar
     */
    public function scopeSubscribedThisMonth(Builder $query): Builder
    {
        return $query->whereHas('userSubscriptions', function ($q) {
            $q->whereBetween('starts_at', [
                now()->startOfMonth(),
                now()->endOfMonth()
            ]);
        });
    }

    /**
     * Bu hafta abone olanlar
     */
    public function scopeSubscribedThisWeek(Builder $query): Builder
    {
        return $query->whereHas('userSubscriptions', function ($q) {
            $q->whereBetween('starts_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ]);
        });
    }

    /**
     * Aboneliği yakında bitecekler (N gün içinde)
     */
    public function scopeSubscriptionExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->whereHas('activeSubscription', function ($q) use ($days) {
            $q->whereBetween('expires_at', [
                now(),
                now()->addDays($days)
            ]);
        });
    }

    /**
     * Aboneliği geçen ay biten kullanıcılar
     */
    public function scopeSubscriptionExpiredLastMonth(Builder $query): Builder
    {
        return $query->whereHas('userSubscriptions', function ($q) {
            $q->where('status', 'expired')
                ->whereBetween('expires_at', [
                    now()->subMonth()->startOfMonth(),
                    now()->subMonth()->endOfMonth()
                ]);
        });
    }

    // ============================================
    // HELPER METODLAR - Cache ile Optimize Edilmiş
    // ============================================

    public function isSubscriber(): bool
    {
        if ($this->relationLoaded('activeSubscription')) {
            return $this->activeSubscription !== null;
        }

        return Cache::remember(
            "user_{$this->id}_is_subscriber",
            now()->addMinutes(self::CACHE_SUBSCRIPTION_TTL),
            fn() => $this->activeSubscription()->exists()
        );
    }

    public function hasAccessToVideo(Video $video): bool
    {
        if (!$video->is_premium) {
            return true;
        }

        return $this->isSubscriber();
    }

    public function subscriptionStatus(): string
    {
        if ($this->relationLoaded('activeSubscription')) {
            if ($this->activeSubscription) {
                return 'active';
            }

            if ($this->relationLoaded('userSubscriptions') && $this->userSubscriptions->isNotEmpty()) {
                return 'expired';
            }
        }

        return Cache::remember(
            "user_{$this->id}_subscription_status",
            now()->addMinutes(self::CACHE_SUBSCRIPTION_TTL),
            function () {
                if ($this->activeSubscription()->exists()) {
                    return 'active';
                }

                if ($this->userSubscriptions()->exists()) {
                    return 'expired';
                }

                return 'none';
            }
        );
    }

    public function subscriptionExpiry(): ?string
    {
        $activeSub = $this->relationLoaded('activeSubscription')
            ? $this->activeSubscription
            : $this->activeSubscription()->first();

        return $activeSub?->expires_at?->format('d.m.Y H:i');
    }

    public function remainingSubscriptionDays(): int
    {
        $activeSub = $this->relationLoaded('activeSubscription')
            ? $this->activeSubscription
            : $this->activeSubscription()->first();

        if (!$activeSub?->expires_at) {
            return 0;
        }

        $days = now()->diffInDays($activeSub->expires_at, false);
        return max(0, (int) $days);
    }

    /**
     * Abonelik durumu badge için renk döndürür
     */
    public function getSubscriptionBadgeColor(): string
    {
        return match($this->subscriptionStatus()) {
            'active' => 'success',
            'expired' => 'danger',
            'none' => 'gray',
            default => 'gray'
        };
    }

    /**
     * Abonelik durumu badge için metin döndürür
     */
    public function getSubscriptionBadgeText(): string
    {
        return match($this->subscriptionStatus()) {
            'active' => 'Aktif Abone',
            'expired' => 'Süresi Dolmuş',
            'none' => 'Abone Değil',
            default => 'Bilinmiyor'
        };
    }

    /**
     * Kullanıcının tüm abonelik cache'lerini temizler
     */
    public function clearSubscriptionCache(): void
    {
        Cache::forget("user_{$this->id}_is_subscriber");
        Cache::forget("user_{$this->id}_subscription_status");
        Cache::forget("user_{$this->id}_stats");
    }

    /**
     * Abonelik verilerini eager load eder
     */
    public function loadSubscriptionData(): self
    {
        return $this->load([
            'activeSubscription' => fn($q) => $q
                ->select(['id', 'user_id', 'plan_id', 'status', 'starts_at', 'expires_at'])
                ->with('plan:id,name,price,duration_days')
        ]);
    }

    /**
     * Dashboard için gerekli tüm verileri yükler
     */
    public function loadDashboardData(): self
    {
        return $this->load([
            'activeSubscription:id,user_id,plan_id,status,starts_at,expires_at',
            'playlists:id,user_id,name,created_at',
            'favorites:id,title,slug,thumbnail'
        ]);
    }

    // ============================================
    // EVENTS - Cache Yönetimi
    // ============================================

    protected static function booted(): void
    {
        static::updated(function (User $user) {
            if ($user->wasChanged(['is_active'])) {
                $user->clearSubscriptionCache();
            }
        });

        static::deleted(function (User $user) {
            $user->clearSubscriptionCache();
        });
    }

    // ============================================
    // İSTATİSTİK METODLARI - Cache'lenmiş
    // ============================================

    public function getStats(): array
    {
        return Cache::remember(
            "user_{$this->id}_stats",
            now()->addMinutes(self::CACHE_STATS_TTL),
            fn() => [
                'total_views' => $this->views()->count(),
                'total_playlists' => $this->playlists()->count(),
                'total_favorites' => $this->favorites()->count(),
                'watch_time_minutes' => $this->views()->sum('watch_duration'),
            ]
        );
    }

    public function recentViews(int $limit = 10)
    {
        return $this->views()
            ->with('video:id,title,slug,thumbnail')
            ->latest('viewed_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Kullanıcının kayıt tarihinden itibaren geçen gün sayısı
     */
    public function getMembershipDays(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Son aktivite tarihi formatlanmış
     */
    public function getLastActivityFormatted(): ?string
    {
        return $this->last_activity_at?->diffForHumans();
    }
}
