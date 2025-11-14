<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Services\Contracts\CategoryServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CategoryService implements CategoryServiceInterface
{
    protected CategoryRepositoryInterface $categoryRepository;

    public function __construct(CategoryRepositoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function getAllCategories(): Collection
    {
        return $this->categoryRepository->all();
    }

    public function getAdminCategories(): Collection
    {
        return $this->categoryRepository->getAdminCategories();
    }

    public function getActiveCategories(): Collection
    {
        return $this->categoryRepository->getActiveCategories();
    }

    public function getHomeCategories(): Collection
    {
        return $this->categoryRepository->getHomeCategories();
    }

    public function getCategoryById(int $id): ?Category
    {
        return $this->categoryRepository->find($id);
    }

    public function getCategoryBySlug(string $slug): ?Category
    {
        return $this->categoryRepository->findBySlug($slug);
    }

    public function createCategory(array $data): Category
    {
        DB::beginTransaction();

        try {
            // Slug oluştur
            if (!isset($data['slug']) || empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Slug unique kontrolü
            $originalSlug = $data['slug'];
            $counter = 1;

            while ($this->categoryRepository->findBySlug($data['slug'])) {
                $data['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }

            $category = $this->categoryRepository->create($data);

            DB::commit();

            Log::info('Kategori başarıyla oluşturuldu', [
                'category_id' => $category->id,
                'name' => $category->name
            ]);

            return $category;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Kategori oluşturma hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function updateCategory(Category $category, array $data): bool
    {
        DB::beginTransaction();

        try {
            // Slug güncelleniyorsa unique kontrolü
            if (isset($data['slug']) && $data['slug'] !== $category->slug) {
                $originalSlug = $data['slug'];
                $counter = 1;

                while ($this->categoryRepository->findBySlug($data['slug'])) {
                    $data['slug'] = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }

            $result = $this->categoryRepository->update($category, $data);

            DB::commit();

            Log::info('Kategori başarıyla güncellendi', [
                'category_id' => $category->id,
                'name' => $category->name
            ]);

            return $result;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Kategori güncelleme hatası', [
                'category_id' => $category->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function deleteCategory(Category $category): bool
    {
        DB::beginTransaction();

        try {
            $result = $this->categoryRepository->delete($category);

            DB::commit();

            Log::info('Kategori başarıyla silindi', [
                'category_id' => $category->id,
                'name' => $category->name
            ]);

            return $result;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Kategori silme hatası', [
                'category_id' => $category->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function toggleCategoryStatus(Category $category): bool
    {
        try {
            $result = $this->categoryRepository->toggleActive($category);

            Log::info('Kategori durumu değiştirildi', [
                'category_id' => $category->id,
                'new_status' => $category->fresh()->is_active ? 'aktif' : 'pasif'
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Kategori durum değiştirme hatası', [
                'category_id' => $category->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function toggleShowOnHome(Category $category): bool
    {
        try {
            $result = $this->categoryRepository->toggleShowOnHome($category);

            Log::info('Kategori ana sayfa gösterim durumu değiştirildi', [
                'category_id' => $category->id,
                'show_on_home' => $category->fresh()->show_on_home
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Kategori ana sayfa durumu değiştirme hatası', [
                'category_id' => $category->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function updateCategoryOrder(array $categories): bool
    {
        try {
            $result = $this->categoryRepository->bulkUpdateOrder($categories);

            if ($result) {
                Log::info('Kategori sıralaması güncellendi', [
                    'count' => count($categories)
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Kategori sıralama hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function getCategoriesWithActiveVideos(): Collection
    {
        return $this->categoryRepository->getCategoriesWithActiveVideos();
    }
}
