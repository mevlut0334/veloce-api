<?php

namespace App\Filament\Resources\Videos\Pages;

use App\Filament\Resources\Videos\VideoResource;
use App\Services\Contracts\VideoServiceInterface;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateVideo extends CreateRecord
{
    protected static string $resource = VideoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Slug otomatik oluşturulacak (Model'de)
        // is_active ve is_processed false olarak başlayacak (Model'de)

        // Relations'ları ve file input'ları kaldır, bunlar handleRecordCreation'da işlenecek
        unset($data['video']);
        unset($data['thumbnail']);
        unset($data['category_ids']);
        unset($data['tag_ids']);

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $videoService = app(VideoServiceInterface::class);

        try {
            // Form'dan file'ları al
            $videoFile = $this->form->getState()['video'] ?? null;
            $thumbnailFile = $this->form->getState()['thumbnail'] ?? null;
            $categoryIds = $this->form->getState()['category_ids'] ?? [];
            $tagIds = $this->form->getState()['tag_ids'] ?? [];

            // Video oluştur (Service üzerinden)
            $video = $videoService->createVideo(
                $data,
                $videoFile,
                $thumbnailFile
            );

            // Relations'ları attach et
            if (!empty($categoryIds)) {
                $video->categories()->attach($categoryIds);
            }

            if (!empty($tagIds)) {
                $video->tags()->attach($tagIds);
            }

            return $video;

        } catch (\Exception $e) {
            Log::error('Filament Video Upload Hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Video yükleniyor! İşlem tamamlandığında aktif hale gelecek.';
    }
}
