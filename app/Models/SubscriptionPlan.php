<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_days',
        'is_active',
        'features',
        'order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'is_active' => 'boolean',
        'features' => 'array',
        'order' => 'integer',
    ];

    protected static function booted()
    {
        // Cache temizleme
        static::saved(function ($plan) {
            static::clearAllCache();
        });

        static::deleted(function ($plan) {
            static::clearAllCache();
        });
    }

    // =========================================================================
    // İLİŞKİLER
    // =========================================================================

    public function userSubscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Aktif abonelikleri getir (withCount için)
     */
    public function activeSubscriptions()
    {
        return $this->hasMany(UserSubscription::class)
            ->where('status', 'active')
            ->where('expires_at', '>', now());
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Sadece aktif planlar
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
        return $query->orderBy('order', 'asc')->orderBy('price', 'asc');
    }

    /**
     * Abonelik sayıları ile getir (N+1 önleme)
     */
    public function scopeWithSubscriptionCounts(Builder $query): Builder
    {
        return $query->withCount([
            'userSubscriptions',
            'activeSubscriptions',
            'payments'
        ]);
    }

    /**
     * Ödeme sayıları ile getir
     */
    public function scopeWithPaymentStats(Builder $query): Builder
    {
        return $query->withCount('payments')
            ->withSum('payments', 'amount');
    }

    /**
     * API için optimize edilmiş
     */
    public function scopeForApi(Builder $query): Builder
    {
        return $query->select([
            'id', 'name', 'description', 'price',
            'duration_days', 'features', 'order'
        ])
        ->active()
        ->ordered();
    }

    /**
     * Admin için optimize edilmiş
     */
    public function scopeForAdmin(Builder $query): Builder
    {
        return $query->withSubscriptionCounts()->ordered();
    }

    // =========================================================================
    // STATIC METODLAR (CACHE'Lİ)
    // =========================================================================

    /**
     * Aktif planı getir (Cache'li)
     * NOT: Sadece 1 aktif plan varsayımı ile
     */
    public static function getActivePlan()
    {
        return Cache::remember(
            'subscription_plan_active',
            now()->addHours(24),
            fn() => static::active()->first()
        );
    }

    /**
     * Tüm aktif planları getir (Cache'li)
     */
    public static function getActivePlans(): Collection
    {
        return Cache::remember(
            'subscription_plans_active',
            now()->addHours(12),
            fn() => static::forApi()->get()
        );
    }

    /**
     * Admin için planları getir (istatistiklerle)
     */
    public static function getAdminPlans(): Collection
    {
        return Cache::remember(
            'subscription_plans_admin',
            now()->addMinutes(30),
            fn() => static::forAdmin()->get()
        );
    }

    /**
     * Plan istatistikleri (Dashboard için)
     */
    public static function getPlanStats(): array
    {
        return Cache::remember(
            'subscription_plan_stats',
            now()->addMinutes(30),
            function () {
                $plans = static::withSubscriptionCounts()
                    ->withPaymentStats()
                    ->get();

                return [
                    'total_plans' => $plans->count(),
                    'active_plans' => $plans->where('is_active', true)->count(),
                    'total_subscriptions' => $plans->sum('user_subscriptions_count'),
                    'active_subscriptions' => $plans->sum('active_subscriptions_count'),
                    'total_revenue' => (float) $plans->sum('payments_sum_amount'),
                    'plans' => $plans->map(function ($plan) {
                        return [
                            'id' => $plan->id,
                            'name' => $plan->name,
                            'price' => $plan->getFormattedPrice(),
                            'subscriptions' => $plan->active_subscriptions_count,
                            'revenue' => (float) $plan->payments_sum_amount,
                        ];
                    })->toArray()
                ];
            }
        );
    }

    // =========================================================================
    // HELPER METODLAR
    // =========================================================================

    /**
     * Formatlanmış fiyat
     */
    public function getFormattedPrice(): string
    {
        $price = $this->price ?? 0;
        return number_format((float) $price, 2) . ' TRY';
    }

    /**
     * Yıl cinsinden süre
     */
    public function getDurationInYears(): int
    {
        return (int) ($this->duration_days / 365);
    }

    /**
     * Ay cinsinden süre
     */
    public function getDurationInMonths(): int
    {
        return (int) ($this->duration_days / 30);
    }

    /**
     * İnsan okunabilir süre
     */
    public function getFormattedDuration(): string
    {
        if ($this->duration_days >= 365) {
            $years = $this->getDurationInYears();
            return $years . ' ' . ($years > 1 ? 'Yıl' : 'Yıl');
        }

        if ($this->duration_days >= 30) {
            $months = $this->getDurationInMonths();
            return $months . ' ' . ($months > 1 ? 'Ay' : 'Ay');
        }

        return $this->duration_days . ' Gün';
    }

    /**
     * Günlük fiyat hesapla
     */
    public function getDailyPrice(): float
    {
        if ($this->duration_days <= 0) {
            return 0;
        }
        return (float) $this->price / $this->duration_days;
    }

    /**
     * Aktif abonelik sayısı
     */
    public function getActiveSubscriptionsCount(): int
    {
        // Eğer withCount ile yüklenmişse
        if (isset($this->active_subscriptions_count)) {
            return $this->active_subscriptions_count;
        }

        return Cache::remember(
            "plan_{$this->id}_active_subs_count",
            now()->addMinutes(10),
            fn() => $this->activeSubscriptions()->count()
        );
    }

    /**
     * Toplam gelir
     */
    public function getTotalRevenue(): float
    {
        // Eğer withSum ile yüklenmişse
        if (isset($this->payments_sum_amount)) {
            return (float) $this->payments_sum_amount;
        }

        return Cache::remember(
            "plan_{$this->id}_total_revenue",
            now()->addMinutes(10),
            fn() => (float) $this->payments()
                ->where('status', 'completed')
                ->sum('amount')
        );
    }

    /**
     * Planın popülerlik skoru (aktif abonelik / toplam abonelik)
     */
    public function getPopularityScore(): float
    {
        $total = $this->userSubscriptions()->count();
        if ($total === 0) {
            return 0;
        }

        $active = $this->getActiveSubscriptionsCount();
        return round(($active / $total) * 100, 2);
    }

    // =========================================================================
    // CACHE YÖNETİMİ
    // =========================================================================

    /**
     * Plan'a ait cache'leri temizle
     */
    public function clearCache(): void
    {
        Cache::forget("plan_{$this->id}_active_subs_count");
        Cache::forget("plan_{$this->id}_total_revenue");
    }

    /**
     * Tüm plan cache'lerini temizle
     */
    public static function clearAllCache(): void
    {
        $keys = [
            'subscription_plan_active',
            'subscription_plans_active',
            'subscription_plans_admin',
            'subscription_plan_stats',
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}
