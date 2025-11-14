<?php

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CategoryRepository implements CategoryRepositoryInterface
{
    protected Category $model;

    public function __construct(Category $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->model->ordered()->get();
    }

    public function find(int $id): ?Category
    {
        return $this->model->find($id);
    }

    public function findOrFail(int $id): Category
    {
        return $this->model->findOrFail($id);
    }

    public function findBySlug(string $slug): ?Category
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function create(array $data): Category
    {
        $category = $this->model->create($data);
        $this->clearCache();
        return $category;
    }

    public function update(Category $category, array $data): bool
    {
        $result = $category->update($data);

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    public function delete(Category $category): bool
    {
        $result = $category->delete();

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    public function toggleActive(Category $category): bool
    {
        $result = $category->update([
            'is_active' => !$category->is_active
        ]);

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    public function toggleShowOnHome(Category $category): bool
    {
        $result = $category->update([
            'show_on_home' => !$category->show_on_home
        ]);

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    public function getActiveCategories(): Collection
    {
        return Cache::remember(
            'categories_active',
            now()->addMinutes(30),
            fn() => $this->model->active()->ordered()->get()
        );
    }

    public function getHomeCategories(): Collection
    {
        return $this->model->getHomeCategories();
    }

    public function getAdminCategories(): Collection
    {
        return $this->model->forAdmin()->get();
    }

    public function updateOrder(int $categoryId, int $order): bool
    {
        $result = $this->model->where('id', $categoryId)
            ->update(['order' => $order]);

        if ($result) {
            $this->clearCache();
        }

        return (bool) $result;
    }

    public function bulkUpdateOrder(array $categories): bool
    {
        DB::beginTransaction();

        try {
            foreach ($categories as $categoryData) {
                $this->model->where('id', $categoryData['id'])
                    ->update(['order' => $categoryData['order']]);
            }

            $this->clearCache();

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function getCategoriesWithActiveVideos(): Collection
    {
        return $this->model->hasActiveVideos()
            ->withActiveVideosCount()
            ->ordered()
            ->get();
    }

    public function withVideoCounts(): Collection
    {
        return $this->model->withAllCounts()->ordered()->get();
    }

    public function withRelations(Category $category): Category
    {
        return $category->load(['videos', 'activeVideos']);
    }

    public function clearCache(): void
    {
        Cache::forget('categories_active');
        $this->model->clearHomeCache();
    }
}
