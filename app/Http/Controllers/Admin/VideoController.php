<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVideoRequest;
use App\Http\Requests\UpdateVideoRequest;
use App\Models\Video;
use App\Models\Category;
use App\Models\Tag;
use App\Services\Contracts\VideoServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VideoController extends Controller
{
    protected VideoServiceInterface $videoService;

    public function __construct(VideoServiceInterface $videoService)
    {
        $this->videoService = $videoService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'search',
            'is_active',
            'is_premium',
            'orientation',
            'category_id'
        ]);

        $videos = $this->videoService->getAllVideos(20, $filters);
        $categories = Category::active()->ordered()->get();

        return view('admin.videos.index', compact('videos', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::active()->ordered()->get();
        $tags = Tag::active()->alphabetical()->get();

        return view('admin.videos.create', compact('categories', 'tags'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVideoRequest $request)
    {
        try {
            $video = $this->videoService->createVideo(
                $request->validated(),
                $request->file('video'),
                $request->file('thumbnail')
            );

            return redirect()
                ->route('admin.videos.index')
                ->with('success', 'Video yükleniyor! İşlem tamamlandığında aktif hale gelecek.');

        } catch (\Exception $e) {
            Log::error('Video oluşturma hatası (Controller)', [
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Video yüklenirken bir hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Video $video)
    {
        $video->load(['categories', 'tags', 'views']);

        return view('admin.videos.show', compact('video'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Video $video)
    {
        $video->load(['categories', 'tags']);
        $categories = Category::active()->ordered()->get();
        $tags = Tag::active()->alphabetical()->get();

        return view('admin.videos.edit', compact('video', 'categories', 'tags'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVideoRequest $request, Video $video)
    {
        try {
            $this->videoService->updateVideo(
                $video,
                $request->validated(),
                $request->file('video'),
                $request->file('thumbnail')
            );

            return redirect()
                ->route('admin.videos.index')
                ->with('success', 'Video güncellendi!');

        } catch (\Exception $e) {
            Log::error('Video güncelleme hatası (Controller)', [
                'video_id' => $video->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Video güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Video $video)
    {
        try {
            $this->videoService->deleteVideo($video);

            return redirect()
                ->route('admin.videos.index')
                ->with('success', 'Video silindi!');

        } catch (\Exception $e) {
            Log::error('Video silme hatası (Controller)', [
                'video_id' => $video->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Video silinirken bir hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Toggle video active status
     */
    public function toggleActive(Video $video)
    {
        try {
            $this->videoService->toggleVideoStatus($video);

            $status = $video->fresh()->is_active ? 'aktif' : 'inaktif';

            return redirect()
                ->back()
                ->with('success', "Video {$status} yapıldı!");

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'İşlem başarısız: ' . $e->getMessage());
        }
    }

    /**
     * Toggle video premium status
     */
    public function togglePremium(Video $video)
    {
        try {
            $video->update([
                'is_premium' => !$video->is_premium
            ]);

            $status = $video->is_premium ? 'premium' : 'genel';

            return redirect()
                ->back()
                ->with('success', "Video {$status} içerik yapıldı!");

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'İşlem başarısız: ' . $e->getMessage());
        }
    }

    /**
     * Regenerate thumbnail
     */
    public function regenerateThumbnail(Video $video)
    {
        try {
            $this->videoService->regenerateThumbnail($video);

            return redirect()
                ->back()
                ->with('success', 'Thumbnail oluşturuluyor...');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Thumbnail oluşturulamadı: ' . $e->getMessage());
        }
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'video_ids' => 'required|array',
            'video_ids.*' => 'exists:videos,id',
            'is_active' => 'required|boolean',
        ]);

        try {
            $count = $this->videoService->bulkUpdateStatus(
                $validated['video_ids'],
                $validated['is_active']
            );

            $status = $validated['is_active'] ? 'aktif' : 'inaktif';

            return redirect()
                ->back()
                ->with('success', "{$count} video {$status} yapıldı!");

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Toplu güncelleme başarısız: ' . $e->getMessage());
        }
    }

    /**
     * Bulk update premium status
     */
    public function bulkUpdatePremium(Request $request)
    {
        $validated = $request->validate([
            'video_ids' => 'required|array',
            'video_ids.*' => 'exists:videos,id',
            'is_premium' => 'required|boolean',
        ]);

        try {
            $count = Video::whereIn('id', $validated['video_ids'])
                ->update(['is_premium' => $validated['is_premium']]);

            $status = $validated['is_premium'] ? 'premium' : 'genel';

            return redirect()
                ->back()
                ->with('success', "{$count} video {$status} yapıldı!");

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Toplu güncelleme başarısız: ' . $e->getMessage());
        }
    }

    /**
     * Get video statistics
     */
    public function statistics()
    {
        try {
            $stats = $this->videoService->getStatistics();

            return view('admin.videos.statistics', compact('stats'));

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'İstatistikler yüklenemedi: ' . $e->getMessage());
        }
    }
}
