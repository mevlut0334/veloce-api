<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\StoreTagRequest;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Http\Resources\VideoResource;
use App\Services\Contracts\TagServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends Controller
{
    public function __construct(
        private TagServiceInterface $tagService
    ) {}

    /**
     * Tüm aktif tag'leri listele (API - Public)
     */
    public function index(): AnonymousResourceCollection
    {
        $tags = $this->tagService->getAllActiveTags();

        return TagResource::collection($tags);
    }

    /**
     * Popüler tag'leri getir (API - Public)
     */
    public function popular(Request $request): AnonymousResourceCollection
    {
        $limit = $request->integer('limit', 20);
        $tags = $this->tagService->getPopularTags($limit);

        return TagResource::collection($tags);
    }

    /**
     * Tag bulutu için tag'leri getir (API - Public)
     */
    public function cloud(Request $request): AnonymousResourceCollection
    {
        $limit = $request->integer('limit', 50);
        $tags = $this->tagService->getTagCloud($limit);

        return TagResource::collection($tags);
    }

    /**
     * Tag detayını göster (API - Public)
     */
    public function show(string $slug): TagResource
    {
        $tag = $this->tagService->getTagBySlug($slug);

        if (!$tag) {
            abort(404, 'Tag bulunamadı.');
        }

        return new TagResource($tag);
    }

    /**
     * Tag'a ait videoları listele (API - Public)
     */
    public function videos(Request $request, string $slug): AnonymousResourceCollection
    {
        $perPage = $request->integer('per_page', 24);
        $videos = $this->tagService->getTagVideos($slug, $perPage);

        return VideoResource::collection($videos);
    }

    /**
     * Tag arama (API - Public)
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $query = $request->input('q', '');
        $perPage = $request->integer('per_page', 20);

        if (empty($query)) {
            return TagResource::collection([]);
        }

        $tags = $this->tagService->searchTags($query, $perPage);

        return TagResource::collection($tags);
    }

    // =========================================================================
    // ADMIN ENDPOINTS
    // =========================================================================

    /**
     * Admin: Tag listesi
     */
    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->integer('per_page', 50);
        $tags = $this->tagService->getAdminTagList($perPage);

        return TagResource::collection($tags);
    }

    /**
     * Admin: Tag oluştur
     */
    public function store(StoreTagRequest $request): JsonResponse
    {
        $tag = $this->tagService->createTag($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tag başarıyla oluşturuldu.',
            'data' => new TagResource($tag)
        ], 201);
    }

    /**
     * Admin: Tag güncelle
     */
    public function update(UpdateTagRequest $request, int $id): JsonResponse
    {
        $tag = $this->tagService->updateTag($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tag başarıyla güncellendi.',
            'data' => new TagResource($tag)
        ]);
    }

    /**
     * Admin: Tag sil
     */
    public function destroy(int $id): JsonResponse
    {
        $this->tagService->deleteTag($id);

        return response()->json([
            'success' => true,
            'message' => 'Tag başarıyla silindi.'
        ]);
    }

    /**
     * Admin: Tag durumunu değiştir
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $tag = $this->tagService->toggleTagStatus($id);

        return response()->json([
            'success' => true,
            'message' => 'Tag durumu güncellendi.',
            'data' => new TagResource($tag)
        ]);
    }

    /**
     * Admin: Tag istatistikleri
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->tagService->getTagStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Admin: Kullanılmayan tag'leri listele
     */
    public function unused(): AnonymousResourceCollection
    {
        $tags = $this->tagService->getUnusedTags();

        return TagResource::collection($tags);
    }

    /**
     * Admin: Kullanılmayan tag'leri temizle
     */
    public function cleanup(): JsonResponse
    {
        $deletedCount = $this->tagService->cleanupUnusedTags();

        return response()->json([
            'success' => true,
            'message' => "{$deletedCount} adet kullanılmayan tag temizlendi.",
            'deleted_count' => $deletedCount
        ]);
    }

    /**
     * Admin: Cache temizle
     */
    public function clearCache(): JsonResponse
    {
        $this->tagService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Tag cache başarıyla temizlendi.'
        ]);
    }
}
