<?php

namespace App\Services\Interfaces;

use App\Models\UserSubscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SubscriptionServiceInterface
{
    /**
     * Tüm abonelikleri listele (Admin)
     */
    public function getAllSubscriptions(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Kullanıcının aboneliklerini getir
     */
    public function getUserSubscriptions(int $userId): Collection;

    /**
     * Kullanıcının aktif aboneliğini getir
     */
    public function getUserActiveSubscription(int $userId): ?UserSubscription;

    /**
     * Kullanıcının premium erişimi var mı?
     */
    public function userHasPremiumAccess(int $userId): bool;

    /**
     * ID ile abonelik bul
     */
    public function findSubscription(int $id): ?UserSubscription;

    /**
     * Manuel abonelik oluştur (Admin)
     */
    public function createManualSubscription(array $data): UserSubscription;

    /**
     * Abonelik güncelle (Admin)
     */
    public function updateSubscription(int $subscriptionId, array $data): UserSubscription;

    /**
     * Abonelik sil (Admin)
     */
    public function deleteSubscription(int $subscriptionId): bool;

    /**
     * Abonelik süresini uzat (Admin)
     */
    public function extendSubscription(int $subscriptionId, int $days, ?string $note = null): UserSubscription;

    /**
     * Aboneliği iptal et (Admin)
     */
    public function cancelSubscription(int $subscriptionId, string $reason): UserSubscription;

    /**
     * Aboneliği aktif et (Admin)
     */
    public function activateSubscription(int $subscriptionId): UserSubscription;

    /**
     * Aboneliği yenile (Admin)
     */
    public function renewSubscription(int $subscriptionId, int $planId, ?string $note = null): UserSubscription;

    /**
     * Aktif abonelikleri listele
     */
    public function getActiveSubscriptions(int $perPage = 20): LengthAwarePaginator;

    /**
     * Süresi dolmuş abonelikleri listele
     */
    public function getExpiredSubscriptions(int $perPage = 20): LengthAwarePaginator;

    /**
     * Yakında sona erecek abonelikleri listele
     */
    public function getExpiringSubscriptions(int $days = 7, int $perPage = 20): LengthAwarePaginator;

    /**
     * Manuel abonelikleri listele
     */
    public function getManualSubscriptions(int $perPage = 20): LengthAwarePaginator;

    /**
     * Ödeme yapılan abonelikleri listele
     */
    public function getPaidSubscriptions(int $perPage = 20): LengthAwarePaginator;

    /**
     * Plana göre abonelikleri listele
     */
    public function getSubscriptionsByPlan(int $planId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Abonelik istatistikleri (Dashboard)
     */
    public function getSubscriptionStats(): array;

    /**
     * Gelir istatistikleri
     */
    public function getRevenueStats(?string $startDate = null, ?string $endDate = null): array;

    /**
     * Süresi dolmuş abonelikleri güncelle (Cron)
     */
    public function expireOldSubscriptions(): int;
}
