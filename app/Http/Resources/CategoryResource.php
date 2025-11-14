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
            'icon' => $this->icon,
            'order' => $this->order,
            'is_active' => (bool) $this->is_active,
            'show_on_home' => (bool) $this->show_on_home,

            // Video sayıları (eager load edilmişse)
            'videos_count' => $this->when(isset($this->videos_count), $this->videos_count),
            'active_videos_count' => $this->when(isset($this->active_videos_count), $this->active_videos_count),

            // İlişkili videolar (varsa)
            'videos' => VideoListResource::collection($this->whenLoaded('videos')),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
