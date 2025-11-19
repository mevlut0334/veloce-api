<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\UserSubscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class SubscriptionStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    // Polling ile otomatik yenileme (30 saniyede bir)
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $stats = Cache::remember('subscription_stats_widget', now()->addMinutes(5), function () {
            return [
                'this_week' => UserSubscription::whereBetween('starts_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'this_month' => UserSubscription::whereBetween('starts_at', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ])->count(),
                'active' => UserSubscription::active()->count(),
                'expiring_7days' => UserSubscription::expiringSoon(7)->count(),
            ];
        });

        return [
            Stat::make('Bu Hafta Başlatılan', $stats['this_week'])
                ->description('Bu hafta oluşturulan abonelikler')
                ->descriptionIcon('heroicon-o-rocket-launch')
                ->color('success')
                ->chart([1, 2, 3, 2, 4, 3, 5, 4]),

            Stat::make('Bu Ay Başlatılan', $stats['this_month'])
                ->description('Bu ay oluşturulan abonelikler')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info')
                ->chart([2, 3, 4, 5, 6, 7, 8, 9]),

            Stat::make('Toplam Aktif Abonelikler', $stats['active'])
                ->description('Şu anda aktif olan abonelikler')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('primary')
                ->chart([3, 4, 5, 6, 7, 8, 9, 10]),

            Stat::make('7 Gün İçinde Bitenler', $stats['expiring_7days'])
                ->description('Acil takip gerekli')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->chart([2, 3, 2, 4, 3, 5, 4, 5]),
        ];
    }
}
