<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeSlider;
use App\Models\Video;
use App\Services\Contracts\HomeSliderServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HomeSliderController extends Controller
{
    protected HomeSliderServiceInterface $sliderService;

    public function __construct(HomeSliderServiceInterface $sliderService)
    {
        $this->sliderService = $sliderService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sliders = $this->sliderService->getAdminSliders();

        return view('admin.sliders.index', compact('sliders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $videos = Video::active()
            ->select(['id', 'title', 'slug'])
            ->orderBy('title')
            ->get();

        return view('admin.sliders.create', compact('videos'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'video_id' => 'nullable|exists:videos,id',
            'order' => 'nullable|integer|min:0',
        ]);

        try {
            $slider = $this->sliderService->createSlider(
                $validated,
                $request->file('image')
            );

            return redirect()
                ->route('admin.sliders.index')
                ->with('success', 'Slider yükleniyor! İşlem tamamlandığında aktif hale gelecek.');

        } catch (\Exception $e) {
            Log::error('Slider oluşturma hatası (Controller)', [
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Slider yüklenirken bir hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(HomeSlider $slider)
    {
        $slider->load('video');

        return view('admin.sliders.show', compact('slider'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HomeSlider $slider)
    {
        $slider->load('video');

        $videos = Video::active()
            ->select(['id', 'title', 'slug'])
            ->orderBy('title')
            ->get();

        return view('admin.sliders.edit', compact('slider', 'videos'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HomeSlider $slider)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'video_id' => 'nullable|exists:videos,id',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        try {
            $this->sliderService->updateSlider(
                $slider,
                $validated,
                $request->file('image')
            );

            return redirect()
                ->route('admin.sliders.index')
                ->with('success', 'Slider güncellendi!');

        } catch (\Exception $e) {
            Log::error('Slider güncelleme hatası (Controller)', [
                'slider_id' => $slider->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Slider güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HomeSlider $slider)
    {
        try {
            $this->sliderService->deleteSlider($slider);

            return redirect()
                ->route('admin.sliders.index')
                ->with('success', 'Slider silindi!');

        } catch (\Exception $e) {
            Log::error('Slider silme hatası (Controller)', [
                'slider_id' => $slider->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Slider silinirken bir hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Toggle slider active status
     */
    public function toggleActive(HomeSlider $slider)
    {
        try {
            $this->sliderService->toggleSliderStatus($slider);

            $status = $slider->fresh()->is_active ? 'aktif' : 'inaktif';

            return redirect()
                ->back()
                ->with('success', "Slider {$status} yapıldı!");

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'İşlem başarısız: ' . $e->getMessage());
        }
    }

    /**
     * Update slider order
     */
    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'sliders' => 'required|array',
            'sliders.*.id' => 'required|exists:home_sliders,id',
            'sliders.*.order' => 'required|integer|min:0',
        ]);

        try {
            $this->sliderService->updateSliderOrder($validated['sliders']);

            return response()->json([
                'success' => true,
                'message' => 'Sıralama güncellendi!'
            ]);

        } catch (\Exception $e) {
            Log::error('Slider sıralama hatası (Controller)', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sıralama güncellenirken hata oluştu!'
            ], 500);
        }
    }
}
