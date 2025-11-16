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

class ProcessVideoUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Job başarısız olursa kaç kez tekrar denenecek
     */
    public $tries = 3;

    /**
     * Job timeout süresi (saniye) - Video boyutuna göre artırabilirsiniz
     */
    public $timeout = 300; // 5 dakika

    /**
     * Video model instance
     */
    public Video $video;

    /**
     * Geçici video dosya yolu
     */
    public string $tempVideoPath;

    /**
     * Hedef klasör
     */
    public string $targetFolder;

    /**
     * Create a new job instance.
     */
    public function __construct(Video $video, string $tempVideoPath, string $targetFolder = 'videos/processed')
    {
        $this->video = $video;
        $this->tempVideoPath = $tempVideoPath;
        $this->targetFolder = $targetFolder;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Video işleme başladı", [
                'video_id' => $this->video->id,
                'temp_path' => $this->tempVideoPath
            ]);

            // 1. Geçici dosyanın var olduğunu kontrol et (PUBLIC disk'te)
            if (!Storage::disk('public')->exists($this->tempVideoPath)) {
                throw new Exception("Geçici video dosyası bulunamadı: {$this->tempVideoPath}");
            }

            // 2. Dosya boyutunu kontrol et
            $fileSize = Storage::disk('public')->size($this->tempVideoPath);
            if ($fileSize === 0) {
                throw new Exception("Video dosyası boş");
            }

            // 3. Yeni dosya adı oluştur (güvenli ve unique)
            $extension = pathinfo($this->tempVideoPath, PATHINFO_EXTENSION);
            $newFileName = $this->generateUniqueFileName($extension);
            $finalPath = "{$this->targetFolder}/{$newFileName}";

            // 4. Dosyayı hedef klasöre taşı (public disk içinde)
            if (!Storage::disk('public')->move($this->tempVideoPath, $finalPath)) {
                throw new Exception("Video dosyası taşınamadı");
            }

            // 5. Video modelini güncelle
            $this->video->update([
                'video_path' => $finalPath,
                'is_processed' => true, // İşleme tamamlandı
            ]);

            Log::info("Video işleme tamamlandı", [
                'video_id' => $this->video->id,
                'final_path' => $finalPath,
                'file_size' => $fileSize
            ]);

        } catch (Exception $e) {
            Log::error("Video işleme hatası", [
                'video_id' => $this->video->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Geçici dosyayı temizle
            if (Storage::disk('public')->exists($this->tempVideoPath)) {
                Storage::disk('public')->delete($this->tempVideoPath);
            }

            throw $e; // Job failed olarak işaretlensin
        }
    }

    /**
     * Unique dosya adı oluştur
     */
    private function generateUniqueFileName(string $extension): string
    {
        return sprintf(
            'video_%d_%s.%s',
            $this->video->id,
            uniqid(),
            $extension
        );
    }

    /**
     * Job başarısız olduğunda çalışır
     */
    public function failed(Exception $exception): void
    {
        Log::error("Video upload job başarısız oldu", [
            'video_id' => $this->video->id,
            'error' => $exception->getMessage()
        ]);

        // Geçici dosyayı temizle
        if (Storage::disk('public')->exists($this->tempVideoPath)) {
            Storage::disk('public')->delete($this->tempVideoPath);
        }

        // Video'yu inactive yap
        $this->video->update([
            'is_active' => false,
        ]);
    }
}
