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

            if (!Storage::disk('public')->exists($this->video->video_path)) {
                throw new Exception("Video dosyası bulunamadı: {$this->video->video_path}");
            }

            // Video süresini hesapla (FFmpeg gerekli)
            $duration = $this->getVideoDuration();

            // Video boyutlarını ve rotation'ı al
            $videoInfo = $this->getVideoInfo();

            // Video yönünü belirle (rotation metadata'sını dikkate alarak)
            $orientation = $this->determineOrientation($videoInfo);

            // Video bilgilerini güncelle
            $this->video->update([
                'duration' => $duration,
                'orientation' => $orientation,
            ]);

            Log::info("Video optimizasyonu tamamlandı", [
                'video_id' => $this->video->id,
                'duration' => $duration,
                'orientation' => $orientation,
                'width' => $videoInfo['width'],
                'height' => $videoInfo['height'],
                'rotation' => $videoInfo['rotation']
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
            $fullPath = Storage::disk('public')->path($this->video->video_path);

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

    /**
     * Video boyutlarını ve rotation metadata'sını al
     */
    private function getVideoInfo(): array
    {
        try {
            $fullPath = Storage::disk('public')->path($this->video->video_path);

            // Genişlik
            $widthCommand = "ffprobe -v error -select_streams v:0 -show_entries stream=width -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($fullPath);
            $width = (int) trim(shell_exec($widthCommand));

            // Yükseklik
            $heightCommand = "ffprobe -v error -select_streams v:0 -show_entries stream=height -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($fullPath);
            $height = (int) trim(shell_exec($heightCommand));

            // Rotation metadata (mobil videolar için kritik)
            $rotationCommand = "ffprobe -v error -select_streams v:0 -show_entries stream_tags=rotate -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($fullPath);
            $rotation = (int) trim(shell_exec($rotationCommand));

            // Eğer rotation bulunamazsa, display_rotation'ı dene (alternatif metadata)
            if ($rotation === 0) {
                $displayRotationCommand = "ffprobe -v error -select_streams v:0 -show_entries stream_side_data=rotation -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($fullPath);
                $displayRotation = trim(shell_exec($displayRotationCommand));
                if (!empty($displayRotation)) {
                    $rotation = (int) $displayRotation;
                }
            }

            return [
                'width' => $width ?: 1920,
                'height' => $height ?: 1080,
                'rotation' => $rotation
            ];

        } catch (Exception $e) {
            Log::warning("Video bilgileri alınamadı", [
                'video_id' => $this->video->id,
                'error' => $e->getMessage()
            ]);
            return [
                'width' => 1920,
                'height' => 1080,
                'rotation' => 0
            ];
        }
    }

    /**
     * Video yönünü rotation metadata'sını dikkate alarak belirle
     *
     * @return int 0 (horizontal) veya 1 (vertical)
     */
    private function determineOrientation(array $videoInfo): int
    {
        $width = $videoInfo['width'];
        $height = $videoInfo['height'];
        $rotation = $videoInfo['rotation'];

        // 90° veya 270° rotation varsa, boyutları swap et
        // Çünkü bu videolar fiziksel olarak yatay ama ekranda dikey gösterilir
        if ($rotation === 90 || $rotation === 270 || $rotation === -90 || $rotation === -270) {
            // Boyutları değiştir
            [$width, $height] = [$height, $width];

            Log::info("Video rotation tespit edildi, boyutlar swap edildi", [
                'video_id' => $this->video->id,
                'original_width' => $videoInfo['width'],
                'original_height' => $videoInfo['height'],
                'rotation' => $rotation,
                'effective_width' => $width,
                'effective_height' => $height
            ]);
        }

        // Şimdi gerçek görüntü boyutlarına göre orientation belirle
        if ($width > $height) {
            return Video::ORIENTATION_HORIZONTAL; // 0
        } elseif ($height > $width) {
            return Video::ORIENTATION_VERTICAL; // 1
        } else {
            // Kare video (1:1) - nadir durum, varsayılan olarak horizontal kabul et
            return Video::ORIENTATION_HORIZONTAL; // 0
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
