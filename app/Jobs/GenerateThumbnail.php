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

class GenerateThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 180;

    public Video $video;
    public int $timeInSeconds;
    public string $targetFolder;

    public function __construct(Video $video, int $timeInSeconds = 2, string $targetFolder = 'thumbnails/auto')
    {
        $this->video = $video;
        $this->timeInSeconds = $timeInSeconds;
        $this->targetFolder = $targetFolder;
    }

    public function handle(): void
    {
        try {
            Log::info("Thumbnail oluşturma başladı", [
                'video_id' => $this->video->id,
                'video_path' => $this->video->video_path,
                'time' => $this->timeInSeconds
            ]);

            if (!Storage::exists($this->video->video_path)) {
                throw new Exception("Video dosyası bulunamadı: {$this->video->video_path}");
            }

            $thumbnailPath = $this->generateThumbnailFromVideo();

            if ($thumbnailPath) {
                $this->video->update([
                    'thumbnail_path' => $thumbnailPath,
                ]);

                Log::info("Thumbnail oluşturma tamamlandı", [
                    'video_id' => $this->video->id,
                    'thumbnail_path' => $thumbnailPath
                ]);
            }

        } catch (Exception $e) {
            Log::error("Thumbnail oluşturma hatası", [
                'video_id' => $this->video->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    private function generateThumbnailFromVideo(): ?string
    {
        try {
            $videoPath = Storage::path($this->video->video_path);
            $thumbnailName = sprintf('thumb_%d_%s.jpg', $this->video->id, uniqid());
            $thumbnailFullPath = Storage::path("{$this->targetFolder}/{$thumbnailName}");

            // Klasörü oluştur (eğer yoksa)
            $targetDir = dirname($thumbnailFullPath);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            // FFmpeg ile thumbnail oluştur
            $command = sprintf(
                'ffmpeg -i %s -ss %d -vframes 1 -q:v 2 %s 2>&1',
                escapeshellarg($videoPath),
                $this->timeInSeconds,
                escapeshellarg($thumbnailFullPath)
            );

            $output = shell_exec($command);

            // Dosya oluşturuldu mu kontrol et
            if (file_exists($thumbnailFullPath) && filesize($thumbnailFullPath) > 0) {
                return "{$this->targetFolder}/{$thumbnailName}";
            }

            throw new Exception("Thumbnail dosyası oluşturulamadı");

        } catch (Exception $e) {
            Log::error("Thumbnail generate error", [
                'video_id' => $this->video->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error("Generate thumbnail job başarısız oldu", [
            'video_id' => $this->video->id,
            'error' => $exception->getMessage()
        ]);
    }
}
