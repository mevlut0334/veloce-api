<?php

namespace App\Services\Contracts;

use App\Models\HomeSlider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

interface HomeSliderServiceInterface
{
    /**
     * Tüm slider'ları listele
     */
    public function getAllSliders(): Collection;

    /**
     * Admin için slider'ları listele
     */
    public function getAdminSliders(): Collection;

    /**
     * Ana sayfa için aktif slider'ları getir
     */
    public function getActiveSliders(): Collection;

    /**
     * Slider detayını getir
     */
    public function getSliderById(int $id): ?HomeSlider;

    /**
     * Yeni slider oluştur
     */
    public function createSlider(array $data, UploadedFile $imageFile): HomeSlider;

    /**
     * Slider güncelle
     */
    public function updateSlider(HomeSlider $slider, array $data, ?UploadedFile $imageFile = null): bool;

    /**
     * Slider sil
     */
    public function deleteSlider(HomeSlider $slider): bool;

    /**
     * Slider aktif/inaktif durumunu değiştir
     */
    public function toggleSliderStatus(HomeSlider $slider): bool;

    /**
     * Slider sıralamasını güncelle
     */
    public function updateSliderOrder(array $sliders): bool;

    /**
     * Video olan slider'ları getir
     */
    public function getSlidersWithVideo(): Collection;
}
