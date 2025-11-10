<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // İlişkiler

    public function videos()
    {
        return $this->belongsToMany(Video::class, 'category_video')
            ->withTimestamps();
    }

    // Helper metodlar

    public function getActiveVideosCount(): int
    {
        return $this->videos()->where('is_active', true)->count();
    }

    // Scope'lar

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
