<?php

namespace App\Jobs;

use App\Models\HomeSlider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessSliderImageUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public HomeSlider $slider;
    public string $tempImagePath;
    public string $targetFolder;

    public function __construct(HomeSlider $slider, string $tempImagePath, string $targetFolder = 'sliders/processed')
    {
        $this->slider = $slider;
        $this->tempImagePath = $tempImagePath;
        $this->targetFolder = $targetFolder;
    }

    public function handle(): void
    {
        try {
            Log::info("Slider image işleme başladı", [
                'slider_id' => $this->slider->id,
                'temp_path' => $this->tempImagePath
            ]);

            if (!Storage::exists($this->tempImagePath)) {
                throw new Exception("Geçici slider image dosyası bulunamadı: {$this->tempImagePath}");
            }

            $fileSize = Storage::size($this->tempImagePath);
            if ($fileSize === 0) {
                throw new Exception("Slider image dosyası boş");
            }

            $extension = pathinfo($this->tempImagePath, PATHINFO_EXTENSION);
            $newFileName = $this->generateUniqueFileName($extension);
            $finalPath = "{$this->targetFolder}/{$newFileName}";

            if (!Storage::move($this->tempImagePath, $finalPath)) {
                throw new Exception("Slider image dosyası taşınamadı");
            }

            $this->slider->update([
                'image_path' => $finalPath,
                'is_active' => true,
            ]);

            HomeSlider::clearAllCache();

            Log::info("Slider image işleme tamamlandı", [
                'slider_id' => $this->slider->id,
                'final_path' => $finalPath,
                'file_size' => $fileSize
            ]);

        } catch (Exception $e) {
            Log::error("Slider image işleme hatası", [
                'slider_id' => $this->slider->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (Storage::exists($this->tempImagePath)) {
                Storage::delete($this->tempImagePath);
            }

            $this->slider->update(['is_active' => false]);

            throw $e;
        }
    }

    private function generateUniqueFileName(string $extension): string
    {
        return sprintf(
            'slider_%d_%s.%s',
            $this->slider->id,
            uniqid(),
            $extension
        );
    }

    public function failed(Exception $exception): void
    {
        Log::error("Slider image upload job başarısız oldu", [
            'slider_id' => $this->slider->id,
            'error' => $exception->getMessage()
        ]);

        if (Storage::exists($this->tempImagePath)) {
            Storage::delete($this->tempImagePath);
        }

        $this->slider->update(['is_active' => false]);
    }
}
