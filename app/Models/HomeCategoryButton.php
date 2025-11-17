<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeCategoryButton extends Model
{
    protected $fillable = [
        'category_id',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * İlişkili kategori
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scope: Aktif butonlar
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Pozisyona göre
     */
    public function scopePosition($query, int $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Scope: Sıralı
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
}
