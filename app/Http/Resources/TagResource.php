<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
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
            'video_count' => $this->when(isset($this->videos_count), $this->videos_count), // video sayısı (opsiyonel)
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
