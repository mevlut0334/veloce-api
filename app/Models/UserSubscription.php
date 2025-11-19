<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UserSubscription extends Model
{
    use HasFactory;

    protected $table = 'user_subscriptions';

    protected $fillable = [
        'user_id',
        'plan_id',
        'starts_at',
        'expires_at',
        'status',
        'subscription_type',
        'payment_method',
        'transaction_id',
        'created_by',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'plan_id' => 'integer',
            'created_by' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    // Status sabitleri
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_PENDING = 'pending';

    // Subscription type sabitleri
    public const TYPE_MANUAL = 'manual';
    public const TYPE_PAID = 'paid';
    public const TYPE_TRIAL = 'trial';

    // =========================================================================
    // İLİŞKİLER
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                     ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where(function($q) {
            $q->where('status', self::STATUS_EXPIRED)
              ->orWhere('expires_at', '<=', now());
        });
    }

    public function scopeManual($query)
    {
        return $query->where('subscription_type', self::TYPE_MANUAL);
    }

    public function scopePaid($query)
    {
        return $query->where('subscription_type', self::TYPE_PAID);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->active()
                     ->whereBetween('expires_at', [
                         now(),
                         now()->addDays($days)
                     ]);
    }

    public function scopeStartedBetween($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('starts_at', [$start, $end]);
    }

    public function scopeWithRelations($query)
    {
        return $query->with([
            'user:id,name,email,avatar',
            'plan:id,name,duration_days,price',
            'createdBy:id,name'
        ]);
    }

    // =========================================================================
    // HELPER METODLAR
    // =========================================================================

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->expires_at > now();
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || $this->expires_at <= now();
    }

    public function isManual(): bool
    {
        return $this->subscription_type === self::TYPE_MANUAL;
    }

    public function isPaid(): bool
    {
        return $this->subscription_type === self::TYPE_PAID;
    }

    public function remainingDays(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        $days = now()->diffInDays($this->expires_at, false);
        return max(0, (int) $days);
    }

    public function remainingHours(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        $hours = now()->diffInHours($this->expires_at, false);
        return max(0, (int) $hours);
    }

    public function formattedExpiryDate(string $format = 'd.m.Y H:i'): string
    {
        return $this->expires_at->format($format);
    }

    public function extend(int $days): bool
    {
        $oldExpiry = $this->expires_at->copy();
        $newExpiry = $oldExpiry->copy()->addDays($days);

        Log::info('Extending subscription', [
            'subscription_id' => $this->id,
            'old_expiry' => $oldExpiry->toDateTimeString(),
            'new_expiry' => $newExpiry->toDateTimeString(),
            'days' => $days,
        ]);

        $updated = $this->update([
            'expires_at' => $newExpiry,
            'status' => self::STATUS_ACTIVE,
        ]);

        if ($updated) {
            $this->clearUserCache();
            $this->refresh();

            Log::info('Subscription extended successfully', [
                'subscription_id' => $this->id,
                'verified_expiry' => $this->expires_at->toDateTimeString(),
            ]);
        } else {
            Log::error('Subscription extend failed', [
                'subscription_id' => $this->id,
            ]);
        }

        return $updated;
    }

    public function renew(int $durationDays, ?string $transactionId = null): bool
    {
        $startDate = $this->expires_at > now()
            ? $this->expires_at
            : now();

        $newExpiry = $startDate->copy()->addDays($durationDays);

        Log::info('Renewing subscription', [
            'subscription_id' => $this->id,
            'start_date' => now()->toDateTimeString(),
            'new_expiry' => $newExpiry->toDateTimeString(),
            'duration_days' => $durationDays,
        ]);

        $updated = $this->update([
            'starts_at' => now(),
            'expires_at' => $newExpiry,
            'status' => self::STATUS_ACTIVE,
            'transaction_id' => $transactionId ?? $this->transaction_id,
        ]);

        if ($updated) {
            $this->clearUserCache();
            $this->refresh();

            Log::info('Subscription renewed successfully', [
                'subscription_id' => $this->id,
                'verified_expiry' => $this->expires_at->toDateTimeString(),
            ]);
        } else {
            Log::error('Subscription renew failed', [
                'subscription_id' => $this->id,
            ]);
        }

        return $updated;
    }

    public function cancel(?string $reason = null): bool
    {
        $adminNote = $this->admin_note ?? '';
        if ($reason) {
            $note = "[" . now()->format('d.m.Y H:i') . "] İptal: " . $reason;
            $adminNote = $adminNote ? $adminNote . "\n" . $note : $note;
        }

        Log::info('Cancelling subscription', [
            'subscription_id' => $this->id,
            'old_status' => $this->status,
            'reason' => $reason,
        ]);

        $updated = $this->update([
            'status' => self::STATUS_CANCELLED,
            'admin_note' => $adminNote,
        ]);

        if ($updated) {
            $this->clearUserCache();
            $this->refresh();

            Log::info('Subscription cancelled successfully', [
                'subscription_id' => $this->id,
                'verified_status' => $this->status,
            ]);
        } else {
            Log::error('Subscription cancel failed', [
                'subscription_id' => $this->id,
            ]);
        }

        return $updated;
    }

    public function updateStatus(): bool
    {
        if ($this->expires_at <= now() && $this->status !== self::STATUS_EXPIRED) {
            $updated = $this->update(['status' => self::STATUS_EXPIRED]);

            if ($updated) {
                $this->clearUserCache();
                $this->refresh();
            }

            return $updated;
        }

        return false;
    }

    public function getTotalDuration(): int
    {
        return $this->starts_at->diffInDays($this->expires_at);
    }

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

    /**
     * Kullanıcının abonelik cache'lerini temizle
     */
    protected function clearUserCache(): void
    {
        if ($this->user_id) {
            Cache::forget("user_{$this->user_id}_is_subscriber");
            Cache::forget("user_{$this->user_id}_subscription_status");
            Cache::forget("user_{$this->user_id}_active_subscription");
        }

        if ($this->user) {
            $this->user->clearSubscriptionCache();
        }
    }

    // =========================================================================
    // STATIC METODLAR
    // =========================================================================

    public static function expireOldSubscriptions(): int
    {
        $expiredCount = self::where('status', self::STATUS_ACTIVE)
            ->where('expires_at', '<=', now())
            ->count();

        if ($expiredCount > 0) {
            self::where('status', self::STATUS_ACTIVE)
                ->where('expires_at', '<=', now())
                ->update(['status' => self::STATUS_EXPIRED]);

            // Etkilenen kullanıcıların cache'lerini temizle
            $userIds = self::where('status', self::STATUS_EXPIRED)
                ->where('expires_at', '<=', now())
                ->pluck('user_id')
                ->unique();

            foreach ($userIds as $userId) {
                Cache::forget("user_{$userId}_is_subscriber");
                Cache::forget("user_{$userId}_subscription_status");
                Cache::forget("user_{$userId}_active_subscription");
            }
        }

        return $expiredCount;
    }

    public static function getExpiringSoonList(int $days = 3): \Illuminate\Support\Collection
    {
        return self::expiringSoon($days)
            ->with(['user:id,name,email', 'plan:id,name'])
            ->get();
    }

    public static function userHasActiveSubscription(int $userId): bool
    {
        return self::where('user_id', $userId)
            ->active()
            ->exists();
    }

    public static function getUserLatestSubscription(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->latest()
            ->first();
    }

    public static function getStatistics(): array
    {
        return Cache::remember('user_subscriptions_stats', now()->addMinutes(30), function () {
            return [
                'total' => self::count(),
                'active' => self::active()->count(),
                'expired' => self::expired()->count(),
                'manual' => self::manual()->count(),
                'paid' => self::paid()->count(),
                'expiring_soon_7days' => self::expiringSoon(7)->count(),
                'expiring_soon_30days' => self::expiringSoon(30)->count(),
            ];
        });
    }

    public static function calculateMonthlyRevenue(int $year, int $month): float
    {
        return self::paid()
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->join('subscription_plans', 'user_subscriptions.plan_id', '=', 'subscription_plans.id')
            ->sum('subscription_plans.price');
    }

    // =========================================================================
    // EVENTS
    // =========================================================================

    protected static function booted()
    {
        static::creating(function ($subscription) {
            if (!$subscription->status) {
                $subscription->status = self::STATUS_ACTIVE;
            }

            if (!$subscription->starts_at) {
                $subscription->starts_at = now();
            }
        });

        static::saved(function ($subscription) {
            $subscription->clearUserCache();
            Cache::forget('user_subscriptions_stats');
        });

        static::deleted(function ($subscription) {
            $subscription->clearUserCache();
            Cache::forget('user_subscriptions_stats');
        });
    }
}
