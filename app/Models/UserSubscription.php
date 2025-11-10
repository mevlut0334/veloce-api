<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserSubscription extends Model
{
    use HasFactory;

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

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // İlişkiler
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scope'lar
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where(function($q) {
            $q->where('status', 'expired')
              ->orWhere('expires_at', '<=', now());
        });
    }

    public function scopeManual($query)
    {
        return $query->where('subscription_type', 'manual');
    }

    public function scopePaid($query)
    {
        return $query->where('subscription_type', 'paid');
    }

    // Helper metodlar
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at > now();
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->expires_at <= now();
    }

    public function isManual(): bool
    {
        return $this->subscription_type === 'manual';
    }

    public function getRemainingDaysAttribute(): int
    {
        if ($this->isExpired()) {
            return 0;
        }
        return now()->diffInDays($this->expires_at);
    }

    public function getFormattedExpiryDateAttribute(): string
    {
        return $this->expires_at->format('d.m.Y H:i');
    }

    // Aboneliği güncelle (expired olanları)
    public function updateStatus(): void
    {
        if ($this->expires_at <= now() && $this->status !== 'expired') {
            $this->update(['status' => 'expired']);
        }
    }
}
