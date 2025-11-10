<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    // İlişkiler

    public function videos()
    {
        return $this->belongsToMany(Video::class, 'tag_video')
            ->withTimestamps();
    }

    // Helper metodlar

    public function getVideosCount(): int
    {
        return $this->videos()->count();
    }
}
