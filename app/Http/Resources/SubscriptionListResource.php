<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Abonelik Özet Resource (Liste için)
 */
class SubscriptionListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'plan_name' => $this->subscriptionPlan->name,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'subscription_type' => $this->subscription_type,
            'expires_at' => $this->expires_at?->format('Y-m-d'),
            'days_remaining' => $this->daysRemaining(),
            'is_active' => $this->isActive(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
