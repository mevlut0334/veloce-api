<?php

namespace App\Repositories\Contracts;

use App\Models\HomeSlider;
use Illuminate\Database\Eloquent\Collection;

interface HomeSliderRepositoryInterface
{
    /**
     * Tüm slider'ları getir
     */
    public function all(): Collection;

    /**
     * ID ile slider bul
     */
    public function find(int $id): ?HomeSlider;

    /**
     * ID ile slider bul veya hata fırlat
     */
    public function findOrFail(int $id): HomeSlider;

    /**
     * Yeni slider oluştur
     */
    public function create(array $data): HomeSlider;

    /**
     * Slider güncelle
     */
    public function update(HomeSlider $slider, array $data): bool;

    /**
     * Slider sil
     */
    public function delete(HomeSlider $slider): bool;

    /**
     * Slider aktif/inaktif durumunu değiştir
     */
    public function toggleActive(HomeSlider $slider): bool;

    /**
     * Aktif slider'ları getir (ana sayfa için)
     */
    public function getActiveSliders(): Collection;

    /**
     * Admin için slider'ları getir
     */
    public function getAdminSliders(): Collection;

    /**
     * Slider sırasını güncelle
     */
    public function updateOrder(int $sliderId, int $order): bool;

    /**
     * Toplu sıralama güncelle
     */
    public function bulkUpdateOrder(array $sliders): bool;

    /**
     * Video olan slider'ları getir
     */
    public function getSlidersWithVideo(): Collection;

    /**
     * Slider resim dosyasını sil
     */
    public function deleteImage(HomeSlider $slider): bool;

    /**
     * İlişkilerle birlikte yükle
     */
    public function withRelations(HomeSlider $slider): HomeSlider;

    /**
     * Cache'i temizle
     */
    public function clearCache(): void;
}
