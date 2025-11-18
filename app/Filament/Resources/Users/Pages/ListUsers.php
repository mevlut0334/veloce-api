<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Yeni Kullanıcı')
                ->disabled()
                ->tooltip('Kullanıcılar sadece kayıt sayfasından oluşturulabilir'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\Users\Widgets\UserStatsWidget::class,
            \App\Filament\Resources\Users\Widgets\SubscriptionStatsWidget::class,
        ];
    }
}
