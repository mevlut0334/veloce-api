<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\UserSubscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class SubscriptionStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    // Polling ile otomatik yenileme (30 saniyede bir)
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $stats = Cache::remember('subscription_stats_widget', now()->addMinutes(5), function () {
            return [
                'total' => UserSubscription::count(),
                'active' => UserSubscription::active()->count(),
                'expired' => UserSubscription::expired()->count(),
                'expiring_7days' => UserSubscription::expiringSoon(7)->count(),
                'expiring_30days' => UserSubscription::expiringSoon(30)->count(),
                'this_week' => UserSubscription::whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'this_month' => UserSubscription::whereBetween('created_at', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ])->count(),
                'manual' => UserSubscription::manual()->count(),
                'paid' => UserSubscription::paid()->count(),
            ];
        });

        return [
            Stat::make('Toplam Abonelik', $stats['total'])
                ->description('Tüm abonelik kayıtları')
                ->descriptionIcon('heroicon-o-rectangle-stack')
                ->color('primary')
                ->chart([5, 6, 7, 8, 9, 8, 10, 11]),

            Stat::make('Aktif Abonelikler', $stats['active'])
                ->description($stats['expired'] . ' süresi dolmuş')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('success')
                ->chart([3, 4, 5, 6, 7, 8, 9, 10]),

            Stat::make('7 Gün İçinde Bitenler', $stats['expiring_7days'])
                ->description('Acil takip gerekli')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->chart([2, 3, 2, 4, 3, 5, 4, 5]),

            Stat::make('30 Gün İçinde Bitenler', $stats['expiring_30days'])
                ->description('Yenileme için iletişim')
                ->descriptionIcon('heroicon-o-bell-alert')
                ->color('warning')
                ->chart([4, 5, 6, 7, 8, 9, 10, 11]),

            Stat::make('Bu Hafta Oluşturulan', $stats['this_week'])
                ->description('Bu ay: ' . $stats['this_month'])
                ->descriptionIcon('heroicon-o-rocket-launch')
                ->color('info')
                ->chart([1, 2, 3, 2, 4, 3, 5, 4]),

            Stat::make('Ödeme Durumu', $stats['paid'] . ' Ödeme')
                ->description($stats['manual'] . ' manuel abonelik')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success')
                ->chart([3, 4, 5, 6, 7, 8, 9, 10]),
        ];
    }
}
