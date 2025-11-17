<?php

namespace App\Filament\Resources\HomeSliders\Pages;

use App\Filament\Resources\HomeSliders\HomeSliderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditHomeSlider extends EditRecord
{
    protected static string $resource = HomeSliderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Form verisini yüklerken image_path'i image field'ına çıkar
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Edit sayfasında mevcut görseli göster
        if (isset($data['image_path']) && $data['image_path']) {
            $data['image'] = $data['image_path'];
        }

        return $data;
    }

    /**
     * Form verisini kaydetmeden önce dönüştür
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Yeni görsel yüklendiyse
        if (isset($data['image']) && $data['image'] !== $this->record->image_path) {
            $tempPath = $data['image'];

            // Eski görseli sil
            if ($this->record->image_path && Storage::disk('public')->exists($this->record->image_path)) {
                Storage::disk('public')->delete($this->record->image_path);
            }

            // Geçici dosyayı sliders/temp klasörüne taşı
            $fileName = 'slider_' . uniqid() . '_' . time() . '.jpg';
            $newTempPath = 'sliders/temp/' . $fileName;

            Storage::disk('public')->move($tempPath, $newTempPath);

            $data['image_path'] = $newTempPath;
        }

        // image field'ını kaldır
        unset($data['image']);

        return $data;
    }

    /**
     * Kayıt güncellendikten sonra image processing job'unu başlat
     */
    protected function afterSave(): void
    {
        $slider = $this->record;

        // Yeni görsel yüklendiyse job'u başlat
        if ($slider->image_path && str_contains($slider->image_path, 'temp')) {
            $slider->dispatchImageUpload($slider->image_path);
        }
    }
}
