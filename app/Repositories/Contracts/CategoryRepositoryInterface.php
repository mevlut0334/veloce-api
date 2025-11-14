<?php

namespace App\Repositories\Contracts;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    /**
     * Tüm kategorileri getir (sıralı)
     */
    public function all(): Collection;

    /**
     * ID ile kategori bul
     */
    public function find(int $id): ?Category;

    /**
     * ID ile kategori bul veya hata fırlat
     */
    public function findOrFail(int $id): Category;

    /**
     * Slug ile kategori bul
     */
    public function findBySlug(string $slug): ?Category;

    /**
     * Yeni kategori oluştur
     */
    public function create(array $data): Category;

    /**
     * Kategori güncelle
     */
    public function update(Category $category, array $data): bool;

    /**
     * Kategori sil
     */
    public function delete(Category $category): bool;

    /**
     * Kategori aktif/inaktif durumunu değiştir
     */
    public function toggleActive(Category $category): bool;

    /**
     * Ana sayfada gösterim durumunu değiştir
     */
    public function toggleShowOnHome(Category $category): bool;

    /**
     * Aktif kategorileri getir
     */
    public function getActiveCategories(): Collection;

    /**
     * Ana sayfa için kategorileri getir (cache'li)
     */
    public function getHomeCategories(): Collection;

    /**
     * Admin için kategorileri getir (video sayıları ile)
     */
    public function getAdminCategories(): Collection;

    /**
     * Kategori sırasını güncelle
     */
    public function updateOrder(int $categoryId, int $order): bool;

    /**
     * Toplu sıralama güncelle
     */
    public function bulkUpdateOrder(array $categories): bool;

    /**
     * En az bir aktif videosu olan kategoriler
     */
    public function getCategoriesWithActiveVideos(): Collection;

    /**
     * Video sayısı ile kategorileri getir
     */
    public function withVideoCounts(): Collection;

    /**
     * İlişkilerle birlikte yükle
     */
    public function withRelations(Category $category): Category;

    /**
     * Cache'i temizle
     */
    public function clearCache(): void;
}
