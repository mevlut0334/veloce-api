<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video' => 'required|file|mimetypes:video/mp4,video/mpeg,video/quicktime|max:512000',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'is_premium' => 'boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        try {
            $video = $this->videoService->createVideo(
                $validated,
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
    public function update(Request $request, Video $video)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video' => 'nullable|file|mimetypes:video/mp4,video/mpeg,video/quicktime|max:512000',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'is_premium' => 'boolean',
            'is_active' => 'boolean',
            'orientation' => 'nullable|in:horizontal,vertical',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        try {
            $this->videoService->updateVideo(
                $video,
                $validated,
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
}
