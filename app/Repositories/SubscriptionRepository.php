<?php

namespace App\Repositories;

use App\Models\UserSubscription;
use App\Repositories\Interfaces\SubscriptionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    /**
     * Tüm abonelikleri listele (filtreleme ile)
     */
    public function getAllSubscriptions(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = UserSubscription::query()->withRelations();

        // User ID filtresi
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Status filtresi
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Subscription type filtresi
        if (!empty($filters['subscription_type'])) {
            $query->where('subscription_type', $filters['subscription_type']);
        }

        // Plan ID filtresi
        if (!empty($filters['plan_id'])) {
            $query->where('subscription_plan_id', $filters['plan_id']);
        }

        // Tarih aralığı filtresi
        if (!empty($filters['start_date'])) {
            $query->where('started_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('started_at', '<=', $filters['end_date']);
        }

        // Arama (kullanıcı adı/email)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Kullanıcının tüm aboneliklerini getir
     */
    public function getUserSubscriptions(int $userId): Collection
    {
        return UserSubscription::forUser($userId)
            ->withRelations()
            ->latest()
            ->get();
    }

    /**
     * Kullanıcının aktif aboneliğini getir
     */
    public function getUserActiveSubscription(int $userId): ?UserSubscription
    {
        return UserSubscription::forUser($userId)
            ->active()
            ->withRelations()
            ->first();
    }

    /**
     * Aktif abonelikleri listele
     */
    public function getActiveSubscriptions(int $perPage = 20): LengthAwarePaginator
    {
        return UserSubscription::active()
            ->withRelations()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Süresi dolmuş abonelikleri listele
     */
    public function getExpiredSubscriptions(int $perPage = 20): LengthAwarePaginator
    {
        return UserSubscription::expired()
            ->withRelations()
            ->latest('expires_at')
            ->paginate($perPage);
    }

    /**
     * Yakında sona erecek abonelikleri getir
     */
    public function getExpiringSubscriptions(int $days = 7, int $perPage = 20): LengthAwarePaginator
    {
        return UserSubscription::expiringSoon($days)
            ->withRelations()
            ->orderBy('expires_at')
            ->paginate($perPage);
    }

    /**
     * ID ile abonelik bul
     */
    public function findSubscription(int $id): ?UserSubscription
    {
        return UserSubscription::with([
            'user:id,name,email',
            'subscriptionPlan:id,name,duration_days,price',
            'createdBy:id,name'
        ])->find($id);
    }

    /**
     * Manuel abonelik oluştur (Admin tarafından)
     */
    public function createManualSubscription(array $data): UserSubscription
    {
        DB::beginTransaction();
        try {
            $subscription = UserSubscription::create([
                'user_id' => $data['user_id'],
                'subscription_plan_id' => $data['subscription_plan_id'],
                'started_at' => $data['started_at'] ?? now(),
                'expires_at' => $data['expires_at'],
                'status' => UserSubscription::STATUS_ACTIVE,
                'subscription_type' => UserSubscription::TYPE_MANUAL,
                'payment_method' => 'manual',
                'transaction_id' => null,
                'created_by' => $data['created_by'],
                'admin_note' => $data['admin_note'] ?? null,
            ]);

            DB::commit();
            return $subscription->load(['user', 'subscriptionPlan', 'createdBy']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Abonelik güncelle
     */
    public function updateSubscription(UserSubscription $subscription, array $data): bool
    {
        return $subscription->update($data);
    }

    /**
     * Abonelik sil
     */
    public function deleteSubscription(UserSubscription $subscription): bool
    {
        return $subscription->delete();
    }

    /**
     * Abonelik süresini uzat
     */
    public function extendSubscription(UserSubscription $subscription, int $days): bool
    {
        return $subscription->extend($days);
    }

    /**
     * Aboneliği iptal et
     */
    public function cancelSubscription(UserSubscription $subscription, ?string $reason = null): bool
    {
        return $subscription->cancel($reason);
    }

    /**
     * Aboneliği aktif et (cancelled'dan active'e)
     */
    public function activateSubscription(UserSubscription $subscription): bool
    {
        return $subscription->update([
            'status' => UserSubscription::STATUS_ACTIVE,
        ]);
    }

    /**
     * Abonelik yenile (renew)
     */
    public function renewSubscription(UserSubscription $subscription, int $durationDays, ?string $transactionId = null): bool
    {
        return $subscription->renew($durationDays, $transactionId);
    }

    /**
     * Süresi dolmuş abonelikleri toplu güncelle (Cron için)
     */
    public function expireOldSubscriptions(): int
    {
        return UserSubscription::expireOldSubscriptions();
    }

    /**
     * Abonelik istatistiklerini getir
     */
    public function getSubscriptionStats(): array
    {
        $stats = UserSubscription::getStatistics();

        // Ek istatistikler
        $stats['this_month'] = UserSubscription::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $stats['last_month'] = UserSubscription::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        return $stats;
    }

    /**
     * Gelir istatistiklerini getir
     */
    public function getRevenueStats(?string $startDate = null, ?string $endDate = null): array
    {
        $query = UserSubscription::paid()
            ->join('subscription_plans', 'user_subscriptions.subscription_plan_id', '=', 'subscription_plans.id');

        if ($startDate) {
            $query->where('user_subscriptions.created_at', '>=', Carbon::parse($startDate));
        }

        if ($endDate) {
            $query->where('user_subscriptions.created_at', '<=', Carbon::parse($endDate));
        }

        $totalRevenue = $query->sum('subscription_plans.price');

        // Aylık gelir
        $monthlyRevenue = UserSubscription::paid()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->join('subscription_plans', 'user_subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->sum('subscription_plans.price');

        // Yıllık gelir
        $yearlyRevenue = UserSubscription::paid()
            ->whereYear('created_at', now()->year)
            ->join('subscription_plans', 'user_subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->sum('subscription_plans.price');

        return [
            'total_revenue' => (float) $totalRevenue,
            'monthly_revenue' => (float) $monthlyRevenue,
            'yearly_revenue' => (float) $yearlyRevenue,
            'currency' => 'TRY',
        ];
    }

    /**
     * Plana göre abonelikleri getir
     */
    public function getSubscriptionsByPlan(int $planId, int $perPage = 20): LengthAwarePaginator
    {
        return UserSubscription::where('subscription_plan_id', $planId)
            ->withRelations()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Manuel abonelikleri listele
     */
    public function getManualSubscriptions(int $perPage = 20): LengthAwarePaginator
    {
        return UserSubscription::manual()
            ->withRelations()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Ödeme yapılan abonelikleri listele
     */
    public function getPaidSubscriptions(int $perPage = 20): LengthAwarePaginator
    {
        return UserSubscription::paid()
            ->withRelations()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * İlişkilerle birlikte getir
     */
    public function loadRelations(UserSubscription $subscription): UserSubscription
    {
        return $subscription->load([
            'user:id,name,email',
            'subscriptionPlan:id,name,duration_days,price',
            'createdBy:id,name'
        ]);
    }
}
