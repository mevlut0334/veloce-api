<?php

namespace App\Services;

use App\Models\HomeSlider;
use App\Repositories\Contracts\HomeSliderRepositoryInterface;
use App\Services\Contracts\HomeSliderServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HomeSliderService implements HomeSliderServiceInterface
{
    protected HomeSliderRepositoryInterface $sliderRepository;

    public function __construct(HomeSliderRepositoryInterface $sliderRepository)
    {
        $this->sliderRepository = $sliderRepository;
    }

    public function getAllSliders(): Collection
    {
        return $this->sliderRepository->all();
    }

    public function getAdminSliders(): Collection
    {
        return $this->sliderRepository->getAdminSliders();
    }

    public function getActiveSliders(): Collection
    {
        return $this->sliderRepository->getActiveSliders();
    }

    public function getSliderById(int $id): ?HomeSlider
    {
        return $this->sliderRepository->find($id);
    }

    public function createSlider(array $data, UploadedFile $imageFile): HomeSlider
    {
        DB::beginTransaction();

        try {
            // Slider kaydını oluştur
            $sliderData = [
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
                'button_text' => $data['button_text'] ?? null,
                'button_link' => $data['button_link'] ?? null,
                'video_id' => $data['video_id'] ?? null,
                'order' => $data['order'] ?? 0,
                'is_active' => $data['is_active'] ?? false,
                'image_path' => '',
            ];

            $slider = $this->sliderRepository->create($sliderData);

            // Resmi geçici klasöre yükle ve job başlat
            $tempImagePath = $imageFile->store('sliders/temp');
            $slider->dispatchImageUpload($tempImagePath);

            DB::commit();

            Log::info('Slider başarıyla oluşturuldu', [
                'slider_id' => $slider->id,
                'title' => $slider->title
            ]);

            return $slider;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Slider oluşturma hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function updateSlider(HomeSlider $slider, array $data, ?UploadedFile $imageFile = null): bool
    {
        DB::beginTransaction();

        try {
            // Slider bilgilerini güncelle
            $updateData = [
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
                'button_text' => $data['button_text'] ?? null,
                'button_link' => $data['button_link'] ?? null,
                'video_id' => $data['video_id'] ?? null,
                'order' => $data['order'] ?? $slider->order,
                'is_active' => $data['is_active'] ?? $slider->is_active,
            ];

            $this->sliderRepository->update($slider, $updateData);

            // Yeni resim yüklendiyse
            if ($imageFile) {
                // Eski resmi sil
                $this->sliderRepository->deleteImage($slider);

                // Geçici klasöre yükle ve job başlat
                $tempImagePath = $imageFile->store('sliders/temp');

                // Resim işlenirken slider'ı inaktif yap
                $this->sliderRepository->update($slider, ['is_active' => false]);

                $slider->dispatchImageUpload($tempImagePath);
            }

            DB::commit();

            Log::info('Slider başarıyla güncellendi', [
                'slider_id' => $slider->id,
                'title' => $slider->title,
                'image_updated' => $imageFile !== null
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Slider güncelleme hatası', [
                'slider_id' => $slider->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function deleteSlider(HomeSlider $slider): bool
    {
        DB::beginTransaction();

        try {
            // Resmi sil
            $this->sliderRepository->deleteImage($slider);

            // Slider kaydını sil
            $result = $this->sliderRepository->delete($slider);

            DB::commit();

            Log::info('Slider başarıyla silindi', [
                'slider_id' => $slider->id,
                'title' => $slider->title
            ]);

            return $result;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Slider silme hatası', [
                'slider_id' => $slider->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function toggleSliderStatus(HomeSlider $slider): bool
    {
        try {
            $result = $this->sliderRepository->toggleActive($slider);

            Log::info('Slider durumu değiştirildi', [
                'slider_id' => $slider->id,
                'new_status' => $slider->fresh()->is_active ? 'aktif' : 'pasif'
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Slider durum değiştirme hatası', [
                'slider_id' => $slider->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function updateSliderOrder(array $sliders): bool
    {
        try {
            $result = $this->sliderRepository->bulkUpdateOrder($sliders);

            if ($result) {
                Log::info('Slider sıralaması güncellendi', [
                    'count' => count($sliders)
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Slider sıralama hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function getSlidersWithVideo(): Collection
    {
        return $this->sliderRepository->getSlidersWithVideo();
    }
}
