<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeSliderResource extends JsonResource
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
            'subtitle' => $this->subtitle,
            'button_text' => $this->button_text,
            'button_link' => $this->button_link,
            'image_path' => $this->image_path,
            'image_url' => $this->image_url, // Model'deki accessor
            'video_id' => $this->video_id,
            'is_active' => $this->is_active,
            'order' => $this->order,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            // İlişkili video bilgisi (varsa)
            'video' => $this->whenLoaded('video', function () {
                return [
                    'id' => $this->video->id,
                    'title' => $this->video->title,
                    'video_url' => $this->video->video_url,
                    'thumbnail_url' => $this->video->thumbnail_url,
                ];
            }),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
        ];
    }
}
