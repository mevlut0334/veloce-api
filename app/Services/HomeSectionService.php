<?php

namespace App\Services;

use App\Models\HomeSection;
use App\Models\Category;
use App\Models\Video;
use App\Repositories\Contracts\HomeSectionRepositoryInterface;
use App\Services\Contracts\HomeSectionServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class HomeSectionService implements HomeSectionServiceInterface
{
    public function __construct(
        private HomeSectionRepositoryInterface $repository
    ) {}

    public function getAllSections(): Collection
    {
        return $this->repository->all();
    }

    public function getHomeSectionsWithVideos(): array
    {
        $sections = $this->repository->getForHomePage();

        $sectionsWithVideos = [];

        foreach ($sections as $section) {
            $videos = $section->getVideos();

            // Boş section'ları atla
            if ($videos->isEmpty()) {
                continue;
            }

            $sectionsWithVideos[] = [
                'id' => $section->id,
                'title' => $section->title,
                'content_type' => $section->content_type,
                'order' => $section->order,
                'videos' => $videos,
            ];
        }

        return $sectionsWithVideos;
    }

    public function getSectionById(int $id): HomeSection
    {
        return $this->repository->findByIdOrFail($id);
    }

    public function createSection(array $data): HomeSection
    {
        // Content data validasyonu
        $this->validateContentData($data['content_type'], $data['content_data'] ?? []);

        return $this->repository->create($data);
    }

    public function updateSection(int $id, array $data): HomeSection
    {
        // Content data validasyonu (eğer değiştiriliyorsa)
        if (isset($data['content_type']) && isset($data['content_data'])) {
            $this->validateContentData($data['content_type'], $data['content_data']);
        }

        return $this->repository->update($id, $data);
    }

    public function deleteSection(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function toggleSectionActive(int $id): HomeSection
    {
        return $this->repository->toggleActive($id);
    }

    public function reorderSections(array $orderData): bool
    {
        return $this->repository->reorder($orderData);
    }

    public function moveSectionUp(int $id): bool
    {
        return $this->repository->moveUp($id);
    }

    public function moveSectionDown(int $id): bool
    {
        return $this->repository->moveDown($id);
    }

    public function previewSection(int $id): array
    {
        $section = $this->getSectionById($id);
        $videos = $section->getVideos();

        return [
            'section' => [
                'id' => $section->id,
                'title' => $section->title,
                'content_type' => $section->content_type,
                'content_type_label' => $section->getContentTypeLabel(),
                'is_active' => $section->is_active,
                'limit' => $section->limit,
                'videos_count' => $videos->count(),
            ],
            'videos' => $videos,
        ];
    }

    public function validateContentData(string $contentType, array $contentData): bool
    {
        $rules = match($contentType) {
            HomeSection::TYPE_VIDEO_IDS => [
                'video_ids' => ['required', 'array', 'min:1'],
                'video_ids.*' => ['required', 'integer', 'exists:videos,id'],
            ],
            HomeSection::TYPE_CATEGORY => [
                'category_id' => ['required', 'integer', 'exists:categories,id'],
            ],
            HomeSection::TYPE_TRENDING => [
                'days' => ['nullable', 'integer', 'min:1', 'max:365'],
            ],
            HomeSection::TYPE_RECENT => [],
            default => throw ValidationException::withMessages([
                'content_type' => ['Geçersiz content type.']
            ]),
        };

        $validator = Validator::make($contentData, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Video IDs için ek kontrol - videoların aktif ve işlenmiş olması
        if ($contentType === HomeSection::TYPE_VIDEO_IDS) {
            $videoIds = $contentData['video_ids'];
            $activeVideoIds = Video::whereIn('id', $videoIds)
                ->active()
                ->processed()
                ->pluck('id')
                ->toArray();

            $inactiveVideoIds = array_diff($videoIds, $activeVideoIds);

            if (!empty($inactiveVideoIds)) {
                throw ValidationException::withMessages([
                    'video_ids' => ['Seçilen videolardan bazıları aktif değil veya işlenmemiş: ' . implode(', ', $inactiveVideoIds)]
                ]);
            }
        }

        // Category için ek kontrol - kategorinin aktif olması
        if ($contentType === HomeSection::TYPE_CATEGORY) {
            $category = Category::find($contentData['category_id']);

            if ($category && !$category->is_active) {
                throw ValidationException::withMessages([
                    'category_id' => ['Seçilen kategori aktif değil.']
                ]);
            }
        }

        return true;
    }

    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    public function checkCategoryUsage(int $categoryId): bool
    {
        return $this->repository->hasCategorySection($categoryId);
    }
}
