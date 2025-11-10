<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content_type',
        'content_data',
        'order',
        'is_active',
        'limit',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'content_data' => 'array', // JSON olarak saklayacağız
    ];

    // Scope'lar
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    // İçeriği getiren metod
    public function getVideos()
    {
        $query = Video::query()->active();

        switch ($this->content_type) {
            case 'video_ids':
                // Manuel olarak seçilen video ID'leri
                if (!empty($this->content_data['video_ids'])) {
                    $videoIds = $this->content_data['video_ids'];
                    // ID sırasını koruyarak getir
                    $query->whereIn('id', $videoIds)
                          ->orderByRaw('FIELD(id, ' . implode(',', $videoIds) . ')');
                }
                break;

            case 'category':
                // Belirli bir kategorideki videolar
                if (!empty($this->content_data['category_id'])) {
                    $query->whereHas('categories', function ($q) {
                        $q->where('categories.id', $this->content_data['category_id']);
                    })->latest();
                }
                break;

            case 'trending':
                // Trend videolar (son 7 gün içinde en çok izlenenler)
                $query->withCount(['views' => function ($q) {
                    $q->where('viewed_at', '>=', now()->subDays(7));
                }])->orderByDesc('views_count');
                break;

            case 'recent':
                // Son eklenen videolar
                $query->latest();
                break;
        }

        return $query->limit($this->limit)->get();
    }
}
