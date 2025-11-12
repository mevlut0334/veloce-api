<?php

namespace App\Repositories\Contracts;

use App\Models\Video;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface VideoRepositoryInterface
{
    /**
     * Tüm videoları getir (pagination ile)
     */
    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator;

    /**
     * ID ile video bul
     */
    public function find(int $id): ?Video;

    /**
     * ID ile video bul veya hata fırlat
     */
    public function findOrFail(int $id): Video;

    /**
     * Slug ile video bul
     */
    public function findBySlug(string $slug): ?Video;

    /**
     * Yeni video oluştur
     */
    public function create(array $data): Video;

    /**
     * Video güncelle
     */
    public function update(Video $video, array $data): bool;

    /**
     * Video sil
     */
    public function delete(Video $video): bool;

    /**
     * Video aktif/inaktif durumunu değiştir
     */
    public function toggleActive(Video $video): bool;

    /**
     * Kategorileri senkronize et
     */
    public function syncCategories(Video $video, array $categoryIds): void;

    /**
     * Etiketleri senkronize et
     */
    public function syncTags(Video $video, array $tagIds): void;

    /**
     * Aktif videoları getir
     */
    public function getActive(int $limit = null): Collection;

    /**
     * Premium videoları getir
     */
    public function getPremium(int $limit = null): Collection;

    /**
     * Popüler videoları getir
     */
    public function getPopular(int $limit = 10): Collection;

    /**
     * Son eklenen videoları getir
     */
    public function getRecent(int $limit = 10): Collection;

    /**
     * Kategoriye göre videoları getir
     */
    public function getByCategory(int $categoryId, int $limit = null): Collection;

    /**
     * Etikete göre videoları getir
     */
    public function getByTag(int $tagId, int $limit = null): Collection;

    /**
     * Arama yap
     */
    public function search(string $term, int $perPage = 20): LengthAwarePaginator;

    /**
     * İlişkilerle birlikte yükle
     */
    public function withRelations(Video $video, array $relations = []): Video;

    /**
     * Görüntülenme sayısını artır
     */
    public function incrementViewCount(Video $video): bool;

    /**
     * Favori sayısını artır
     */
    public function incrementFavoriteCount(Video $video): bool;

    /**
     * Favori sayısını azalt
     */
    public function decrementFavoriteCount(Video $video): bool;

    /**
     * Video istatistiklerini getir
     */
    public function getStatistics(): array;

    /**
     * Benzer videoları getir
     */
    public function getSimilarVideos(Video $video, int $limit = 6): Collection;

    /**
     * Toplu durum güncelleme
     */
    public function bulkUpdateStatus(array $videoIds, bool $isActive): int;

    /**
     * Video dosyalarını sil
     */
    public function deleteFiles(Video $video): bool;
}
