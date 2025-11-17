<?php

namespace App\Filament\Resources\HomeCategoryButtons\Pages;

use App\Filament\Resources\HomeCategoryButtons\HomeCategoryButtonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHomeCategoryButtons extends ListRecords
{
    protected static string $resource = HomeCategoryButtonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => \App\Models\HomeCategoryButton::count() < 2), // Max 2 buton
        ];
    }
}
