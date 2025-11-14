<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\Video;
use App\Services\Contracts\TagServiceInterface;
use App\Repositories\Contracts\TagRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TagService implements TagServiceInterface
{
    public function __construct(
        private TagRepositoryInterface $tagRepository
    ) {}

    /**
     * API için tüm aktif tag'leri getir
     */
    public function getAllActiveTags(): Collection
    {
        return $this->tagRepository->getAllActive();
    }

    /**
     * Popüler tag'leri getir
     */
    public function getPopularTags(int $limit = 20): Collection
    {
        return $this->tagRepository->getPopular($limit);
    }

    /**
     * Tag bulutu için tag'leri getir
     */
    public function getTagCloud(int $limit = 50): Collection
    {
        return $this->tagRepository->getTagCloud($limit);
    }

    /**
     * Slug ile tag detayını getir
     */
    public function getTagBySlug(string $slug): ?Tag
    {
        $tag = $this->tagRepository->findBySlug($slug);

        if (!$tag || !$tag->is_active) {
            return null;
        }

        return $tag;
    }

    /**
     * Tag'a ait videoları getir
     */
    public function getTagVideos(string $slug, int $perPage = 24): LengthAwarePaginator
    {
        $tag = $this->getTagBySlug($slug);

        if (!$tag) {
            abort(404, 'Tag not found');
        }

        return $this->tagRepository->getVideos($tag->id, $perPage);
    }

    /**
     * Tag arama
     */
    public function searchTags(string $query, int $perPage = 20): LengthAwarePaginator
    {
        return $this->tagRepository->search($query, $perPage);
    }

    /**
     * Admin: Tag listesi
     */
    public function getAdminTagList(int $perPage = 50): LengthAwarePaginator
    {
        return $this->tagRepository->getForAdmin($perPage);
    }

    /**
     * Admin: Tag oluştur
     */
    public function createTag(array $data): Tag
    {
        try {
            DB::beginTransaction();

            $tag = $this->tagRepository->create($data);

            DB::commit();

            Log::info('Tag created', ['tag_id' => $tag->id, 'name' => $tag->name]);

            return $tag;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Tag creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Admin: Tag güncelle
     */
    public function updateTag(int $id, array $data): Tag
    {
        try {
            DB::beginTransaction();

            $this->tagRepository->update($id, $data);
            $tag = $this->tagRepository->findById($id);

            DB::commit();

            Log::info('Tag updated', ['tag_id' => $id]);

            return $tag;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Tag update failed', ['tag_id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Admin: Tag sil
     */
    public function deleteTag(int $id): bool
    {
        try {
            DB::beginTransaction();

            $result = $this->tagRepository->delete($id);

            DB::commit();

            Log::info('Tag deleted', ['tag_id' => $id]);

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Tag deletion failed', ['tag_id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Admin: Tag durumunu değiştir
     */
    public function toggleTagStatus(int $id): Tag
    {
        try {
            DB::beginTransaction();

            $this->tagRepository->toggleStatus($id);
            $tag = $this->tagRepository->findById($id);

            DB::commit();

            Log::info('Tag status toggled', ['tag_id' => $id, 'is_active' => $tag->is_active]);

            return $tag;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Tag status toggle failed', ['tag_id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Admin: Tag istatistikleri
     */
    public function getTagStatistics(): array
    {
        return $this->tagRepository->getStats();
    }

    /**
     * Admin: Kullanılmayan tag'leri getir
     */
    public function getUnusedTags(): Collection
    {
        return $this->tagRepository->getUnused();
    }

    /**
     * Admin: Kullanılmayan tag'leri temizle
     */
    public function cleanupUnusedTags(): int
    {
        try {
            DB::beginTransaction();

            $deletedCount = $this->tagRepository->deleteUnused();

            DB::commit();

            Log::info('Unused tags cleaned up', ['deleted_count' => $deletedCount]);

            return $deletedCount;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cleanup unused tags failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Video'ya tag'ler ekle (array of names)
     */
    public function syncTagsToVideo(int $videoId, array $tagNames): void
    {
        try {
            $video = Video::findOrFail($videoId);

            // Tag'leri bul veya oluştur
            $tags = $this->tagRepository->findOrCreateMany($tagNames);

            // Video ile tag'leri sync et
            $video->tags()->sync($tags->pluck('id')->toArray());

            Log::info('Tags synced to video', [
                'video_id' => $videoId,
                'tag_count' => $tags->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Tag sync to video failed', [
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Cache temizle
     */
    public function clearCache(): void
    {
        $this->tagRepository->clearCache();
        Log::info('Tag cache cleared');
    }
}
