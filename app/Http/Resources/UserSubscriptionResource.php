<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Kullanıcı Abonelik Resource (Kullanıcı için basit)
 */
class UserSubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plan_name' => $this->subscriptionPlan->name,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'started_at' => $this->started_at?->format('d.m.Y'),
            'expires_at' => $this->expires_at?->format('d.m.Y'),
            'days_remaining' => $this->daysRemaining(),
            'is_active' => $this->isActive(),
            'features' => $this->subscriptionPlan->features ?? [],
        ];
    }
}
