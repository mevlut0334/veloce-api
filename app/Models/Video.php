<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'video_path',
        'thumbnail_path',
        'duration',
        'orientation',
        'is_premium',
        'is_active',
        'view_count',
    ];

    protected $casts = [
        'is_premium' => 'boolean',
        'is_active' => 'boolean',
        'view_count' => 'integer',
        'duration' => 'integer',
    ];

    // İlişkiler

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_video')
            ->withTimestamps();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'tag_video')
            ->withTimestamps();
    }

    public function playlists()
    {
        return $this->belongsToMany(UserPlaylist::class, 'playlist_video')
            ->withPivot('order')
            ->withTimestamps();
    }

    public function views()
    {
        return $this->hasMany(VideoView::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'user_favorites')
            ->withTimestamps();
    }

    // Accessor metodlar - API'de tam URL döndürmek için

    public function getVideoUrlAttribute(): string
    {
        return Storage::url($this->video_path);
    }

    public function getThumbnailUrlAttribute(): string
    {
        return Storage::url($this->thumbnail_path);
    }

    // Helper metodlar

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function isFavoritedBy(User $user): bool
    {
        return $this->favoritedBy()->where('user_id', $user->id)->exists();
    }

    // Scope'lar - Sorgulama kolaylığı için

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    public function scopeFree($query)
    {
        return $query->where('is_premium', false);
    }

    public function scopeHorizontal($query)
    {
        return $query->where('orientation', 'horizontal');
    }

    public function scopeVertical($query)
    {
        return $query->where('orientation', 'vertical');
    }
}
