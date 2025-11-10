<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoView extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_id',
        'user_id',
        'ip_address',
        'watch_duration',
        'is_completed',
        'viewed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'viewed_at' => 'datetime',
    ];

    // İlişkiler
    public function video()
    {
        return $this->belongsTo(Video::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope'lar - İstatistikler için
    public function scopeToday($query)
    {
        return $query->whereDate('viewed_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('viewed_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('viewed_at', now()->month)
                     ->whereYear('viewed_at', now()->year);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('viewed_at', now()->year);
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }
}
