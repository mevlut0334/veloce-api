<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPlaylist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    // İlişkiler

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function videos()
    {
        return $this->belongsToMany(Video::class, 'playlist_video')
            ->withPivot('order')
            ->orderBy('playlist_video.order')
            ->withTimestamps();
    }

    // Helper metodlar

    public function getVideosCount(): int
    {
        return $this->videos()->count();
    }

    public function addVideo(Video $video, ?int $order = null): void
    {
        if (is_null($order)) {
            $order = $this->videos()->max('playlist_video.order') + 1;
        }

        $this->videos()->attach($video->id, ['order' => $order]);
    }

    public function removeVideo(Video $video): void
    {
        $this->videos()->detach($video->id);
    }
}
