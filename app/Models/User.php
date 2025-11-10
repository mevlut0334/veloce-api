<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_active',
        'last_activity_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_activity_at' => 'datetime',
        ];
    }

    // İlişkiler

    public function userSubscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function subscription()
    {
        return $this->hasOne(UserSubscription::class)->latest();
    }

    public function activeSubscription()
    {
        return $this->hasOne(UserSubscription::class)
            ->where('status', 'active')
            ->where('expires_at', '>', now());
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function playlists()
    {
        return $this->hasMany(UserPlaylist::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(Video::class, 'user_favorites')
            ->withTimestamps();
    }

    public function views()
    {
        return $this->hasMany(VideoView::class);
    }

    // Scope'lar

    public function scopeSubscribers($query)
    {
        return $query->whereHas('activeSubscription');
    }

    public function scopeNonSubscribers($query)
    {
        return $query->whereDoesntHave('activeSubscription');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    // Helper metodlar

    public function isSubscriber(): bool
    {
        return $this->activeSubscription()->exists();
    }

    public function hasAccessToVideo(Video $video): bool
    {
        // Premium değilse herkes erişebilir
        if (!$video->is_premium) {
            return true;
        }

        // Premium ise sadece abone olanlar erişebilir
        return $this->isSubscriber();
    }

    public function getSubscriptionStatusAttribute(): string
    {
        if ($this->isSubscriber()) {
            return 'active';
        }

        if ($this->userSubscriptions()->exists()) {
            return 'expired';
        }

        return 'none';
    }

    public function getSubscriptionExpiryAttribute(): ?string
    {
        $activeSub = $this->activeSubscription;

        if ($activeSub) {
            return $activeSub->expires_at->format('d.m.Y H:i');
        }

        return null;
    }

    public function getRemainingSubscriptionDaysAttribute(): int
    {
        $activeSub = $this->activeSubscription;

        if ($activeSub) {
            return now()->diffInDays($activeSub->expires_at);
        }

        return 0;
    }
}
