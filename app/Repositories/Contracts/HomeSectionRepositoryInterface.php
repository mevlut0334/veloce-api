<?php

namespace App\Repositories\Contracts;

use App\Models\HomeSection;
use Illuminate\Database\Eloquent\Collection;

interface HomeSectionRepositoryInterface
{
    /**
     * Tüm section'ları getir (sıralı)
     */
    public function all(): Collection;

    /**
     * Aktif section'ları getir (sıralı)
     */
    public function getAllActive(): Collection;

    /**
     * Ana sayfa için section'ları getir (aktif + sıralı + minimal)
     */
    public function getForHomePage(): Collection;

    /**
     * ID ile section bul
     */
    public function findById(int $id): ?HomeSection;

    /**
     * ID ile section bul veya hata fırlat
     */
    public function findByIdOrFail(int $id): HomeSection;

    /**
     * Yeni section oluştur
     */
    public function create(array $data): HomeSection;

    /**
     * Section güncelle
     */
    public function update(int $id, array $data): HomeSection;

    /**
     * Section sil
     */
    public function delete(int $id): bool;

    /**
     * Section'ın aktif/pasif durumunu değiştir
     */
    public function toggleActive(int $id): HomeSection;

    /**
     * Toplu sıralama güncelleme
     * @param array $orderData [['id' => 1, 'order' => 1], ['id' => 2, 'order' => 2], ...]
     */
    public function reorder(array $orderData): bool;

    /**
     * Section'ı bir sıra yukarı taşı
     */
    public function moveUp(int $id): bool;

    /**
     * Section'ı bir sıra aşağı taşı
     */
    public function moveDown(int $id): bool;

    /**
     * Content type'a göre section'ları getir
     */
    public function getByContentType(string $contentType): Collection;

    /**
     * Section istatistiklerini getir
     */
    public function getStatistics(): array;

    /**
     * Belirli bir kategoriye ait section var mı kontrol et
     */
    public function hasCategorySection(int $categoryId): bool;

    /**
     * Tüm section sıralamalarını normalize et
     */
    public function normalizeOrders(): void;
}
