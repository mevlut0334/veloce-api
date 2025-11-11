<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'is_active' => $this->is_active,
            'subscription_type' => $this->subscription_type,
            'last_activity_at' => $this->last_activity_at?->format('d.m.Y H:i'),
            'created_at' => $this->created_at->format('d.m.Y H:i'),

            // Abonelik bilgileri (ilişki yüklüyse)
            'subscription' => $this->when($this->relationLoaded('activeSubscription'), function () {
                return [
                    'is_subscriber' => $this->isSubscriber(),
                    'status' => $this->subscriptionStatus(),
                    'expires_at' => $this->subscriptionExpiry(),
                    'remaining_days' => $this->remainingSubscriptionDays(),
                ];
            }),

            // İstatistikler (istenirse)
            'stats' => $this->when($request->input('with_stats'), function () {
                return $this->getStats();
            }),
        ];
    }

    /**
     * Ek meta data ekle
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
        ];
    }
}
