<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessThumbnailUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public Video $video;
    public string $tempThumbnailPath;
    public string $targetFolder;

    public function __construct(Video $video, string $tempThumbnailPath, string $targetFolder = 'thumbnails/processed')
    {
        $this->video = $video;
        $this->tempThumbnailPath = $tempThumbnailPath;
        $this->targetFolder = $targetFolder;
    }

    public function handle(): void
    {
        try {
            Log::info("Thumbnail işleme başladı", [
                'video_id' => $this->video->id,
                'temp_path' => $this->tempThumbnailPath
            ]);

            if (!Storage::exists($this->tempThumbnailPath)) {
                throw new Exception("Geçici thumbnail dosyası bulunamadı: {$this->tempThumbnailPath}");
            }

            $fileSize = Storage::size($this->tempThumbnailPath);
            if ($fileSize === 0) {
                throw new Exception("Thumbnail dosyası boş");
            }

            $extension = pathinfo($this->tempThumbnailPath, PATHINFO_EXTENSION);
            $newFileName = $this->generateUniqueFileName($extension);
            $finalPath = "{$this->targetFolder}/{$newFileName}";

            if (!Storage::move($this->tempThumbnailPath, $finalPath)) {
                throw new Exception("Thumbnail dosyası taşınamadı");
            }

            $this->video->update([
                'thumbnail_path' => $finalPath,
            ]);

            Log::info("Thumbnail işleme tamamlandı", [
                'video_id' => $this->video->id,
                'final_path' => $finalPath,
                'file_size' => $fileSize
            ]);

        } catch (Exception $e) {
            Log::error("Thumbnail işleme hatası", [
                'video_id' => $this->video->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (Storage::exists($this->tempThumbnailPath)) {
                Storage::delete($this->tempThumbnailPath);
            }

            throw $e;
        }
    }

    private function generateUniqueFileName(string $extension): string
    {
        return sprintf(
            'thumb_%d_%s.%s',
            $this->video->id,
            uniqid(),
            $extension
        );
    }

    public function failed(Exception $exception): void
    {
        Log::error("Thumbnail upload job başarısız oldu", [
            'video_id' => $this->video->id,
            'error' => $exception->getMessage()
        ]);

        if (Storage::exists($this->tempThumbnailPath)) {
            Storage::delete($this->tempThumbnailPath);
        }
    }
}
