<?php

namespace App\Services;

use App\Models\UserSubscription;
use App\Models\SubscriptionPlan;
use App\Repositories\Interfaces\SubscriptionRepositoryInterface;
use App\Services\Interfaces\SubscriptionServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService implements SubscriptionServiceInterface
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository
    ) {}

    /**
     * Tüm abonelikleri listele (Admin)
     */
    public function getAllSubscriptions(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->subscriptionRepository->getAllSubscriptions($filters, $perPage);
    }

    /**
     * Kullanıcının aboneliklerini getir
     */
    public function getUserSubscriptions(int $userId): Collection
    {
        return $this->subscriptionRepository->getUserSubscriptions($userId);
    }

    /**
     * Kullanıcının aktif aboneliğini getir
     */
    public function getUserActiveSubscription(int $userId): ?UserSubscription
    {
        return $this->subscriptionRepository->getUserActiveSubscription($userId);
    }

    /**
     * Kullanıcının premium erişimi var mı?
     */
    public function userHasPremiumAccess(int $userId): bool
    {
        $activeSubscription = $this->getUserActiveSubscription($userId);
        return $activeSubscription !== null && $activeSubscription->isActive();
    }

    /**
     * ID ile abonelik bul
     */
    public function findSubscription(int $id): ?UserSubscription
    {
        return $this->subscriptionRepository->findSubscription($id);
    }

    /**
     * Manuel abonelik oluştur (Admin)
     */
    public function createManualSubscription(array $data): UserSubscription
    {
        // Plan bilgisini al
        $plan = SubscriptionPlan::findOrFail($data['subscription_plan_id']);

        // Bitiş tarihini hesapla (eğer verilmediyse)
        if (empty($data['expires_at'])) {
            $startDate = $data['started_at'] ?? now();
            $data['expires_at'] = now()->parse($startDate)->addDays($plan->duration_days);
        }

        // Kullanıcının mevcut aktif aboneliği varsa iptal et (opsiyonel)
        $existingSubscription = $this->getUserActiveSubscription($data['user_id']);
        if ($existingSubscription) {
            $this->cancelSubscription(
                $existingSubscription->id,
                'Yeni abonelik oluşturulduğu için otomatik iptal edildi.'
            );
        }

        DB::beginTransaction();
        try {
            $subscription = $this->subscriptionRepository->createManualSubscription($data);

            Log::info('Manuel abonelik oluşturuldu', [
                'subscription_id' => $subscription->id,
                'user_id' => $data['user_id'],
                'plan_id' => $data['subscription_plan_id'],
                'created_by' => $data['created_by'],
            ]);

            DB::commit();
            return $subscription;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Manuel abonelik oluşturma hatası', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Abonelik güncelle (Admin)
     */
    public function updateSubscription(int $subscriptionId, array $data): UserSubscription
    {
        $subscription = $this->findSubscription($subscriptionId);

        if (!$subscription) {
            throw new \Exception('Abonelik bulunamadı.');
        }

        DB::beginTransaction();
        try {
            $this->subscriptionRepository->updateSubscription($subscription, $data);

            Log::info('Abonelik güncellendi', [
                'subscription_id' => $subscriptionId,
                'updated_fields' => array_keys($data),
            ]);

            DB::commit();
            return $subscription->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Abonelik güncelleme hatası', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Abonelik sil (Admin)
     */
    public function deleteSubscription(int $subscriptionId): bool
    {
        $subscription = $this->findSubscription($subscriptionId);

        if (!$subscription) {
            throw new \Exception('Abonelik bulunamadı.');
        }

        DB::beginTransaction();
        try {
            $result = $this->subscriptionRepository->deleteSubscription($subscription);

            Log::info('Abonelik silindi', [
                'subscription_id' => $subscriptionId,
                'user_id' => $subscription->user_id,
            ]);

            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Abonelik silme hatası', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Abonelik süresini uzat (Admin)
     */
    public function extendSubscription(int $subscriptionId, int $days, ?string $note = null): UserSubscription
    {
        $subscription = $this->findSubscription($subscriptionId);

        if (!$subscription) {
            throw new \Exception('Abonelik bulunamadı.');
        }

        DB::beginTransaction();
        try {
            $this->subscriptionRepository->extendSubscription($subscription, $days);

            // Not varsa ekle
            if ($note) {
                $currentNote = $subscription->admin_note ?? '';
                $newNote = $currentNote
                    ? $currentNote . "\n[" . now()->format('Y-m-d H:i') . "] " . $note
                    : "[" . now()->format('Y-m-d H:i') . "] " . $note;

                $subscription->update(['admin_note' => $newNote]);
            }

            Log::info('Abonelik uzatıldı', [
                'subscription_id' => $subscriptionId,
                'extended_days' => $days,
                'new_expires_at' => $subscription->expires_at,
            ]);

            DB::commit();
            return $subscription->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Abonelik uzatma hatası', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Aboneliği iptal et (Admin)
     */
    public function cancelSubscription(int $subscriptionId, string $reason): UserSubscription
    {
        $subscription = $this->findSubscription($subscriptionId);

        if (!$subscription) {
            throw new \Exception('Abonelik bulunamadı.');
        }

        DB::beginTransaction();
        try {
            $reasonWithDate = "[" . now()->format('Y-m-d H:i') . "] İptal: " . $reason;
            $this->subscriptionRepository->cancelSubscription($subscription, $reasonWithDate);

            Log::info('Abonelik iptal edildi', [
                'subscription_id' => $subscriptionId,
                'reason' => $reason,
            ]);

            DB::commit();
            return $subscription->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Abonelik iptal hatası', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Aboneliği aktif et (Admin)
     */
    public function activateSubscription(int $subscriptionId): UserSubscription
    {
        $subscription = $this->findSubscription($subscriptionId);

        if (!$subscription) {
            throw new \Exception('Abonelik bulunamadı.');
        }

        // Eğer süresi dolmuşsa aktif edilemez
        if ($subscription->expires_at <= now()) {
            throw new \Exception('Süresi dolmuş abonelik aktif edilemez. Önce süreyi uzatın.');
        }

        DB::beginTransaction();
        try {
            $this->subscriptionRepository->activateSubscription($subscription);

            Log::info('Abonelik aktif edildi', [
                'subscription_id' => $subscriptionId,
            ]);

            DB::commit();
            return $subscription->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Abonelik aktif etme hatası', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Aboneliği yenile (Admin)
     */
    public function renewSubscription(int $subscriptionId, int $planId, ?string $note = null): UserSubscription
    {
        $subscription = $this->findSubscription($subscriptionId);

        if (!$subscription) {
            throw new \Exception('Abonelik bulunamadı.');
        }

        $plan = SubscriptionPlan::findOrFail($planId);

        DB::beginTransaction();
        try {
            $this->subscriptionRepository->renewSubscription($subscription, $plan->duration_days);

            // Not varsa ekle
            if ($note) {
                $currentNote = $subscription->admin_note ?? '';
                $newNote = $currentNote
                    ? $currentNote . "\n[" . now()->format('Y-m-d H:i') . "] Yenileme: " . $note
                    : "[" . now()->format('Y-m-d H:i') . "] Yenileme: " . $note;

                $subscription->update(['admin_note' => $newNote]);
            }

            Log::info('Abonelik yenilendi', [
                'subscription_id' => $subscriptionId,
                'plan_id' => $planId,
                'new_expires_at' => $subscription->expires_at,
            ]);

            DB::commit();
            return $subscription->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Abonelik yenileme hatası', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Aktif abonelikleri listele
     */
    public function getActiveSubscriptions(int $perPage = 20): LengthAwarePaginator
    {
        return $this->subscriptionRepository->getActiveSubscriptions($perPage);
    }

    /**
     * Süresi dolmuş abonelikleri listele
     */
    public function getExpiredSubscriptions(int $perPage = 20): LengthAwarePaginator
    {
        return $this->subscriptionRepository->getExpiredSubscriptions($perPage);
    }

    /**
     * Yakında sona erecek abonelikleri listele
     */
    public function getExpiringSubscriptions(int $days = 7, int $perPage = 20): LengthAwarePaginator
    {
        return $this->subscriptionRepository->getExpiringSubscriptions($days, $perPage);
    }

    /**
     * Manuel abonelikleri listele
     */
    public function getManualSubscriptions(int $perPage = 20): LengthAwarePaginator
    {
        return $this->subscriptionRepository->getManualSubscriptions($perPage);
    }

    /**
     * Ödeme yapılan abonelikleri listele
     */
    public function getPaidSubscriptions(int $perPage = 20): LengthAwarePaginator
    {
        return $this->subscriptionRepository->getPaidSubscriptions($perPage);
    }

    /**
     * Plana göre abonelikleri listele
     */
    public function getSubscriptionsByPlan(int $planId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->subscriptionRepository->getSubscriptionsByPlan($planId, $perPage);
    }

    /**
     * Abonelik istatistikleri (Dashboard)
     */
    public function getSubscriptionStats(): array
    {
        return $this->subscriptionRepository->getSubscriptionStats();
    }

    /**
     * Gelir istatistikleri
     */
    public function getRevenueStats(?string $startDate = null, ?string $endDate = null): array
    {
        return $this->subscriptionRepository->getRevenueStats($startDate, $endDate);
    }

    /**
     * Süresi dolmuş abonelikleri güncelle (Cron)
     */
    public function expireOldSubscriptions(): int
    {
        return $this->subscriptionRepository->expireOldSubscriptions();
    }
}
