<?php

namespace App\Services\Contracts;

use App\Models\HomeSlider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

interface HomeSliderServiceInterface
{
    /**
     * Tüm slider'ları listele (sıralı)
     *
     * @return Collection<HomeSlider>
     */
    public function getAllSliders(): Collection;

    /**
     * Admin paneli için slider'ları listele
     * (Tüm slider'lar, aktif/inaktif durum gösterimi ile)
     *
     * @return Collection<HomeSlider>
     */
    public function getAdminSliders(): Collection;

    /**
     * Ana sayfa için aktif slider'ları getir
     * (Cache'lenmiş, sadece aktif olan slider'lar)
     *
     * @return Collection<HomeSlider>
     */
    public function getActiveSliders(): Collection;

    /**
     * ID ile slider detayını getir
     *
     * @param int $id
     * @return HomeSlider|null
     */
    public function getSliderById(int $id): ?HomeSlider;

    /**
     * Yeni slider oluştur
     * (Resim job ile asenkron işlenir)
     *
     * @param array $data Slider verileri (title, subtitle, button_text, button_link, video_id, order, is_active)
     * @param UploadedFile $imageFile Yüklenecek resim dosyası
     * @return HomeSlider
     * @throws \Exception
     */
    public function createSlider(array $data, UploadedFile $imageFile): HomeSlider;

    /**
     * Slider güncelle
     * (Yeni resim varsa job ile asenkron işlenir)
     *
     * @param HomeSlider $slider Güncellenecek slider
     * @param array $data Güncellenecek veriler
     * @param UploadedFile|null $imageFile Yeni resim dosyası (opsiyonel)
     * @return bool
     * @throws \Exception
     */
    public function updateSlider(HomeSlider $slider, array $data, ?UploadedFile $imageFile = null): bool;

    /**
     * Slider sil
     * (Resim dosyası da silinir)
     *
     * @param HomeSlider $slider Silinecek slider
     * @return bool
     * @throws \Exception
     */
    public function deleteSlider(HomeSlider $slider): bool;

    /**
     * Slider aktif/inaktif durumunu değiştir
     * (Toggle işlemi)
     *
     * @param HomeSlider $slider
     * @return bool
     * @throws \Exception
     */
    public function toggleSliderStatus(HomeSlider $slider): bool;

    /**
     * Slider sıralamasını toplu güncelle
     * (Sürükle-bırak için)
     *
     * @param array $sliders [['id' => 1, 'order' => 0], ['id' => 2, 'order' => 1], ...]
     * @return bool
     * @throws \Exception
     */
    public function updateSliderOrder(array $sliders): bool;

    /**
     * Video ilişkisi olan slider'ları getir
     *
     * @return Collection<HomeSlider>
     */
    public function getSlidersWithVideo(): Collection;
}
