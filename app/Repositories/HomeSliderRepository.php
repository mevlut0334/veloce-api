<?php

namespace App\Repositories;

use App\Models\HomeSlider;
use App\Repositories\Contracts\HomeSliderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class HomeSliderRepository implements HomeSliderRepositoryInterface
{
    protected HomeSlider $model;

    public function __construct(HomeSlider $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->model->ordered()->get();
    }

    public function find(int $id): ?HomeSlider
    {
        return $this->model->find($id);
    }

    public function findOrFail(int $id): HomeSlider
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data): HomeSlider
    {
        return $this->model->create($data);
    }

    public function update(HomeSlider $slider, array $data): bool
    {
        return $slider->update($data);
    }

    public function delete(HomeSlider $slider): bool
    {
        return $slider->delete();
    }

    public function toggleActive(HomeSlider $slider): bool
    {
        $result = $slider->update([
            'is_active' => !$slider->is_active
        ]);

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    public function getActiveSliders(): Collection
    {
        return $this->model->getHomeSliders();
    }

    public function getAdminSliders(): Collection
    {
        return $this->model->getAdminSliders();
    }

    public function updateOrder(int $sliderId, int $order): bool
    {
        $result = $this->model->where('id', $sliderId)
            ->update(['order' => $order]);

        if ($result) {
            $this->clearCache();
        }

        return (bool) $result;
    }

    public function bulkUpdateOrder(array $sliders): bool
    {
        DB::beginTransaction();

        try {
            foreach ($sliders as $sliderData) {
                $this->model->where('id', $sliderData['id'])
                    ->update(['order' => $sliderData['order']]);
            }

            $this->clearCache();

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function getSlidersWithVideo(): Collection
    {
        return $this->model->hasVideo()
            ->withVideo()
            ->ordered()
            ->get();
    }

    public function deleteImage(HomeSlider $slider): bool
    {
        return $slider->deleteImage();
    }

    public function withRelations(HomeSlider $slider): HomeSlider
    {
        return $slider->load('video');
    }

    public function clearCache(): void
    {
        $this->model->clearAllCache();
    }
}
