<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class UserStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 4;

    // Polling ile otomatik yenileme (30 saniyede bir)
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $stats = Cache::remember('user_stats_widget', now()->addMinutes(5), function () {
            return [
                'total' => User::count(),
            ];
        });

        return [
            Stat::make('Toplam Kullanıcılar', $stats['total'])
                ->description('Sistemde kayıtlı tüm kullanıcılar')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary')
                ->chart([7, 8, 9, 10, 11, 12, 13, 14]),
        ];
    }
}
