<?php

namespace App\Filament\Resources\Videos\Pages;

use App\Filament\Resources\Videos\VideoResource;
use App\Services\Contracts\VideoServiceInterface;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditVideo extends EditRecord
{
    protected static string $resource = VideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Relations'ı doldur
        $data['category_ids'] = $this->record->categories->pluck('id')->toArray();
        $data['tag_ids'] = $this->record->tags->pluck('id')->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Relations'ları ve file input'ları kaldır, bunlar handleRecordUpdate'de işlenecek
        unset($data['video']);
        unset($data['thumbnail']);
        unset($data['category_ids']);
        unset($data['tag_ids']);

        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $videoService = app(VideoServiceInterface::class);

        try {
            // Form'dan file'ları al
            $videoFile = $this->form->getState()['video'] ?? null;
            $thumbnailFile = $this->form->getState()['thumbnail'] ?? null;
            $categoryIds = $this->form->getState()['category_ids'] ?? [];
            $tagIds = $this->form->getState()['tag_ids'] ?? [];

            // Video güncelle (Service üzerinden)
            $videoService->updateVideo(
                $record,
                $data,
                $videoFile,
                $thumbnailFile
            );

            // Relations'ları sync et
            $record->categories()->sync($categoryIds);
            $record->tags()->sync($tagIds);

            // Record'u fresh olarak döndür
            return $record->fresh();

        } catch (\Exception $e) {
            Log::error('Filament Video Update Hatası', [
                'video_id' => $record->id,
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

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Video güncellendi!';
    }
}
