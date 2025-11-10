<?php

// =============================================================================
// MODEL: App\Models\Payment.php
// =============================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'amount',
        'currency',
        'payment_method',
        'transaction_id',
        'status',
        'payment_details',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'array',
        'paid_at' => 'datetime',
        'user_id' => 'integer',
        'subscription_plan_id' => 'integer',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    // Payment method constants
    const METHOD_CREDIT_CARD = 'credit_card';
    const METHOD_PAYPAL = 'paypal';
    const METHOD_BANK_TRANSFER = 'bank_transfer';

    protected static function booted()
    {
        // Ödeme tamamlandığında cache'i temizle
        static::saved(function ($payment) {
            if ($payment->isDirty('status') && $payment->status === self::STATUS_COMPLETED) {
                static::clearStatisticsCache();
            }
        });

        static::deleted(function ($payment) {
            static::clearStatisticsCache();
        });
    }

    // =========================================================================
    // İLİŞKİLER
    // =========================================================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    // =========================================================================
    // SCOPES - Temel Filtreleme
    // =========================================================================

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeRefunded(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REFUNDED);
    }

    // =========================================================================
    // SCOPES - Tarih Filtreleme (INDEX Optimizasyonlu)
    // =========================================================================

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereBetween('paid_at', [
            now()->startOfDay(),
            now()->endOfDay()
        ]);
    }

    public function scopeYesterday(Builder $query): Builder
    {
        return $query->whereBetween('paid_at', [
            now()->subDay()->startOfDay(),
            now()->subDay()->endOfDay()
        ]);
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('paid_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeLastWeek(Builder $query): Builder
    {
        return $query->whereBetween('paid_at', [
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek()
        ]);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereBetween('paid_at', [
            now()->startOfMonth(),
            now()->endOfMonth()
        ]);
    }

    public function scopeLastMonth(Builder $query): Builder
    {
        return $query->whereBetween('paid_at', [
            now()->subMonth()->startOfMonth(),
            now()->subMonth()->endOfMonth()
        ]);
    }

    public function scopeThisYear(Builder $query): Builder
    {
        return $query->whereBetween('paid_at', [
            now()->startOfYear(),
            now()->endOfYear()
        ]);
    }

    public function scopeDateRange(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->whereBetween('paid_at', [$startDate, $endDate]);
    }

    // =========================================================================
    // SCOPES - İlişki Filtreleme
    // =========================================================================

    public function scopeWithRelations(Builder $query): Builder
    {
        return $query->with([
            'user:id,name,email',
            'subscriptionPlan:id,name,price,duration_days'
        ]);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForPlan(Builder $query, int $planId): Builder
    {
        return $query->where('subscription_plan_id', $planId);
    }

    public function scopeByPaymentMethod(Builder $query, string $method): Builder
    {
        return $query->where('payment_method', $method);
    }

    // =========================================================================
    // HELPER METODLAR
    // =========================================================================

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function getFormattedAmount(): string
    {
        $amount = $this->amount ?? 0;
    return number_format((float) $amount, 2) . ' ' . strtoupper($this->currency);
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_COMPLETED => 'Tamamlandı',
            self::STATUS_PENDING => 'Beklemede',
            self::STATUS_FAILED => 'Başarısız',
            self::STATUS_REFUNDED => 'İade Edildi',
            default => 'Bilinmeyen',
        };
    }

    public function getPaymentMethodLabel(): string
    {
        return match($this->payment_method) {
            self::METHOD_CREDIT_CARD => 'Kredi Kartı',
            self::METHOD_PAYPAL => 'PayPal',
            self::METHOD_BANK_TRANSFER => 'Banka Havalesi',
            default => 'Diğer',
        };
    }

    // =========================================================================
    // İSTATİSTİK METODLARI (CACHE'Lİ & OPTİMİZE)
    // =========================================================================

    /**
     * Bugünkü toplam gelir
     */
    public static function getTodayRevenue(): float
    {
        return Cache::remember('payments_today_revenue', 300, function () {
            return (float) static::completed()
                ->today()
                ->sum('amount');
        });
    }

    /**
     * Bu ayki toplam gelir
     */
    public static function getThisMonthRevenue(): float
    {
        return Cache::remember('payments_month_revenue_' . now()->format('Y-m'), 3600, function () {
            return (float) static::completed()
                ->thisMonth()
                ->sum('amount');
        });
    }

    /**
     * Bu yılki toplam gelir
     */
    public static function getThisYearRevenue(): float
    {
        return Cache::remember('payments_year_revenue_' . now()->year, 3600, function () {
            return (float) static::completed()
                ->thisYear()
                ->sum('amount');
        });
    }

    /**
     * Tarih aralığı geliri
     */
    public static function getRevenueByDateRange(Carbon $startDate, Carbon $endDate): float
    {
        return (float) static::completed()
            ->dateRange($startDate, $endDate)
            ->sum('amount');
    }

    /**
     * Günlük gelir grafiği için veri (son 30 gün)
     */
    public static function getDailyRevenueChart(int $days = 30): array
    {
        $cacheKey = "payments_daily_chart_{$days}_" . now()->format('Y-m-d');

        return Cache::remember($cacheKey, 3600, function () use ($days) {
            return static::completed()
                ->whereBetween('paid_at', [now()->subDays($days), now()])
                ->select(
                    DB::raw('DATE(paid_at) as date'),
                    DB::raw('SUM(amount) as total'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->toArray();
        });
    }

    /**
     * Aylık gelir grafiği için veri (son 12 ay)
     */
    public static function getMonthlyRevenueChart(): array
    {
        $cacheKey = 'payments_monthly_chart_' . now()->format('Y-m');

        return Cache::remember($cacheKey, 7200, function () {
            return static::completed()
                ->whereBetween('paid_at', [now()->subMonths(12), now()])
                ->select(
                    DB::raw('YEAR(paid_at) as year'),
                    DB::raw('MONTH(paid_at) as month'),
                    DB::raw('SUM(amount) as total'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->toArray();
        });
    }

    /**
     * Ödeme yöntemine göre istatistikler
     */
    public static function getPaymentMethodStats(): array
    {
        return Cache::remember('payments_method_stats', 3600, function () {
            return static::completed()
                ->select(
                    'payment_method',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(amount) as total')
                )
                ->groupBy('payment_method')
                ->get()
                ->toArray();
        });
    }

    /**
     * En çok satan paketler
     */
    public static function getTopSellingPlans(int $limit = 5): array
    {
        return Cache::remember('payments_top_plans', 3600, function () use ($limit) {
            return static::completed()
                ->with('subscriptionPlan:id,name,price')
                ->select(
                    'subscription_plan_id',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(amount) as total')
                )
                ->groupBy('subscription_plan_id')
                ->orderByDesc('count')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    /**
     * Dashboard özet istatistikleri (Tek sorgu)
     */
    public static function getDashboardStats(): array
    {
        return Cache::remember('payments_dashboard_stats', 300, function () {
            $today = static::completed()->today();
            $thisMonth = static::completed()->thisMonth();

            return [
                'today' => [
                    'revenue' => (float) $today->sum('amount'),
                    'count' => $today->count(),
                ],
                'month' => [
                    'revenue' => (float) $thisMonth->sum('amount'),
                    'count' => $thisMonth->count(),
                ],
                'pending_count' => static::pending()->count(),
                'failed_count' => static::failed()->today()->count(),
            ];
        });
    }

    // =========================================================================
    // CACHE YÖNETİMİ
    // =========================================================================

    public static function clearStatisticsCache(): void
    {
        $keys = [
            'payments_today_revenue',
            'payments_month_revenue_' . now()->format('Y-m'),
            'payments_year_revenue_' . now()->year,
            'payments_daily_chart_30_' . now()->format('Y-m-d'),
            'payments_monthly_chart_' . now()->format('Y-m'),
            'payments_method_stats',
            'payments_top_plans',
            'payments_dashboard_stats',
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}

// =============================================================================
// MIGRATION: database/migrations/xxxx_create_payments_table.php
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('TRY');
            $table->string('payment_method', 50);
            $table->string('transaction_id', 100)->unique()->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])
                  ->default('pending');
            $table->json('payment_details')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // Performans için kritik indexler
            $table->index(['status', 'paid_at']); // En önemli composite index
            $table->index('user_id');
            $table->index('subscription_plan_id');
            $table->index('payment_method');
            $table->index('paid_at'); // Tarih sorgular için
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

// =============================================================================
// KULLANIM ÖRNEKLERİ
// =============================================================================

/*

// ❌ YANLIŞ - Her seferinde sorgu
$todayRevenue = Payment::completed()->today()->sum('amount');
$monthRevenue = Payment::completed()->thisMonth()->sum('amount');

// ✅ DOĞRU - Cache'li istatistikler
$todayRevenue = Payment::getTodayRevenue();
$monthRevenue = Payment::getThisMonthRevenue();

// ✅ Dashboard için tek çağrı
$stats = Payment::getDashboardStats();
echo $stats['today']['revenue'];
echo $stats['month']['count'];

// ✅ Grafik verileri
$dailyChart = Payment::getDailyRevenueChart(30);
$monthlyChart = Payment::getMonthlyRevenueChart();

// ✅ Controller kullanımı
class DashboardController extends Controller
{
    public function index()
    {
        $stats = Payment::getDashboardStats();
        $dailyChart = Payment::getDailyRevenueChart(30);
        $topPlans = Payment::getTopSellingPlans(5);

        return view('dashboard', compact('stats', 'dailyChart', 'topPlans'));
    }
}

// ✅ Admin - Ödeme listesi
$payments = Payment::query()
    ->completed()
    ->thisMonth()
    ->withRelations()
    ->orderByDesc('paid_at')
    ->paginate(50);

// ✅ Kullanıcı ödemeleri
$userPayments = Payment::query()
    ->forUser($userId)
    ->completed()
    ->withRelations()
    ->latest()
    ->get();

// ✅ Tarih aralığı raporu
$revenue = Payment::getRevenueByDateRange(
    Carbon::parse('2024-01-01'),
    Carbon::parse('2024-12-31')
);

// ✅ Ödeme yöntemi istatistikleri
$methodStats = Payment::getPaymentMethodStats();

// ✅ Manuel cache temizleme (admin panelde)
Payment::clearStatisticsCache();

// ✅ Yeni ödeme oluşturma
$payment = Payment::create([
    'user_id' => $user->id,
    'subscription_plan_id' => $plan->id,
    'amount' => $plan->price,
    'currency' => 'TRY',
    'payment_method' => Payment::METHOD_CREDIT_CARD,
    'status' => Payment::STATUS_PENDING,
    'paid_at' => now(),
]);

// Ödeme tamamlandığında
$payment->update([
    'status' => Payment::STATUS_COMPLETED,
    'transaction_id' => $transactionId,
]);
// Cache otomatik temizleniyor!

*/
