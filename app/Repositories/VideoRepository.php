<?php

namespace App\Repositories;

use App\Models\Video;
use App\Repositories\Contracts\VideoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class VideoRepository implements VideoRepositoryInterface
{
    protected Video $model;

    public function __construct(Video $model)
    {
        $this->model = $model;
    }

    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->query()->withRelations();

        // Arama
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Aktif/Inaktif
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Premium/Free
        if (isset($filters['is_premium'])) {
            $query->where('is_premium', $filters['is_premium']);
        }

        // Yönelim
        if (!empty($filters['orientation'])) {
            $query->where('orientation', $filters['orientation']);
        }

        // Kategori
        if (!empty($filters['category_id'])) {
            $query->inCategory($filters['category_id']);
        }

        // Etiket
        if (!empty($filters['tag_id'])) {
            $query->withTag($filters['tag_id']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function find(int $id): ?Video
    {
        return $this->model->find($id);
    }

    public function findOrFail(int $id): Video
    {
        return $this->model->findOrFail($id);
    }

    public function findBySlug(string $slug): ?Video
    {
        return $this->model->bySlug($slug)->first();
    }

    public function create(array $data): Video
    {
        return $this->model->create($data);
    }

    public function update(Video $video, array $data): bool
    {
        return $video->update($data);
    }

    public function delete(Video $video): bool
    {
        return $video->delete();
    }

    public function toggleActive(Video $video): bool
    {
        return $video->update([
            'is_active' => !$video->is_active
        ]);
    }

    public function syncCategories(Video $video, array $categoryIds): void
    {
        $video->categories()->sync($categoryIds);
    }

    public function syncTags(Video $video, array $tagIds): void
    {
        $video->tags()->sync($tagIds);
    }

    public function getActive(int $limit = null): Collection
    {
        $query = $this->model->active()->withRelations()->latest();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function getPremium(int $limit = null): Collection
    {
        $query = $this->model->active()->premium()->withRelations()->latest();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function getPopular(int $limit = 10): Collection
    {
        return $this->model->active()
            ->withRelations()
            ->popular($limit)
            ->get();
    }

    public function getRecent(int $limit = 10): Collection
    {
        return $this->model->active()
            ->withRelations()
            ->recent($limit)
            ->get();
    }

    public function getByCategory(int $categoryId, int $limit = null): Collection
    {
        $query = $this->model->active()
            ->inCategory($categoryId)
            ->withRelations()
            ->latest();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function getByTag(int $tagId, int $limit = null): Collection
    {
        $query = $this->model->active()
            ->withTag($tagId)
            ->withRelations()
            ->latest();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function search(string $term, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model->active()
            ->search($term)
            ->withRelations()
            ->latest()
            ->paginate($perPage);
    }

    public function withRelations(Video $video, array $relations = []): Video
    {
        if (empty($relations)) {
            $relations = ['categories', 'tags'];
        }

        return $video->load($relations);
    }

    public function incrementViewCount(Video $video): bool
    {
        return $video->incrementViewCount();
    }

    public function incrementFavoriteCount(Video $video): bool
    {
        return $video->incrementFavoriteCount();
    }

    public function decrementFavoriteCount(Video $video): bool
    {
        return $video->decrementFavoriteCount();
    }

    public function getStatistics(): array
    {
        return $this->model->getStatistics();
    }

    public function getSimilarVideos(Video $video, int $limit = 6): Collection
    {
        return $video->getSimilarVideos($limit);
    }

    public function bulkUpdateStatus(array $videoIds, bool $isActive): int
    {
        return $this->model->whereIn('id', $videoIds)
            ->update(['is_active' => $isActive]);
    }

    public function deleteFiles(Video $video): bool
    {
        return $video->deleteFiles();
    }
}
