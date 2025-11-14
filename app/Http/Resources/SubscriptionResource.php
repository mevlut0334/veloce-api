<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Abonelik Resource (Detaylı)
 */
class SubscriptionResource extends JsonResource
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
            'plan' => [
                'id' => $this->subscriptionPlan->id,
                'name' => $this->subscriptionPlan->name,
                'duration_days' => $this->subscriptionPlan->duration_days,
                'price' => $this->subscriptionPlan->price,
                'currency' => $this->subscriptionPlan->currency,
            ],
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'expires_at' => $this->expires_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'subscription_type' => $this->subscription_type,
            'subscription_type_label' => $this->getTypeLabel(),
            'payment_method' => $this->payment_method,
            'transaction_id' => $this->transaction_id,
            'cancellation_reason' => $this->cancellation_reason,
            'admin_note' => $this->admin_note,
            'created_by' => $this->whenLoaded('createdBy', function() {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                ];
            }),
            'days_remaining' => $this->daysRemaining(),
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'is_expiring_soon' => $this->isExpiringSoon(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
