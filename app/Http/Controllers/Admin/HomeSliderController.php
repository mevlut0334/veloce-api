<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHomeSliderRequest;
use App\Http\Requests\UpdateHomeSliderRequest;
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
    public function store(StoreHomeSliderRequest $request)
    {
        try {
            $slider = $this->sliderService->createSlider(
                $request->validated(),
                $request->file('image')
            );

            return redirect()
                ->route('admin.sliders.index')
                ->with('success', 'Slider başarıyla oluşturuldu!');

        } catch (\Exception $e) {
            Log::error('Slider oluşturma hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Slider oluşturulurken bir hata oluştu: ' . $e->getMessage());
        }
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
    public function update(UpdateHomeSliderRequest $request, HomeSlider $slider)
    {
        try {
            $this->sliderService->updateSlider(
                $slider,
                $request->validated(),
                $request->file('image')
            );

            return redirect()
                ->route('admin.sliders.index')
                ->with('success', 'Slider başarıyla güncellendi!');

        } catch (\Exception $e) {
            Log::error('Slider güncelleme hatası', [
                'slider_id' => $slider->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
                ->with('success', 'Slider başarıyla silindi!');

        } catch (\Exception $e) {
            Log::error('Slider silme hatası', [
                'slider_id' => $slider->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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

            $status = $slider->fresh()->is_active ? 'aktif' : 'pasif';

            return redirect()
                ->back()
                ->with('success', "Slider {$status} hale getirildi!");

        } catch (\Exception $e) {
            Log::error('Slider durum değiştirme hatası', [
                'slider_id' => $slider->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Durum değiştirilemedi: ' . $e->getMessage());
        }
    }

    /**
     * Update slider order (Reorder)
     */
    public function reorder(Request $request)
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
                'message' => 'Slider sıralaması başarıyla güncellendi!'
            ]);

        } catch (\Exception $e) {
            Log::error('Slider sıralama hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sıralama güncellenirken hata oluştu!'
            ], 500);
        }
    }
}
