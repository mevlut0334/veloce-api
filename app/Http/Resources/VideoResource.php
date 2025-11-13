<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
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
            'description' => $this->description,

            // URLs
            'video_url' => $this->video_url,
            'thumbnail_url' => $this->thumbnail_url,

            // Properties
            'duration' => $this->duration,
            'duration_human' => $this->duration_human,
            'orientation' => $this->orientation,
            'resolution' => $this->resolution,

            // Status
            'is_premium' => (bool) $this->is_premium,
            'is_active' => (bool) $this->is_active,
            'is_processed' => (bool) $this->is_processed,

            // Stats
            'view_count' => (int) $this->view_count,

            // Relations (optional)
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
