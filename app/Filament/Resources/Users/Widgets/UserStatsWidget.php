<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class UserStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    // Polling ile otomatik yenileme (30 saniyede bir)
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $stats = Cache::remember('user_stats_widget', now()->addMinutes(5), function () {
            return [
                'total' => User::count(),
                'active' => User::active()->count(),
                'inactive' => User::inactive()->count(),
                'subscribers' => User::subscribers()->count(),
                'non_subscribers' => User::nonSubscribers()->count(),
                'recent_activity' => User::recentActivity(30)->count(),
                'this_month' => User::whereBetween('created_at', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ])->count(),
            ];
        });

        return [
            Stat::make('Toplam Kullanıcı', $stats['total'])
                ->description('Sistemde kayıtlı tüm kullanıcılar')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Aktif Kullanıcılar', $stats['active'])
                ->description($stats['inactive'] . ' pasif kullanıcı var')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->chart([3, 2, 4, 3, 5, 4, 6, 5]),

            Stat::make('Aktif Aboneler', $stats['subscribers'])
                ->description($stats['non_subscribers'] . ' abone olmayan')
                ->descriptionIcon('heroicon-o-credit-card')
                ->color('warning')
                ->chart([2, 3, 3, 4, 5, 6, 7, 8]),

            Stat::make('Son 30 Gün Aktif', $stats['recent_activity'])
                ->description('Son aktivite kaydı')
                ->descriptionIcon('heroicon-o-clock')
                ->color('info')
                ->chart([4, 5, 3, 6, 4, 5, 7, 6]),

            Stat::make('Bu Ay Kayıt', $stats['this_month'])
                ->description('Bu ay sisteme katılanlar')
                ->descriptionIcon('heroicon-o-user-plus')
                ->color('success')
                ->chart([1, 2, 2, 3, 4, 3, 5, 4]),
        ];
    }
}
