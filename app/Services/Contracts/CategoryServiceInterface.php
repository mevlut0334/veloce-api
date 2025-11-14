<?php

namespace App\Services\Contracts;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryServiceInterface
{
    /**
     * Tüm kategorileri listele
     */
    public function getAllCategories(): Collection;

    /**
     * Admin için kategorileri listele (video sayıları ile)
     */
    public function getAdminCategories(): Collection;

    /**
     * Aktif kategorileri listele
     */
    public function getActiveCategories(): Collection;

    /**
     * Ana sayfa için kategorileri getir
     */
    public function getHomeCategories(): Collection;

    /**
     * Kategori detayını getir
     */
    public function getCategoryById(int $id): ?Category;

    /**
     * Slug ile kategori getir
     */
    public function getCategoryBySlug(string $slug): ?Category;

    /**
     * Yeni kategori oluştur
     */
    public function createCategory(array $data): Category;

    /**
     * Kategori güncelle
     */
    public function updateCategory(Category $category, array $data): bool;

    /**
     * Kategori sil
     */
    public function deleteCategory(Category $category): bool;

    /**
     * Kategori aktif/inaktif durumunu değiştir
     */
    public function toggleCategoryStatus(Category $category): bool;

    /**
     * Ana sayfada gösterim durumunu değiştir
     */
    public function toggleShowOnHome(Category $category): bool;

    /**
     * Kategori sıralamasını güncelle
     */
    public function updateCategoryOrder(array $categories): bool;

    /**
     * En az bir aktif videosu olan kategoriler
     */
    public function getCategoriesWithActiveVideos(): Collection;
}
