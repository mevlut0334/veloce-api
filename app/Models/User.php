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

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    const CACHE_SUBSCRIPTION_TTL = 10;
    const CACHE_STATS_TTL = 30;

    protected $fillable = [
        'name',
        'email',
        'password',
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
    // İLİŞKİLER - Optimize Edilmiş
    // ============================================

    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class)
            ->select(['id', 'user_id', 'plan_id', 'status', 'starts_at', 'expires_at']);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(UserSubscription::class)
            ->select(['id', 'user_id', 'plan_id', 'status', 'starts_at', 'expires_at'])
            ->latest('created_at');
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(UserSubscription::class)
            ->select(['id', 'user_id', 'plan_id', 'status', 'starts_at', 'expires_at'])
            ->where('status', 'active')
            ->where('expires_at', '>', now());
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)
            ->select(['id', 'user_id', 'amount', 'status', 'created_at']);
    }

    public function playlists(): HasMany
    {
        return $this->hasMany(UserPlaylist::class)
            ->select(['id', 'user_id', 'name', 'created_at']);
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'user_favorites')
            ->select(['videos.id', 'title', 'slug', 'thumbnail'])
            ->withTimestamps();
    }

    public function views(): HasMany
    {
        return $this->hasMany(VideoView::class)
            ->select(['id', 'user_id', 'video_id', 'viewed_at', 'watch_duration']);
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

    public function clearSubscriptionCache(): void
    {
        Cache::forget("user_{$this->id}_is_subscriber");
        Cache::forget("user_{$this->id}_subscription_status");
    }

    public function loadSubscriptionData(): self
    {
        return $this->load([
            'activeSubscription' => fn($q) => $q->select(['id', 'user_id', 'plan_id', 'status', 'expires_at'])
        ]);
    }

    public function loadDashboardData(): self
    {
        return $this->load([
            'activeSubscription:id,user_id,plan_id,status,expires_at',
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
}
