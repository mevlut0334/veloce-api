<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHomeSectionRequest;
use App\Http\Requests\Admin\UpdateHomeSectionRequest;
use App\Http\Requests\Admin\ReorderHomeSectionsRequest;
use App\Http\Resources\Admin\HomeSectionResource;
use App\Services\Contracts\HomeSectionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HomeSectionController extends Controller
{
    public function __construct(
        private HomeSectionServiceInterface $homeSectionService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $sections = $this->homeSectionService->getAllSections();

        return HomeSectionResource::collection($sections);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHomeSectionRequest $request): JsonResponse
    {
        $section = $this->homeSectionService->createSection($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Section başarıyla oluşturuldu.',
            'data' => new HomeSectionResource($section),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $section = $this->homeSectionService->getSectionById($id);

        return response()->json([
            'success' => true,
            'data' => new HomeSectionResource($section),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHomeSectionRequest $request, int $id): JsonResponse
    {
        $section = $this->homeSectionService->updateSection($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Section başarıyla güncellendi.',
            'data' => new HomeSectionResource($section),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->homeSectionService->deleteSection($id);

        return response()->json([
            'success' => true,
            'message' => 'Section başarıyla silindi.',
        ]);
    }

    /**
     * Toggle section active status
     */
    public function toggleActive(int $id): JsonResponse
    {
        $section = $this->homeSectionService->toggleSectionActive($id);

        return response()->json([
            'success' => true,
            'message' => 'Section durumu güncellendi.',
            'data' => new HomeSectionResource($section),
        ]);
    }

    /**
     * Reorder sections
     */
    public function reorder(ReorderHomeSectionsRequest $request): JsonResponse
    {
        $this->homeSectionService->reorderSections($request->input('sections'));

        return response()->json([
            'success' => true,
            'message' => 'Section sıralaması güncellendi.',
        ]);
    }

    /**
     * Move section up
     */
    public function moveUp(int $id): JsonResponse
    {
        $moved = $this->homeSectionService->moveSectionUp($id);

        if (!$moved) {
            return response()->json([
                'success' => false,
                'message' => 'Section yukarı taşınamadı. Zaten en üstte.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Section yukarı taşındı.',
        ]);
    }

    /**
     * Move section down
     */
    public function moveDown(int $id): JsonResponse
    {
        $moved = $this->homeSectionService->moveSectionDown($id);

        if (!$moved) {
            return response()->json([
                'success' => false,
                'message' => 'Section aşağı taşınamadı. Zaten en altta.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Section aşağı taşındı.',
        ]);
    }

    /**
     * Preview section with videos
     */
    public function preview(int $id): JsonResponse
    {
        $preview = $this->homeSectionService->previewSection($id);

        return response()->json([
            'success' => true,
            'data' => $preview,
        ]);
    }

    /**
     * Get statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->homeSectionService->getStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
