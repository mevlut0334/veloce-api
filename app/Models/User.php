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
        'password',
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
        ];
    }

    // İlişkiler

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

    // Helper metodlar

    public function isSubscriber(): bool
    {
        return $this->activeSubscription()->exists();
    }

    public function hasAccessToVideo(Video $video): bool
    {
        if (!$video->is_premium) {
            return true;
        }

        return $this->isSubscriber();
    }
}
