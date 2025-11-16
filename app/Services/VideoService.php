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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Jobs\ProcessVideoUpload;
use App\Jobs\ProcessThumbnailUpload;
use App\Jobs\OptimizeVideo;
use App\Jobs\GenerateThumbnail;

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
            // 1. ÖNCE DOSYALARI STORE ET
            $tempVideoPath = null;
            $tempThumbnailPath = null;

            if ($videoFile) {
                $tempVideoPath = $videoFile->store('videos/temp', 'public');
                Log::info('Video geçici klasöre yüklendi', [
                    'temp_path' => $tempVideoPath,
                    'size' => $videoFile->getSize()
                ]);
            }

            if ($thumbnailFile) {
                $tempThumbnailPath = $thumbnailFile->store('thumbnails/temp', 'public');
                Log::info('Thumbnail geçici klasöre yüklendi', [
                    'temp_path' => $tempThumbnailPath
                ]);
            }

            // 2. Video kaydını oluştur
            $videoData = [
                'title' => $data['title'],
                'slug' => Str::slug($data['title']),
                'description' => $data['description'] ?? null,
                'orientation' => null, // OptimizeVideo job'u otomatik belirleyecek
                'is_premium' => $data['is_premium'] ?? false,
                'is_active' => false,
                'is_processed' => false,
                'video_path' => '',
                'thumbnail_path' => '',
            ];

            $video = $this->videoRepository->create($videoData);

            // 3. Kategorileri ekle
            if (!empty($data['category_ids'])) {
                $this->videoRepository->syncCategories($video, $data['category_ids']);
                Log::info('Kategoriler eklendi', [
                    'video_id' => $video->id,
                    'categories' => $data['category_ids']
                ]);
            }

            // 4. Etiketleri ekle
            if (!empty($data['tag_ids'])) {
                $this->videoRepository->syncTags($video, $data['tag_ids']);
                Log::info('Etiketler eklendi', [
                    'video_id' => $video->id,
                    'tags' => $data['tag_ids']
                ]);
            }

            // 5. Job'ları dispatch et - DOĞRUDAN DISPATCH
            if ($tempVideoPath) {
                ProcessVideoUpload::dispatch($video, $tempVideoPath)->onQueue('default');
                Log::info('Video upload job başlatıldı', ['video_id' => $video->id]);
            }

            if ($tempThumbnailPath) {
                ProcessThumbnailUpload::dispatch($video, $tempThumbnailPath)->onQueue('default');
                Log::info('Thumbnail upload job başlatıldı', ['video_id' => $video->id]);
            } elseif ($tempVideoPath) {
                GenerateThumbnail::dispatch($video)->onQueue('default');
                Log::info('Thumbnail generation job başlatıldı', ['video_id' => $video->id]);
            }

            if ($tempVideoPath) {
                OptimizeVideo::dispatch($video)->onQueue('default');
                Log::info('Video optimization job başlatıldı', ['video_id' => $video->id]);
            }

            DB::commit();

            Log::info('Video başarıyla oluşturuldu', [
                'video_id' => $video->id,
                'title' => $video->title,
                'temp_video_path' => $tempVideoPath
            ]);

            return $video;

        } catch (\Exception $e) {
            DB::rollBack();

            // Hata durumunda geçici dosyaları temizle
            if (isset($tempVideoPath) && Storage::exists($tempVideoPath)) {
                Storage::delete($tempVideoPath);
            }
            if (isset($tempThumbnailPath) && Storage::exists($tempThumbnailPath)) {
                Storage::delete($tempThumbnailPath);
            }

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
                // orientation kaldırıldı - sadece video dosyası değişirse yeniden hesaplanacak
            ];

            $this->videoRepository->update($video, $updateData);

            // 2. Yeni video dosyası yüklendiyse
            if ($videoFile) {
                // Eski dosyaları sil
                $this->videoRepository->deleteFiles($video);

                // Geçici klasöre yükle ve job başlat
                $tempVideoPath = $videoFile->store('videos/temp', 'public');
                $this->videoRepository->update($video, [
                    'is_active' => false,
                    'orientation' => null // Yeni video için orientation sıfırla
                ]);
                ProcessVideoUpload::dispatch($video, $tempVideoPath)->onQueue('default');
                OptimizeVideo::dispatch($video)->onQueue('default');
            }

            // 3. Yeni thumbnail yüklendiyse
            if ($thumbnailFile) {
                $tempThumbnailPath = $thumbnailFile->store('thumbnails/temp', 'public');
                ProcessThumbnailUpload::dispatch($video, $tempThumbnailPath)->onQueue('default');
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

        GenerateThumbnail::dispatch($video, $timeInSeconds)->onQueue('default');
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
