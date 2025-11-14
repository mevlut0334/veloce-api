<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeSectionResource extends JsonResource
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
            'content_type' => $this->content_type,
            'content_type_label' => $this->getContentTypeLabel(),
            'content_data' => $this->content_data,
            'order' => $this->order,
            'limit' => $this->limit,
            'is_active' => $this->is_active,
            'has_valid_content_data' => $this->hasValidContentData(),
            'videos_count' => $this->when(
                $request->has('include_videos_count'),
                fn() => $this->getVideosCount()
            ),
            'category_name' => $this->when(
                $this->content_type === 'category',
                fn() => $this->getCategoryName()
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
