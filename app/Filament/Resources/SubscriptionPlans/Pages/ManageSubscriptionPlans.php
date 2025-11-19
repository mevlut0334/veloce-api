<?php

namespace App\Filament\Resources\SubscriptionPlans\Pages;

use App\Filament\Resources\SubscriptionPlans\SubscriptionPlanResource;
use Filament\Resources\Pages\ManageRecords;

class ManageSubscriptionPlans extends ManageRecords
{
    protected static string $resource = SubscriptionPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Create butonu kaldırıldı - Sadece mevcut planları düzenleyebilirsiniz
        ];
    }

    public function getTitle(): string
    {
        return 'Abonelik Planları';
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
