<?php

namespace App\Repositories\Contracts;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TagRepositoryInterface
{
    /**
     * Tüm aktif tag'leri getir
     */
    public function getAllActive(): Collection;

    /**
     * ID ile tag bul
     */
    public function findById(int $id): ?Tag;

    /**
     * Slug ile tag bul
     */
    public function findBySlug(string $slug): ?Tag;

    /**
     * Popüler tag'leri getir
     */
    public function getPopular(int $limit = 20): Collection;

    /**
     * Tag bulutu için tag'leri getir
     */
    public function getTagCloud(int $limit = 50): Collection;

    /**
     * Arama yap
     */
    public function search(string $query, int $perPage = 20): LengthAwarePaginator;

    /**
     * Admin için tag'leri getir (sayfalı)
     */
    public function getForAdmin(int $perPage = 50): LengthAwarePaginator;

    /**
     * Tag oluştur
     */
    public function create(array $data): Tag;

    /**
     * Tag güncelle
     */
    public function update(int $id, array $data): bool;

    /**
     * Tag sil
     */
    public function delete(int $id): bool;

    /**
     * İsme göre tag bul veya oluştur
     */
    public function findOrCreateByName(string $name): Tag;

    /**
     * Birden fazla tag bul veya oluştur
     */
    public function findOrCreateMany(array $names): Collection;

    /**
     * Tag istatistiklerini getir
     */
    public function getStats(): array;

    /**
     * Aktif/Pasif durumunu değiştir
     */
    public function toggleStatus(int $id): bool;

    /**
     * Tag'a ait videoları getir
     */
    public function getVideos(int $tagId, int $perPage = 24): LengthAwarePaginator;

    /**
     * Kullanılmayan tag'leri getir
     */
    public function getUnused(): Collection;

    /**
     * Kullanılmayan tag'leri sil
     */
    public function deleteUnused(): int;

    /**
     * Cache temizle
     */
    public function clearCache(): void;
}
