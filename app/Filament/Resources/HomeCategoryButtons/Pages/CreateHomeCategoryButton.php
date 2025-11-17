<?php

namespace App\Filament\Resources\HomeCategoryButtons\Pages;

use App\Filament\Resources\HomeCategoryButtons\HomeCategoryButtonResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHomeCategoryButton extends CreateRecord
{
    protected static string $resource = HomeCategoryButtonResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Kategori butonu oluşturuldu!';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Position kontrolü - aynı position'da başka kayıt var mı?
        $exists = \App\Models\HomeCategoryButton::where('position', $data['position'])->exists();

        if ($exists) {
            throw new \Exception('Bu pozisyonda zaten bir buton mevcut. Lütfen önce mevcut butonu silin veya güncelleyin.');
        }

        return $data;
    }
}
