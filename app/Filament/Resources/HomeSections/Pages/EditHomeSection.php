<?php

namespace App\Filament\Resources\HomeSections\Pages;

use App\Filament\Resources\HomeSections\HomeSectionResource;
use App\Models\HomeSection;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHomeSection extends EditRecord
{
    protected static string $resource = HomeSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Form verisini yüklerken content_data'yı düz field'lara çıkar
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $contentType = $data['content_type'] ?? null;
        $contentData = $data['content_data'] ?? [];

        // Content type'a göre content_data'dan veriyi çıkar
        switch ($contentType) {
            case HomeSection::TYPE_VIDEO_IDS:
                if (isset($contentData['video_ids'])) {
                    $data['video_ids'] = $contentData['video_ids'];
                }
                break;

            case HomeSection::TYPE_CATEGORY:
                if (isset($contentData['category_id'])) {
                    $data['category_id'] = $contentData['category_id'];
                }
                break;

            case HomeSection::TYPE_TRENDING:
                if (isset($contentData['days'])) {
                    $data['days'] = $contentData['days'];
                }
                break;
        }

        return $data;
    }

    /**
     * Form verisini kaydetmeden önce düz field'ları content_data'ya paketle
     */
    protected function mutateFormDataBeforeSave(array $data): array
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
