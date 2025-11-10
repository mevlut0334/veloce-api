<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserSubscription extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_subscriptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'started_at',
        'expires_at',
        'status',
        'subscription_type',
        'payment_method',
        'transaction_id',
        'created_by',
        'admin_note',
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
            'subscription_plan_id' => 'integer',
            'created_by' => 'integer',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Status sabitleri
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_PENDING = 'pending';

    /**
     * Subscription type sabitleri
     */
    public const TYPE_MANUAL = 'manual';
    public const TYPE_PAID = 'paid';
    public const TYPE_TRIAL = 'trial';

    // İlişkiler

    /**
     * Abonelik sahibi kullanıcı
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Abonelik planı
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Aboneliği oluşturan admin
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scope'lar - Optimize edilmiş

    /**
     * Aktif abonelikler
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                     ->where('expires_at', '>', now());
    }

    /**
     * Süresi dolmuş abonelikler
     */
    public function scopeExpired($query)
    {
        return $query->where(function($q) {
            $q->where('status', self::STATUS_EXPIRED)
              ->orWhere('expires_at', '<=', now());
        });
    }

    /**
     * Manuel abonelikler
     */
    public function scopeManual($query)
    {
        return $query->where('subscription_type', self::TYPE_MANUAL);
    }

    /**
     * Ödeme yapılan abonelikler
     */
    public function scopePaid($query)
    {
        return $query->where('subscription_type', self::TYPE_PAID);
    }

    /**
     * Belirli kullanıcının abonelikleri
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Yakında sona erecekler (X gün kala)
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->active()
                     ->whereBetween('expires_at', [
                         now(),
                         now()->addDays($days)
                     ]);
    }

    /**
     * Belirli tarih aralığında başlayanlar
     */
    public function scopeStartedBetween($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('started_at', [$start, $end]);
    }

    /**
     * İlişkilerle birlikte yükle
     */
    public function scopeWithRelations($query)
    {
        return $query->with([
            'user:id,name,email',
            'subscriptionPlan:id,name,duration_days,price',
            'createdBy:id,name'
        ]);
    }

    // Helper metodlar - Optimize edilmiş

    /**
     * Abonelik aktif mi?
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->expires_at > now();
    }

    /**
     * Abonelik süresi dolmuş mu?
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || $this->expires_at <= now();
    }

    /**
     * Manuel abonelik mi?
     */
    public function isManual(): bool
    {
        return $this->subscription_type === self::TYPE_MANUAL;
    }

    /**
     * Ödeme yapılmış abonelik mi?
     */
    public function isPaid(): bool
    {
        return $this->subscription_type === self::TYPE_PAID;
    }

    /**
     * Kalan gün sayısı (method olarak)
     */
    public function remainingDays(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        $days = now()->diffInDays($this->expires_at, false);
        return max(0, (int) $days);
    }

    /**
     * Kalan saat sayısı
     */
    public function remainingHours(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        $hours = now()->diffInHours($this->expires_at, false);
        return max(0, (int) $hours);
    }

    /**
     * Bitiş tarihi formatlanmış
     */
    public function formattedExpiryDate(string $format = 'd.m.Y H:i'): string
    {
        return $this->expires_at->format($format);
    }

    /**
     * Abonelik süresini uzat
     */
    public function extend(int $days): bool
    {
        return $this->update([
            'expires_at' => $this->expires_at->addDays($days),
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Aboneliği yenile
     */
    public function renew(int $durationDays, ?string $transactionId = null): bool
    {
        $startDate = $this->expires_at > now()
            ? $this->expires_at
            : now();

        return $this->update([
            'started_at' => now(),
            'expires_at' => $startDate->addDays($durationDays),
            'status' => self::STATUS_ACTIVE,
            'transaction_id' => $transactionId ?? $this->transaction_id,
        ]);
    }

    /**
     * Aboneliği iptal et
     */
    public function cancel(?string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
            'admin_note' => $reason
                ? ($this->admin_note ? $this->admin_note . "\n" . $reason : $reason)
                : $this->admin_note,
        ]);
    }

    /**
     * Tek bir aboneliğin durumunu güncelle
     */
    public function updateStatus(): bool
    {
        if ($this->expires_at <= now() && $this->status !== self::STATUS_EXPIRED) {
            return $this->update(['status' => self::STATUS_EXPIRED]);
        }

        return false;
    }

    /**
     * Kullanıcının toplam abonelik süresi (gün)
     */
    public function getTotalDuration(): int
    {
        return $this->started_at->diffInDays($this->expires_at);
    }

    /**
     * Abonelik yüzdesi (ne kadar tüketildi)
     */
    public function getProgressPercentage(): float
    {
        $total = $this->getTotalDuration();
        $remaining = $this->remainingDays();

        if ($total <= 0) {
            return 100.0;
        }

        $used = $total - $remaining;
        return round(($used / $total) * 100, 2);
    }

    // Static Helper Metodlar

    /**
     * Toplu durum güncelleme (Cron job için)
     */
    public static function expireOldSubscriptions(): int
    {
        return self::where('status', self::STATUS_ACTIVE)
            ->where('expires_at', '<=', now())
            ->update(['status' => self::STATUS_EXPIRED]);
    }

    /**
     * Yakında sona erecekleri getir (bildirim için)
     */
    public static function getExpiringSoonList(int $days = 3): \Illuminate\Support\Collection
    {
        return self::expiringSoon($days)
            ->with(['user:id,name,email', 'subscriptionPlan:id,name'])
            ->get();
    }

    /**
     * Kullanıcının aktif aboneliği var mı?
     */
    public static function userHasActiveSubscription(int $userId): bool
    {
        return self::where('user_id', $userId)
            ->active()
            ->exists();
    }

    /**
     * Kullanıcının en son aboneliği
     */
    public static function getUserLatestSubscription(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->latest()
            ->first();
    }

    /**
     * İstatistikler için özet
     */
    public static function getStatistics(): array
    {
        return [
            'total' => self::count(),
            'active' => self::active()->count(),
            'expired' => self::expired()->count(),
            'manual' => self::manual()->count(),
            'paid' => self::paid()->count(),
            'expiring_soon' => self::expiringSoon(7)->count(),
        ];
    }

    /**
     * Aylık gelir hesapla (ödeme yapılan abonelikler)
     */
    public static function calculateMonthlyRevenue(int $year, int $month): float
    {
        return self::paid()
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->join('subscription_plans', 'user_subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->sum('subscription_plans.price');
    }

    // Event Hooks

    protected static function booted()
    {
        // Oluşturulduğunda otomatik aktif yap
        static::creating(function ($subscription) {
            if (!$subscription->status) {
                $subscription->status = self::STATUS_ACTIVE;
            }

            if (!$subscription->started_at) {
                $subscription->started_at = now();
            }
        });

        // Güncelleme sonrası cache temizleme
        static::saved(function ($subscription) {
            // Cache::forget("user_subscription_{$subscription->user_id}");
        });

        // Silinme sonrası cache temizleme
        static::deleted(function ($subscription) {
            // Cache::forget("user_subscription_{$subscription->user_id}");
        });
    }
}
