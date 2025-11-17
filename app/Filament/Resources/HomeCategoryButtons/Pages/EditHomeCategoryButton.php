<?php

namespace App\Filament\Resources\HomeCategoryButtons\Pages;

use App\Filament\Resources\HomeCategoryButtons\HomeCategoryButtonResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHomeCategoryButton extends EditRecord
{
    protected static string $resource = HomeCategoryButtonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Kategori butonu güncellendi!';
    }
}
