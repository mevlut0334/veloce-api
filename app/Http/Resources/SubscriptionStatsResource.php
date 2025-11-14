<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Abonelik İstatistikleri Resource
 */
class SubscriptionStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_subscriptions' => $this->resource['total_count'] ?? 0,
            'active_subscriptions' => $this->resource['active_count'] ?? 0,
            'expired_subscriptions' => $this->resource['expired_count'] ?? 0,
            'cancelled_subscriptions' => $this->resource['cancelled_count'] ?? 0,
            'manual_subscriptions' => $this->resource['manual_count'] ?? 0,
            'paid_subscriptions' => $this->resource['paid_count'] ?? 0,
            'this_month' => $this->resource['this_month'] ?? 0,
            'last_month' => $this->resource['last_month'] ?? 0,
            'growth_rate' => $this->calculateGrowthRate(),
        ];
    }

    private function calculateGrowthRate(): ?float
    {
        $thisMonth = $this->resource['this_month'] ?? 0;
        $lastMonth = $this->resource['last_month'] ?? 0;

        if ($lastMonth === 0) {
            return $thisMonth > 0 ? 100.0 : 0.0;
        }

        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
    }
}
