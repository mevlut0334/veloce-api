<?php

namespace App\Filament\Resources\Videos\Pages;

use App\Filament\Resources\Videos\VideoResource;
use App\Services\Contracts\VideoServiceInterface;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class CreateVideo extends CreateRecord
{
    protected static string $resource = VideoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Video ve thumbnail file'ları kaldır - bunlar handleRecordCreation'da işlenecek
        unset($data['video']);
        unset($data['thumbnail']);

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $videoService = app(VideoServiceInterface::class);

        try {
            // Form'dan tüm state'i al
            $formState = $this->form->getState();

            // Video ve thumbnail yollarını al
            $tempVideoPath = null;
            $tempThumbnailPath = null;

            // Video dosyasını işle
            if (!empty($formState['video'])) {
                $videoPath = is_array($formState['video']) ? $formState['video'][0] : $formState['video'];

                // Filament'in yüklediği dosya public disk'te
                if (Storage::disk('public')->exists($videoPath)) {
                    // Yeni bir geçici isim oluştur
                    $newFileName = uniqid('video_') . '_' . time() . '.' . pathinfo($videoPath, PATHINFO_EXTENSION);
                    $tempVideoPath = 'videos/temp/' . $newFileName;

                    // Dosyayı kalıcı temp klasörüne kopyala
                    Storage::disk('public')->copy($videoPath, $tempVideoPath);

                    // Orijinal Filament dosyasını sil
                    Storage::disk('public')->delete($videoPath);

                    Log::info('Video dosyası temp klasörüne kopyalandı', [
                        'original' => $videoPath,
                        'new_temp' => $tempVideoPath
                    ]);
                } else {
                    Log::warning('Video dosyası bulunamadı', ['path' => $videoPath]);
                }
            }

            // Thumbnail dosyasını işle
            if (!empty($formState['thumbnail'])) {
                $thumbnailPath = is_array($formState['thumbnail']) ? $formState['thumbnail'][0] : $formState['thumbnail'];

                if (Storage::disk('public')->exists($thumbnailPath)) {
                    // Yeni bir geçici isim oluştur
                    $newFileName = uniqid('thumb_') . '_' . time() . '.' . pathinfo($thumbnailPath, PATHINFO_EXTENSION);
                    $tempThumbnailPath = 'thumbnails/temp/' . $newFileName;

                    // Dosyayı kalıcı temp klasörüne kopyala
                    Storage::disk('public')->copy($thumbnailPath, $tempThumbnailPath);

                    // Orijinal Filament dosyasını sil
                    Storage::disk('public')->delete($thumbnailPath);

                    Log::info('Thumbnail dosyası temp klasörüne kopyalandı', [
                        'original' => $thumbnailPath,
                        'new_temp' => $tempThumbnailPath
                    ]);
                } else {
                    Log::warning('Thumbnail dosyası bulunamadı', ['path' => $thumbnailPath]);
                }
            }

            // Kategori ve tag'leri ekle
            $data['category_ids'] = $formState['category_ids'] ?? [];
            $data['tag_ids'] = $formState['tag_ids'] ?? [];

            // VideoService için UploadedFile nesneleri oluştur (değil, path gönder)
            // VideoService içinde zaten path kullanılıyor, UploadedFile'a gerek yok

            // DÜZELTME: VideoService'e dosya nesnesi değil path göndereceğiz
            // Bu yüzden VideoService'i de düzeltmemiz gerekiyor

            // Şimdilik mevcut yapıya uygun UploadedFile oluştur
            $videoFile = null;
            $thumbnailFile = null;

            if ($tempVideoPath) {
                $fullPath = Storage::disk('public')->path($tempVideoPath);
                if (file_exists($fullPath)) {
                    $videoFile = new UploadedFile(
                        $fullPath,
                        basename($tempVideoPath),
                        mime_content_type($fullPath),
                        null,
                        true
                    );
                }
            }

            if ($tempThumbnailPath) {
                $fullPath = Storage::disk('public')->path($tempThumbnailPath);
                if (file_exists($fullPath)) {
                    $thumbnailFile = new UploadedFile(
                        $fullPath,
                        basename($tempThumbnailPath),
                        mime_content_type($fullPath),
                        null,
                        true
                    );
                }
            }

            // Video oluştur
            $video = $videoService->createVideo(
                $data,
                $videoFile,
                $thumbnailFile
            );

            return $video;

        } catch (\Exception $e) {
            Log::error('Filament Video Upload Hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Video yükleniyor! İşlem tamamlandığında aktif hale gelecek.';
    }
}
