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

            // 1. Geçici dosyanın var olduğunu kontrol et
            if (!Storage::exists($this->tempVideoPath)) {
                throw new Exception("Geçici video dosyası bulunamadı: {$this->tempVideoPath}");
            }

            // 2. Dosya boyutunu kontrol et
            $fileSize = Storage::size($this->tempVideoPath);
            if ($fileSize === 0) {
                throw new Exception("Video dosyası boş");
            }

            // 3. Yeni dosya adı oluştur (güvenli ve unique)
            $extension = pathinfo($this->tempVideoPath, PATHINFO_EXTENSION);
            $newFileName = $this->generateUniqueFileName($extension);
            $finalPath = "{$this->targetFolder}/{$newFileName}";

            // 4. Dosyayı hedef klasöre taşı
            if (!Storage::move($this->tempVideoPath, $finalPath)) {
                throw new Exception("Video dosyası taşınamadı");
            }

            // 5. Video modelini güncelle
            $this->video->update([
                'video_path' => $finalPath,
                'is_active' => true, // İşlem tamamlandı, aktif yap
            ]);

            // 6. Cache temizleme (varsa)
            // Cache::forget("video_{$this->video->id}");

            Log::info("Video işleme tamamlandı", [
                'video_id' => $this->video->id,
                'final_path' => $finalPath,
                'file_size' => $fileSize
            ]);

            // 7. (Opsiyonel) Video optimize job'u tetikle
            // OptimizeVideo::dispatch($this->video);

        } catch (Exception $e) {
            Log::error("Video işleme hatası", [
                'video_id' => $this->video->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Geçici dosyayı temizle
            if (Storage::exists($this->tempVideoPath)) {
                Storage::delete($this->tempVideoPath);
            }

            // Video'yu inactive yap
            $this->video->update(['is_active' => false]);

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
        if (Storage::exists($this->tempVideoPath)) {
            Storage::delete($this->tempVideoPath);
        }

        // Video'yu inactive yap ve hata mesajı ekle
        $this->video->update([
            'is_active' => false,
            // 'error_message' => $exception->getMessage() // Eğer bu kolon varsa
        ]);

        // (Opsiyonel) Admin'e bildirim gönder
        // Notification::send(User::admins(), new VideoProcessingFailed($this->video));
    }
}
