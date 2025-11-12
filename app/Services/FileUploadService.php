<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Dosyayı geçici klasöre yükle
     */
    public function uploadToTemp(UploadedFile $file, string $folder = 'temp'): string
    {
        try {
            $path = $file->store($folder);

            Log::info('Dosya geçici klasöre yüklendi', [
                'path' => $path,
                'size' => $file->getSize(),
                'mime' => $file->getMimeType()
            ]);

            return $path;

        } catch (\Exception $e) {
            Log::error('Dosya yükleme hatası', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Video dosyasını geçici klasöre yükle
     */
    public function uploadVideoToTemp(UploadedFile $file): string
    {
        $this->validateVideo($file);
        return $this->uploadToTemp($file, 'videos/temp');
    }

    /**
     * Thumbnail dosyasını geçici klasöre yükle
     */
    public function uploadThumbnailToTemp(UploadedFile $file): string
    {
        $this->validateImage($file);
        return $this->uploadToTemp($file, 'thumbnails/temp');
    }

    /**
     * Slider image dosyasını geçici klasöre yükle
     */
    public function uploadSliderImageToTemp(UploadedFile $file): string
    {
        $this->validateImage($file);
        return $this->uploadToTemp($file, 'sliders/temp');
    }

    /**
     * Dosyayı kalıcı klasöre taşı
     */
    public function moveToFinal(string $tempPath, string $finalFolder, ?string $customName = null): string
    {
        try {
            if (!Storage::exists($tempPath)) {
                throw new \Exception("Geçici dosya bulunamadı: {$tempPath}");
            }

            $extension = pathinfo($tempPath, PATHINFO_EXTENSION);
            $fileName = $customName ?? $this->generateUniqueFileName($extension);
            $finalPath = "{$finalFolder}/{$fileName}";

            if (!Storage::move($tempPath, $finalPath)) {
                throw new \Exception("Dosya taşınamadı");
            }

            Log::info('Dosya kalıcı klasöre taşındı', [
                'temp_path' => $tempPath,
                'final_path' => $finalPath
            ]);

            return $finalPath;

        } catch (\Exception $e) {
            Log::error('Dosya taşıma hatası', [
                'temp_path' => $tempPath,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Dosyayı sil
     */
    public function delete(string $path): bool
    {
        try {
            if (Storage::exists($path)) {
                $result = Storage::delete($path);

                Log::info('Dosya silindi', ['path' => $path]);

                return $result;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Dosya silme hatası', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Toplu dosya silme
     */
    public function bulkDelete(array $paths): bool
    {
        try {
            $existingPaths = array_filter($paths, fn($path) => Storage::exists($path));

            if (empty($existingPaths)) {
                return false;
            }

            $result = Storage::delete($existingPaths);

            Log::info('Toplu dosya silindi', [
                'count' => count($existingPaths)
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Toplu dosya silme hatası', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Geçici klasörü temizle
     */
    public function cleanTempFolder(string $folder = 'temp', int $olderThanHours = 24): int
    {
        try {
            $files = Storage::files($folder);
            $deletedCount = 0;
            $threshold = now()->subHours($olderThanHours)->timestamp;

            foreach ($files as $file) {
                $lastModified = Storage::lastModified($file);

                if ($lastModified < $threshold) {
                    Storage::delete($file);
                    $deletedCount++;
                }
            }

            Log::info('Geçici klasör temizlendi', [
                'folder' => $folder,
                'deleted_count' => $deletedCount
            ]);

            return $deletedCount;

        } catch (\Exception $e) {
            Log::error('Geçici klasör temizleme hatası', [
                'folder' => $folder,
                'error' => $e->getMessage()
            ]);

            return 0;
        }
    }

    /**
     * Dosya var mı kontrol et
     */
    public function exists(string $path): bool
    {
        return Storage::exists($path);
    }

    /**
     * Dosya boyutunu getir
     */
    public function getSize(string $path): int
    {
        return Storage::size($path);
    }

    /**
     * Dosya URL'sini getir
     */
    public function getUrl(string $path): string
    {
        return Storage::url($path);
    }

    /**
     * Video doğrulama
     */
    protected function validateVideo(UploadedFile $file): void
    {
        $maxSize = 512000; // 500MB in KB
        $allowedMimes = ['video/mp4', 'video/mpeg', 'video/quicktime'];

        if ($file->getSize() > ($maxSize * 1024)) {
            throw new \Exception("Video dosyası çok büyük. Maksimum 500MB olmalıdır.");
        }

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception("Geçersiz video formatı. Sadece MP4, MPEG, MOV desteklenmektedir.");
        }
    }

    /**
     * Resim doğrulama
     */
    protected function validateImage(UploadedFile $file): void
    {
        $maxSize = 10240; // 10MB in KB
        $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];

        if ($file->getSize() > ($maxSize * 1024)) {
            throw new \Exception("Resim dosyası çok büyük. Maksimum 10MB olmalıdır.");
        }

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception("Geçersiz resim formatı. Sadece JPEG, PNG, JPG, WEBP desteklenmektedir.");
        }
    }

    /**
     * Unique dosya adı oluştur
     */
    protected function generateUniqueFileName(string $extension): string
    {
        return Str::random(40) . '.' . $extension;
    }

    /**
     * Dosya bilgilerini getir
     */
    public function getFileInfo(string $path): array
    {
        if (!Storage::exists($path)) {
            throw new \Exception("Dosya bulunamadı: {$path}");
        }

        return [
            'path' => $path,
            'size' => Storage::size($path),
            'mime_type' => Storage::mimeType($path),
            'last_modified' => Storage::lastModified($path),
            'url' => Storage::url($path),
        ];
    }
}
