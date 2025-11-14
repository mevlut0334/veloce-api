<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\{
    CreateManualSubscriptionRequest,
    UpdateSubscriptionRequest,
    ExtendSubscriptionRequest,
    CancelSubscriptionRequest,
    RenewSubscriptionRequest,
    FilterSubscriptionsRequest,
    RevenueStatsRequest
};
use App\Http\Resources\{
    SubscriptionResource,
    SubscriptionListResource,
    SubscriptionStatsResource,
    RevenueStatsResource
};
use App\Services\Interfaces\SubscriptionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionServiceInterface $subscriptionService
    ) {
        // Middleware'ler route tarafında tanımlanacak
    }

    /**
     * Tüm abonelikleri listele (Filtreleme ile)
     * GET /api/admin/subscriptions
     */
    public function index(FilterSubscriptionsRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $perPage = $request->input('per_page', 20);

        $subscriptions = $this->subscriptionService->getAllSubscriptions($filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Abonelikler listelendi',
            'data' => SubscriptionListResource::collection($subscriptions),
            'meta' => [
                'total' => $subscriptions->total(),
                'per_page' => $subscriptions->perPage(),
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
            ],
        ]);
    }

    /**
     * Abonelik detayı
     * GET /api/admin/subscriptions/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->findSubscription($id);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Abonelik bulunamadı',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Abonelik detayı',
                'data' => new SubscriptionResource($subscription),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Abonelik getirme hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manuel abonelik oluştur
     * POST /api/admin/subscriptions
     */
    public function store(CreateManualSubscriptionRequest $request): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->createManualSubscription(
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Manuel abonelik başarıyla oluşturuldu',
                'data' => new SubscriptionResource($subscription),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Abonelik oluşturma hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Abonelik güncelle
     * PUT /api/admin/subscriptions/{id}
     */
    public function update(UpdateSubscriptionRequest $request, int $id): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->updateSubscription(
                $id,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Abonelik başarıyla güncellendi',
                'data' => new SubscriptionResource($subscription),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Abonelik güncelleme hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Abonelik sil
     * DELETE /api/admin/subscriptions/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->subscriptionService->deleteSubscription($id);

            return response()->json([
                'success' => true,
                'message' => 'Abonelik başarıyla silindi',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Abonelik silme hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Abonelik süresini uzat
     * POST /api/admin/subscriptions/{id}/extend
     */
    public function extend(ExtendSubscriptionRequest $request, int $id): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->extendSubscription(
                $id,
                $request->input('days'),
                $request->input('note')
            );

            return response()->json([
                'success' => true,
                'message' => "{$request->input('days')} gün uzatıldı",
                'data' => new SubscriptionResource($subscription),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Uzatma hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aboneliği iptal et
     * POST /api/admin/subscriptions/{id}/cancel
     */
    public function cancel(CancelSubscriptionRequest $request, int $id): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->cancelSubscription(
                $id,
                $request->input('reason')
            );

            return response()->json([
                'success' => true,
                'message' => 'Abonelik başarıyla iptal edildi',
                'data' => new SubscriptionResource($subscription),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'İptal hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aboneliği aktif et
     * POST /api/admin/subscriptions/{id}/activate
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->activateSubscription($id);

            return response()->json([
                'success' => true,
                'message' => 'Abonelik başarıyla aktif edildi',
                'data' => new SubscriptionResource($subscription),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Aktivasyon hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aboneliği yenile
     * POST /api/admin/subscriptions/{id}/renew
     */
    public function renew(RenewSubscriptionRequest $request, int $id): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->renewSubscription(
                $id,
                $request->input('plan_id'),
                $request->input('note')
            );

            return response()->json([
                'success' => true,
                'message' => 'Abonelik başarıyla yenilendi',
                'data' => new SubscriptionResource($subscription),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Yenileme hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aktif abonelikler
     * GET /api/admin/subscriptions/active
     */
    public function active(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 20);
        $subscriptions = $this->subscriptionService->getActiveSubscriptions($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Aktif abonelikler',
            'data' => SubscriptionListResource::collection($subscriptions),
            'meta' => [
                'total' => $subscriptions->total(),
                'current_page' => $subscriptions->currentPage(),
            ],
        ]);
    }

    /**
     * Süresi dolmuş abonelikler
     * GET /api/admin/subscriptions/expired
     */
    public function expired(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 20);
        $subscriptions = $this->subscriptionService->getExpiredSubscriptions($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Süresi dolmuş abonelikler',
            'data' => SubscriptionListResource::collection($subscriptions),
            'meta' => [
                'total' => $subscriptions->total(),
            ],
        ]);
    }

    /**
     * Yakında sona erecek abonelikler
     * GET /api/admin/subscriptions/expiring
     */
    public function expiring(Request $request): JsonResponse
    {
        $days = $request->input('days', 7);
        $perPage = $request->input('per_page', 20);

        $subscriptions = $this->subscriptionService->getExpiringSubscriptions($days, $perPage);

        return response()->json([
            'success' => true,
            'message' => "Önümüzdeki {$days} gün içinde sona erecek abonelikler",
            'data' => SubscriptionListResource::collection($subscriptions),
            'meta' => [
                'total' => $subscriptions->total(),
            ],
        ]);
    }

    /**
     * Manuel abonelikler
     * GET /api/admin/subscriptions/manual
     */
    public function manual(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 20);
        $subscriptions = $this->subscriptionService->getManualSubscriptions($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Manuel abonelikler',
            'data' => SubscriptionListResource::collection($subscriptions),
            'meta' => [
                'total' => $subscriptions->total(),
            ],
        ]);
    }

    /**
     * Ödeme yapılan abonelikler
     * GET /api/admin/subscriptions/paid
     */
    public function paid(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 20);
        $subscriptions = $this->subscriptionService->getPaidSubscriptions($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Ödeme yapılan abonelikler',
            'data' => SubscriptionListResource::collection($subscriptions),
            'meta' => [
                'total' => $subscriptions->total(),
            ],
        ]);
    }

    /**
     * Abonelik istatistikleri
     * GET /api/admin/subscriptions/stats
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->subscriptionService->getSubscriptionStats();

            return response()->json([
                'success' => true,
                'message' => 'Abonelik istatistikleri',
                'data' => new SubscriptionStatsResource($stats),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'İstatistik hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Gelir istatistikleri
     * GET /api/admin/subscriptions/revenue
     */
    public function revenue(RevenueStatsRequest $request): JsonResponse
    {
        try {
            $stats = $this->subscriptionService->getRevenueStats(
                $request->input('start_date'),
                $request->input('end_date')
            );

            return response()->json([
                'success' => true,
                'message' => 'Gelir istatistikleri',
                'data' => new RevenueStatsResource($stats),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gelir istatistikleri hatası: ' . $e->getMessage(),
            ], 500);
        }
    }
}
