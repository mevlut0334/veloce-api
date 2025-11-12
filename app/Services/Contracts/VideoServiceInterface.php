<?php

namespace App\Services\Contracts;

use App\Models\Video;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

interface VideoServiceInterface
{
    /**
     * Tüm videoları listele (pagination ile)
     */
    public function getAllVideos(int $perPage = 20, array $filters = []): LengthAwarePaginator;

    /**
     * Video detayını getir
     */
    public function getVideoById(int $id): ?Video;

    /**
     * Slug ile video getir
     */
    public function getVideoBySlug(string $slug): ?Video;

    /**
     * Yeni video oluştur
     */
    public function createVideo(array $data, ?UploadedFile $videoFile = null, ?UploadedFile $thumbnailFile = null): Video;

    /**
     * Video güncelle
     */
    public function updateVideo(Video $video, array $data, ?UploadedFile $videoFile = null, ?UploadedFile $thumbnailFile = null): bool;

    /**
     * Video sil
     */
    public function deleteVideo(Video $video): bool;

    /**
     * Video aktif/inaktif durumunu değiştir
     */
    public function toggleVideoStatus(Video $video): bool;

    /**
     * Popüler videoları getir
     */
    public function getPopularVideos(int $limit = 10): Collection;

    /**
     * Son eklenen videoları getir
     */
    public function getRecentVideos(int $limit = 10): Collection;

    /**
     * Kategoriye göre videoları getir
     */
    public function getVideosByCategory(int $categoryId, int $limit = null): Collection;

    /**
     * Etikete göre videoları getir
     */
    public function getVideosByTag(int $tagId, int $limit = null): Collection;

    /**
     * Video arama
     */
    public function searchVideos(string $term, int $perPage = 20): LengthAwarePaginator;

    /**
     * Benzer videoları getir
     */
    public function getSimilarVideos(Video $video, int $limit = 6): Collection;

    /**
     * Video görüntülenme sayısını artır
     */
    public function incrementViews(Video $video): bool;

    /**
     * Thumbnail yeniden oluştur
     */
    public function regenerateThumbnail(Video $video, int $timeInSeconds = 2): void;

    /**
     * Video istatistiklerini getir
     */
    public function getStatistics(): array;

    /**
     * Toplu durum güncelleme
     */
    public function bulkUpdateStatus(array $videoIds, bool $isActive): int;
}
