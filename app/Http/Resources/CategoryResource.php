<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon, // ikon adı veya URL (opsiyonel)
            'color' => $this->color, // hex renk kodu (opsiyonel, UI için)
            'is_active' => (bool) $this->is_active,
            'video_count' => $this->when(isset($this->videos_count), $this->videos_count), // video sayısı (opsiyonel)
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
