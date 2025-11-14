<?php

namespace App\Repositories\Interfaces;

use App\Models\UserSubscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SubscriptionRepositoryInterface
{
    /**
     * Tüm abonelikleri listele (filtreleme ile)
     *
     * @param array $filters ['user_id', 'status', 'subscription_type', 'plan_id']
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllSubscriptions(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Kullanıcının tüm aboneliklerini getir
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserSubscriptions(int $userId): Collection;

    /**
     * Kullanıcının aktif aboneliğini getir
     *
     * @param int $userId
     * @return UserSubscription|null
     */
    public function getUserActiveSubscription(int $userId): ?UserSubscription;

    /**
     * Aktif abonelikleri listele
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActiveSubscriptions(int $perPage = 20): LengthAwarePaginator;

    /**
     * Süresi dolmuş abonelikleri listele
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getExpiredSubscriptions(int $perPage = 20): LengthAwarePaginator;

    /**
     * Yakında sona erecek abonelikleri getir
     *
     * @param int $days
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getExpiringSubscriptions(int $days = 7, int $perPage = 20): LengthAwarePaginator;

    /**
     * ID ile abonelik bul
     *
     * @param int $id
     * @return UserSubscription|null
     */
    public function findSubscription(int $id): ?UserSubscription;

    /**
     * Manuel abonelik oluştur (Admin tarafından)
     *
     * @param array $data
     * @return UserSubscription
     */
    public function createManualSubscription(array $data): UserSubscription;

    /**
     * Abonelik güncelle
     *
     * @param UserSubscription $subscription
     * @param array $data
     * @return bool
     */
    public function updateSubscription(UserSubscription $subscription, array $data): bool;

    /**
     * Abonelik sil
     *
     * @param UserSubscription $subscription
     * @return bool
     */
    public function deleteSubscription(UserSubscription $subscription): bool;

    /**
     * Abonelik süresini uzat
     *
     * @param UserSubscription $subscription
     * @param int $days
     * @return bool
     */
    public function extendSubscription(UserSubscription $subscription, int $days): bool;

    /**
     * Aboneliği iptal et
     *
     * @param UserSubscription $subscription
     * @param string|null $reason
     * @return bool
     */
    public function cancelSubscription(UserSubscription $subscription, ?string $reason = null): bool;

    /**
     * Aboneliği aktif et (cancelled'dan active'e)
     *
     * @param UserSubscription $subscription
     * @return bool
     */
    public function activateSubscription(UserSubscription $subscription): bool;

    /**
     * Abonelik yenile (renew)
     *
     * @param UserSubscription $subscription
     * @param int $durationDays
     * @param string|null $transactionId
     * @return bool
     */
    public function renewSubscription(UserSubscription $subscription, int $durationDays, ?string $transactionId = null): bool;

    /**
     * Süresi dolmuş abonelikleri toplu güncelle (Cron için)
     *
     * @return int Güncellenen kayıt sayısı
     */
    public function expireOldSubscriptions(): int;

    /**
     * Abonelik istatistiklerini getir
     *
     * @return array
     */
    public function getSubscriptionStats(): array;

    /**
     * Gelir istatistiklerini getir
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getRevenueStats(?string $startDate = null, ?string $endDate = null): array;

    /**
     * Plana göre abonelikleri getir
     *
     * @param int $planId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getSubscriptionsByPlan(int $planId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Manuel abonelikleri listele
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getManualSubscriptions(int $perPage = 20): LengthAwarePaginator;

    /**
     * Ödeme yapılan abonelikleri listele
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaidSubscriptions(int $perPage = 20): LengthAwarePaginator;

    /**
     * İlişkilerle birlikte getir
     *
     * @param UserSubscription $subscription
     * @return UserSubscription
     */
    public function loadRelations(UserSubscription $subscription): UserSubscription;
}
