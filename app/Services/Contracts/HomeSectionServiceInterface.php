<?php

namespace App\Services\Contracts;

use App\Models\HomeSection;
use Illuminate\Database\Eloquent\Collection;

interface HomeSectionServiceInterface
{
    /**
     * Tüm section'ları listele (Admin için)
     */
    public function getAllSections(): Collection;

    /**
     * Ana sayfa için section'ları videolarıyla birlikte getir (Frontend için)
     */
    public function getHomeSectionsWithVideos(): array;

    /**
     * ID ile section getir
     */
    public function getSectionById(int $id): HomeSection;

    /**
     * Yeni section oluştur
     */
    public function createSection(array $data): HomeSection;

    /**
     * Section güncelle
     */
    public function updateSection(int $id, array $data): HomeSection;

    /**
     * Section sil
     */
    public function deleteSection(int $id): bool;

    /**
     * Section'ın aktif/pasif durumunu değiştir
     */
    public function toggleSectionActive(int $id): HomeSection;

    /**
     * Toplu sıralama güncelleme
     */
    public function reorderSections(array $orderData): bool;

    /**
     * Section'ı yukarı taşı
     */
    public function moveSectionUp(int $id): bool;

    /**
     * Section'ı aşağı taşı
     */
    public function moveSectionDown(int $id): bool;

    /**
     * Section preview (Admin panelinde önizleme için)
     */
    public function previewSection(int $id): array;

    /**
     * Content type validasyonu
     */
    public function validateContentData(string $contentType, array $contentData): bool;

    /**
     * Section istatistikleri
     */
    public function getStatistics(): array;

    /**
     * Kategori silindiğinde ilgili section'ları kontrol et
     */
    public function checkCategoryUsage(int $categoryId): bool;
}
