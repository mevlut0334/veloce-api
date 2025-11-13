<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoDetailResource extends JsonResource
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

            // Video URLs
            'video_url' => $this->video_url, // accessor: tam URL
            'thumbnail_url' => $this->thumbnail_url, // accessor: tam URL

            // Video Properties
            'duration' => $this->duration,
            'duration_human' => $this->duration_human, // "5:30" formatında
            'orientation' => $this->orientation, // 'horizontal' veya 'vertical'
            'resolution' => $this->resolution, // örn: "1920x1080"
            'file_size' => $this->file_size, // bytes
            'file_size_human' => $this->file_size_human, // "15.5 MB" formatında

            // Status & Permissions
            'is_premium' => (bool) $this->is_premium,
            'is_active' => (bool) $this->is_active,
            'is_processed' => (bool) $this->is_processed,

            // Statistics
            'view_count' => (int) $this->view_count,
            'like_count' => (int) $this->like_count,
            'comment_count' => (int) $this->comment_count,

            // Relations
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'published_at' => $this->published_at?->toISOString(),

            // Additional Data (optional)
            'similar_videos' => VideoListResource::collection($this->whenLoaded('similarVideos')),
        ];
    }
}
