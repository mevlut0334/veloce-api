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
            'description' => $this->description,
            'is_active' => $this->is_active,
            'url' => $this->getUrl(),

            // Video sayıları (eğer withCount ile yüklenmişse)
            'videos_count' => $this->when(
                isset($this->videos_count),
                $this->videos_count
            ),
            'active_videos_count' => $this->when(
                isset($this->active_videos_count),
                $this->active_videos_count
            ),

            // İlişkili videolar (eğer yüklenmişse)
            'videos' => VideoResource::collection(
                $this->whenLoaded('activeVideos')
            ),

            // Zaman bilgileri
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
