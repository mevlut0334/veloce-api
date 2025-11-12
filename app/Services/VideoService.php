<?php

namespace App\Services;

use App\Models\Video;
use App\Repositories\Contracts\VideoRepositoryInterface;
use App\Services\Contracts\VideoServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VideoService implements VideoServiceInterface
{
    protected VideoRepositoryInterface $videoRepository;

    public function __construct(VideoRepositoryInterface $videoRepository)
    {
        $this->videoRepository = $videoRepository;
    }

    public function getAllVideos(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        return $this->videoRepository->paginate($perPage, $filters);
    }

    public function getVideoById(int $id): ?Video
    {
        return $this->videoRepository->find($id);
    }

    public function getVideoBySlug(string $slug): ?Video
    {
        return $this->videoRepository->findBySlug($slug);
    }

    public function createVideo(array $data, ?UploadedFile $videoFile = null, ?UploadedFile $thumbnailFile = null): Video
    {
        DB::beginTransaction();

        try {
            // 1. Video kaydını oluştur (inactive)
            $videoData = [
                'title' => $data['title'],
                'slug' => Str::slug($data['title']),
                'description' => $data['description'] ?? null,
                'is_premium' => $data['is_premium'] ?? false,
                'is_active' => false,
                'video_path' => '',
                'thumbnail_path' => '',
            ];

            $video = $this->videoRepository->create($videoData);

            // 2. Kategorileri ekle
            if (!empty($data['category_ids'])) {
                $this->videoRepository->syncCategories($video, $data['category_ids']);
            }

            // 3. Etiketleri ekle
            if (!empty($data['tag_ids'])) {
                $this->videoRepository->syncTags($video, $data['tag_ids']);
            }

            // 4. Video dosyasını işle
            if ($videoFile) {
                $tempVideoPath = $videoFile->store('videos/temp');
                $video->dispatchVideoUpload($tempVideoPath);
            }

            // 5. Thumbnail işle
            if ($thumbnailFile) {
                $tempThumbnailPath = $thumbnailFile->store('thumbnails/temp');
                $video->dispatchThumbnailUpload($tempThumbnailPath);
            } elseif ($videoFile) {
                // Video varsa otomatik thumbnail oluştur
                $video->dispatchThumbnailGeneration();
            }

            // 6. Video optimize et
            if ($videoFile) {
                $video->dispatchOptimization();
            }

            DB::commit();

            Log::info('Video başarıyla oluşturuldu', [
                'video_id' => $video->id,
                'title' => $video->title
            ]);

            return $video;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Video oluşturma hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function updateVideo(Video $video, array $data, ?UploadedFile $videoFile = null, ?UploadedFile $thumbnailFile = null): bool
    {
        DB::beginTransaction();

        try {
            // 1. Video bilgilerini güncelle
            $updateData = [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'is_premium' => $data['is_premium'] ?? false,
                'is_active' => $data['is_active'] ?? $video->is_active,
                'orientation' => $data['orientation'] ?? $video->orientation,
            ];

            $this->videoRepository->update($video, $updateData);

            // 2. Yeni video dosyası yüklendiyse
            if ($videoFile) {
                // Eski dosyaları sil
                $this->videoRepository->deleteFiles($video);

                // Geçici klasöre yükle ve job başlat
                $tempVideoPath = $videoFile->store('videos/temp');
                $this->videoRepository->update($video, ['is_active' => false]);
                $video->dispatchVideoUpload($tempVideoPath);
                $video->dispatchOptimization();
            }

            // 3. Yeni thumbnail yüklendiyse
            if ($thumbnailFile) {
                $tempThumbnailPath = $thumbnailFile->store('thumbnails/temp');
                $video->dispatchThumbnailUpload($tempThumbnailPath);
            }

            // 4. Kategorileri güncelle
            if (isset($data['category_ids'])) {
                $this->videoRepository->syncCategories($video, $data['category_ids']);
            }

            // 5. Etiketleri güncelle
            if (isset($data['tag_ids'])) {
                $this->videoRepository->syncTags($video, $data['tag_ids']);
            }

            DB::commit();

            Log::info('Video başarıyla güncellendi', [
                'video_id' => $video->id,
                'title' => $video->title
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Video güncelleme hatası', [
                'video_id' => $video->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function deleteVideo(Video $video): bool
    {
        try {
            // Dosyaları sil
            $this->videoRepository->deleteFiles($video);

            // Video kaydını sil (event hook ilişkileri temizler)
            $result = $this->videoRepository->delete($video);

            Log::info('Video başarıyla silindi', [
                'video_id' => $video->id,
                'title' => $video->title
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Video silme hatası', [
                'video_id' => $video->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function toggleVideoStatus(Video $video): bool
    {
        return $this->videoRepository->toggleActive($video);
    }

    public function getPopularVideos(int $limit = 10): Collection
    {
        return $this->videoRepository->getPopular($limit);
    }

    public function getRecentVideos(int $limit = 10): Collection
    {
        return $this->videoRepository->getRecent($limit);
    }

    public function getVideosByCategory(int $categoryId, int $limit = null): Collection
    {
        return $this->videoRepository->getByCategory($categoryId, $limit);
    }

    public function getVideosByTag(int $tagId, int $limit = null): Collection
    {
        return $this->videoRepository->getByTag($tagId, $limit);
    }

    public function searchVideos(string $term, int $perPage = 20): LengthAwarePaginator
    {
        return $this->videoRepository->search($term, $perPage);
    }

    public function getSimilarVideos(Video $video, int $limit = 6): Collection
    {
        return $this->videoRepository->getSimilarVideos($video, $limit);
    }

    public function incrementViews(Video $video): bool
    {
        return $this->videoRepository->incrementViewCount($video);
    }

    public function regenerateThumbnail(Video $video, int $timeInSeconds = 2): void
    {
        if (!$video->video_path) {
            throw new \Exception('Video dosyası bulunamadı!');
        }

        $video->dispatchThumbnailGeneration($timeInSeconds);
    }

    public function getStatistics(): array
    {
        return $this->videoRepository->getStatistics();
    }

    public function bulkUpdateStatus(array $videoIds, bool $isActive): int
    {
        return $this->videoRepository->bulkUpdateStatus($videoIds, $isActive);
    }
}
