<?php

namespace App\Filament\Resources\HomeSliders\Pages;

use App\Filament\Resources\HomeSliders\HomeSliderResource;
use App\Models\HomeSlider;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateHomeSlider extends CreateRecord
{
    protected static string $resource = HomeSliderResource::class;

    /**
     * Form verisini kaydetmeden önce dönüştür
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Order değeri yoksa otomatik ata
        if (!isset($data['order'])) {
            $data['order'] = HomeSlider::max('order') + 1;
        }

        // Görsel yüklendiyse geçici klasörden taşı
        if (isset($data['image']) && $data['image']) {
            $tempPath = $data['image'];

            // Geçici dosyayı sliders/temp klasörüne taşı
            $fileName = 'slider_' . uniqid() . '_' . time() . '.jpg';
            $newTempPath = 'sliders/temp/' . $fileName;

            Storage::disk('public')->move($tempPath, $newTempPath);

            $data['image_path'] = $newTempPath;
            unset($data['image']);
        }

        return $data;
    }

    /**
     * Kayıt oluşturulduktan sonra image processing job'unu başlat
     */
    protected function afterCreate(): void
    {
        $slider = $this->record;

        if ($slider->image_path) {
            // Job'u başlat
            $slider->dispatchImageUpload($slider->image_path);
        }
    }
}
