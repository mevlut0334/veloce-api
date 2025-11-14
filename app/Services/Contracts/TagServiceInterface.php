<?php

namespace App\Services\Contracts;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TagServiceInterface
{
    /**
     * API için tüm aktif tag'leri getir
     */
    public function getAllActiveTags(): Collection;

    /**
     * Popüler tag'leri getir
     */
    public function getPopularTags(int $limit = 20): Collection;

    /**
     * Tag bulutu için tag'leri getir
     */
    public function getTagCloud(int $limit = 50): Collection;

    /**
     * Slug ile tag detayını getir
     */
    public function getTagBySlug(string $slug): ?Tag;

    /**
     * Tag'a ait videoları getir
     */
    public function getTagVideos(string $slug, int $perPage = 24): LengthAwarePaginator;

    /**
     * Tag arama
     */
    public function searchTags(string $query, int $perPage = 20): LengthAwarePaginator;

    /**
     * Admin: Tag listesi
     */
    public function getAdminTagList(int $perPage = 50): LengthAwarePaginator;

    /**
     * Admin: Tag oluştur
     */
    public function createTag(array $data): Tag;

    /**
     * Admin: Tag güncelle
     */
    public function updateTag(int $id, array $data): Tag;

    /**
     * Admin: Tag sil
     */
    public function deleteTag(int $id): bool;

    /**
     * Admin: Tag durumunu değiştir
     */
    public function toggleTagStatus(int $id): Tag;

    /**
     * Admin: Tag istatistikleri
     */
    public function getTagStatistics(): array;

    /**
     * Admin: Kullanılmayan tag'leri getir
     */
    public function getUnusedTags(): Collection;

    /**
     * Admin: Kullanılmayan tag'leri temizle
     */
    public function cleanupUnusedTags(): int;

    /**
     * Video'ya tag'ler ekle (array of names)
     */
    public function syncTagsToVideo(int $videoId, array $tagNames): void;

    /**
     * Cache temizle
     */
    public function clearCache(): void;
}
