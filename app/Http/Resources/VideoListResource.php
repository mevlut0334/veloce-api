<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'thumbnail_url' => $this->thumbnail_url, // accessor kullanacağız
            'duration' => $this->duration,
            'duration_human' => $this->duration_human, // accessor: "5:30" formatında
            'orientation' => $this->orientation, // 'horizontal' veya 'vertical'
            'is_premium' => (bool) $this->is_premium,
            'is_active' => (bool) $this->is_active,
            'view_count' => (int) $this->view_count,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
