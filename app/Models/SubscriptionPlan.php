<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_days',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'is_active' => 'boolean',
    ];

    // İlişkiler
    public function userSubscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Helper metodlar
    public function getFormattedPriceAttribute(): string
    {
        return number_format((float) $this->price, 2) . ' TRY';
    }

    public function getDurationInYearsAttribute(): int
    {
        return (int) ($this->duration_days / 365);
    }

    // Aktif planı getir (Sadece 1 tane olacak)
    public static function getActivePlan()
    {
        return self::where('is_active', true)->first();
    }
}
