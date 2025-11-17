<?php

namespace App\Filament\Resources\HomeSections\Pages;

use App\Filament\Resources\HomeSections\HomeSectionResource;
use App\Models\HomeSection;
use Filament\Resources\Pages\CreateRecord;

class CreateHomeSection extends CreateRecord
{
    protected static string $resource = HomeSectionResource::class;

    /**
     * Form verisini kaydetmeden önce dönüştür
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $contentType = $data['content_type'] ?? null;
        $contentData = [];

        // Content type'a göre ilgili veriyi content_data'ya taşı
        switch ($contentType) {
            case HomeSection::TYPE_VIDEO_IDS:
                if (isset($data['video_ids'])) {
                    $contentData['video_ids'] = $data['video_ids'];
                    unset($data['video_ids']);
                }
                break;

            case HomeSection::TYPE_CATEGORY:
                if (isset($data['category_id'])) {
                    $contentData['category_id'] = $data['category_id'];
                    unset($data['category_id']);
                }
                break;

            case HomeSection::TYPE_TRENDING:
                if (isset($data['days'])) {
                    $contentData['days'] = $data['days'];
                    unset($data['days']);
                }
                break;

            case HomeSection::TYPE_RECENT:
                // Son eklenenler için ekstra veri gerekmez
                break;
        }

        // content_data'yı kaydet
        $data['content_data'] = $contentData;

        // Kullanılmayan alanları temizle
        unset($data['video_ids'], $data['category_id'], $data['days']);

        return $data;
    }
}
