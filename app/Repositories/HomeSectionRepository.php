<?php

namespace App\Repositories;

use App\Models\HomeSection;
use App\Repositories\Contracts\HomeSectionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class HomeSectionRepository implements HomeSectionRepositoryInterface
{
    public function __construct(
        private HomeSection $model
    ) {}

    public function all(): Collection
    {
        return $this->model->ordered()->get();
    }

    public function getAllActive(): Collection
    {
        return $this->model->active()->ordered()->get();
    }

    public function getForHomePage(): Collection
    {
        return $this->model->forHomePage()->get();
    }

    public function findById(int $id): ?HomeSection
    {
        return $this->model->find($id);
    }

    public function findByIdOrFail(int $id): HomeSection
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data): HomeSection
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): HomeSection
    {
        $section = $this->findByIdOrFail($id);
        $section->update($data);

        return $section->fresh();
    }

    public function delete(int $id): bool
    {
        $section = $this->findByIdOrFail($id);

        return $section->delete();
    }

    public function toggleActive(int $id): HomeSection
    {
        $section = $this->findByIdOrFail($id);
        $section->update(['is_active' => !$section->is_active]);

        return $section->fresh();
    }

    public function reorder(array $orderData): bool
    {
        DB::beginTransaction();

        try {
            foreach ($orderData as $item) {
                $this->model
                    ->where('id', $item['id'])
                    ->update(['order' => $item['order']]);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function moveUp(int $id): bool
    {
        $section = $this->findByIdOrFail($id);

        return $section->moveUp();
    }

    public function moveDown(int $id): bool
    {
        $section = $this->findByIdOrFail($id);

        return $section->moveDown();
    }

    public function getByContentType(string $contentType): Collection
    {
        return $this->model
            ->where('content_type', $contentType)
            ->ordered()
            ->get();
    }

    public function getStatistics(): array
    {
        return $this->model->getStatistics();
    }

    public function hasCategorySection(int $categoryId): bool
    {
        return $this->model
            ->where('content_type', HomeSection::TYPE_CATEGORY)
            ->where('content_data->category_id', $categoryId)
            ->exists();
    }

    public function normalizeOrders(): void
    {
        $this->model->reorderAll();
    }
}
