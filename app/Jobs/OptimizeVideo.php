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

class OptimizeVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 600; // 10 dakika

    public Video $video;

    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    public function handle(): void
    {
        try {
            Log::info("Video optimizasyonu başladı", [
                'video_id' => $this->video->id,
                'video_path' => $this->video->video_path
            ]);

            if (!Storage::exists($this->video->video_path)) {
                throw new Exception("Video dosyası bulunamadı: {$this->video->video_path}");
            }

            // Video süresini hesapla (FFmpeg gerekli)
            $duration = $this->getVideoDuration();

            // Video boyutlarını al
            $dimensions = $this->getVideoDimensions();

            // Video yönünü belirle
            $orientation = $dimensions['width'] > $dimensions['height']
                ? Video::ORIENTATION_HORIZONTAL
                : Video::ORIENTATION_VERTICAL;

            // Video bilgilerini güncelle
            $this->video->update([
                'duration' => $duration,
                'orientation' => $orientation,
            ]);

            Log::info("Video optimizasyonu tamamlandı", [
                'video_id' => $this->video->id,
                'duration' => $duration,
                'orientation' => $orientation,
                'dimensions' => $dimensions
            ]);

        } catch (Exception $e) {
            Log::error("Video optimizasyon hatası", [
                'video_id' => $this->video->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    private function getVideoDuration(): int
    {
        try {
            $fullPath = Storage::path($this->video->video_path);

            // FFmpeg ile video süresini al
            $command = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($fullPath);
            $output = shell_exec($command);

            return (int) round((float) $output);

        } catch (Exception $e) {
            Log::warning("Video süresi hesaplanamadı", [
                'video_id' => $this->video->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    private function getVideoDimensions(): array
    {
        try {
            $fullPath = Storage::path($this->video->video_path);

            // Genişlik
            $widthCommand = "ffprobe -v error -select_streams v:0 -show_entries stream=width -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($fullPath);
            $width = (int) shell_exec($widthCommand);

            // Yükseklik
            $heightCommand = "ffprobe -v error -select_streams v:0 -show_entries stream=height -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($fullPath);
            $height = (int) shell_exec($heightCommand);

            return [
                'width' => $width ?: 1920,
                'height' => $height ?: 1080
            ];

        } catch (Exception $e) {
            Log::warning("Video boyutları hesaplanamadı", [
                'video_id' => $this->video->id,
                'error' => $e->getMessage()
            ]);
            return ['width' => 1920, 'height' => 1080];
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error("Video optimization job başarısız oldu", [
            'video_id' => $this->video->id,
            'error' => $exception->getMessage()
        ]);
    }
}
