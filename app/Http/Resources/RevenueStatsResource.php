<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Gelir İstatistikleri Resource
 */
class RevenueStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_revenue' => [
                'amount' => number_format($this->resource['total_revenue'], 2, '.', ''),
                'formatted' => $this->formatCurrency($this->resource['total_revenue']),
                'currency' => $this->resource['currency'] ?? 'TRY',
            ],
            'monthly_revenue' => [
                'amount' => number_format($this->resource['monthly_revenue'], 2, '.', ''),
                'formatted' => $this->formatCurrency($this->resource['monthly_revenue']),
                'currency' => $this->resource['currency'] ?? 'TRY',
            ],
            'yearly_revenue' => [
                'amount' => number_format($this->resource['yearly_revenue'], 2, '.', ''),
                'formatted' => $this->formatCurrency($this->resource['yearly_revenue']),
                'currency' => $this->resource['currency'] ?? 'TRY',
            ],
            'stats' => [
                'avg_transaction' => $this->calculateAverageTransaction(),
                'total_transactions' => $this->resource['total_transactions'] ?? 0,
            ],
        ];
    }

    private function formatCurrency(float $amount): string
    {
        return number_format($amount, 2, ',', '.') . ' ₺';
    }

    private function calculateAverageTransaction(): string
    {
        $total = $this->resource['total_revenue'] ?? 0;
        $count = $this->resource['total_transactions'] ?? 0;

        if ($count === 0) {
            return $this->formatCurrency(0);
        }

        return $this->formatCurrency($total / $count);
    }
}
