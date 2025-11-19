<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\UserSubscription;
use App\Models\SubscriptionPlan;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManageUserSubscription extends Page
{
    use InteractsWithRecord;

    protected static string $resource = UserResource::class;

    protected string $view = 'filament.resources.users.pages.manage-user-subscription';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->loadSubscriptionData();
    }

    protected function loadSubscriptionData(): void
    {
        $this->record->load(['activeSubscription.plan', 'userSubscriptions.plan']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_subscription')
                ->label('Yeni Abonelik Oluştur')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->visible(fn () => !$this->record->isSubscriber())
                ->form([
                    Select::make('plan_id')
                        ->label('Abonelik Planı')
                        ->options(SubscriptionPlan::active()->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, $set) {
                            if ($state) {
                                $plan = SubscriptionPlan::find($state);
                                if ($plan) {
                                    $set('duration_preview', $plan->getFormattedDuration());
                                    $set('price_preview', $plan->getFormattedPrice());
                                }
                            }
                        }),

                    TextInput::make('duration_preview')
                        ->label('Süre')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('price_preview')
                        ->label('Fiyat')
                        ->disabled()
                        ->dehydrated(false),

                    Select::make('subscription_type')
                        ->label('Abonelik Tipi')
                        ->options([
                            UserSubscription::TYPE_MANUAL => 'Manuel (Admin Tarafından)',
                            UserSubscription::TYPE_PAID => 'Ödeme Yapıldı',
                            UserSubscription::TYPE_TRIAL => 'Deneme',
                        ])
                        ->default(UserSubscription::TYPE_MANUAL)
                        ->required(),

                    Select::make('payment_method')
                        ->label('Ödeme Yöntemi')
                        ->options([
                            'credit_card' => 'Kredi Kartı',
                            'bank_transfer' => 'Banka Havalesi',
                            'paypal' => 'PayPal',
                            'manual' => 'Manuel',
                            'free' => 'Ücretsiz',
                        ])
                        ->default('manual'),

                    Textarea::make('admin_note')
                        ->label('Admin Notu')
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->action(function (array $data) {
                    try {
                        DB::beginTransaction();

                        $plan = SubscriptionPlan::findOrFail($data['plan_id']);

                        $subscription = UserSubscription::create([
                            'user_id' => $this->record->id,
                            'plan_id' => $data['plan_id'],
                            'starts_at' => now(),
                            'expires_at' => now()->addDays($plan->duration_days),
                            'status' => UserSubscription::STATUS_ACTIVE,
                            'subscription_type' => $data['subscription_type'],
                            'payment_method' => $data['payment_method'] ?? null,
                            'admin_note' => $data['admin_note'] ?? null,
                            'created_by' => auth()->id(),
                        ]);

                        DB::commit();

                        $this->record->clearSubscriptionCache();
                        $this->loadSubscriptionData();

                        Notification::make()
                            ->title('Abonelik başarıyla oluşturuldu!')
                            ->body("Plan: {$plan->name} - Süre: {$plan->getFormattedDuration()}")
                            ->success()
                            ->send();

                        Log::info('Subscription created', [
                            'subscription_id' => $subscription->id,
                            'user_id' => $this->record->id,
                            'plan_id' => $plan->id,
                            'created_by' => auth()->id()
                        ]);

                    } catch (\Exception $e) {
                        DB::rollBack();

                        Log::error('Subscription creation failed', [
                            'error' => $e->getMessage(),
                            'user_id' => $this->record->id,
                            'data' => $data
                        ]);

                        Notification::make()
                            ->title('Hata!')
                            ->body('Abonelik oluşturulamadı: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalWidth('2xl'),

            Action::make('extend_subscription')
                ->label('Aboneliği Uzat')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('info')
                ->visible(fn () => $this->record->isSubscriber())
                ->form([
                    TextInput::make('days')
                        ->label('Kaç gün uzatılsın?')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(365)
                        ->default(30)
                        ->suffix('gün'),

                    Textarea::make('reason')
                        ->label('Uzatma Nedeni')
                        ->rows(3)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    try {
                        DB::beginTransaction();

                        $subscription = $this->record->activeSubscription;

                        if (!$subscription) {
                            throw new \Exception('Aktif abonelik bulunamadı');
                        }

                        // Eski tarihi kaydet
                        $oldExpiry = $subscription->expires_at->copy();

                        // Admin notu ekle
                        $adminNote = $subscription->admin_note ?? '';
                        if (!empty($data['reason'])) {
                            $note = "[" . now()->format('d.m.Y H:i') . "] Uzatıldı ({$data['days']} gün) - Admin: " . auth()->user()->name . " - Neden: " . $data['reason'];
                            $adminNote = $adminNote ? $adminNote . "\n" . $note : $note;
                            $subscription->admin_note = $adminNote;
                            $subscription->save();
                        }

                        // Model metodunu kullan
                        $success = $subscription->extend((int) $data['days']);

                        if (!$success) {
                            throw new \Exception('Abonelik uzatma işlemi başarısız oldu');
                        }

                        // Yeni tarihi al (fresh from database)
                        $subscription->refresh();
                        $newExpiry = $subscription->expires_at;

                        DB::commit();

                        $this->record->clearSubscriptionCache();
                        $this->loadSubscriptionData();

                        Notification::make()
                            ->title("Abonelik {$data['days']} gün uzatıldı!")
                            ->body("Eski bitiş: " . $oldExpiry->format('d.m.Y H:i') . "\nYeni bitiş: " . $newExpiry->format('d.m.Y H:i'))
                            ->success()
                            ->send();

                        Log::info('Subscription extended by admin', [
                            'subscription_id' => $subscription->id,
                            'user_id' => $this->record->id,
                            'days' => $data['days'],
                            'old_expiry' => $oldExpiry->toDateTimeString(),
                            'new_expiry' => $newExpiry->toDateTimeString(),
                            'extended_by' => auth()->id(),
                            'reason' => $data['reason'] ?? null,
                        ]);

                    } catch (\Exception $e) {
                        DB::rollBack();

                        Log::error('Subscription extension failed', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'user_id' => $this->record->id,
                            'data' => $data
                        ]);

                        Notification::make()
                            ->title('Hata!')
                            ->body('Abonelik uzatılamadı: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('cancel_subscription')
                ->label('Aboneliği İptal Et')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->isSubscriber())
                ->requiresConfirmation()
                ->form([
                    Textarea::make('reason')
                        ->label('İptal Nedeni')
                        ->required()
                        ->rows(3)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    try {
                        DB::beginTransaction();

                        $subscription = $this->record->activeSubscription;

                        if (!$subscription) {
                            throw new \Exception('Aktif abonelik bulunamadı');
                        }

                        $planName = $subscription->plan->name;

                        // İptal nedenini formatla
                        $reason = "[Admin: " . auth()->user()->name . "] " . $data['reason'];

                        // Model metodunu kullan
                        $success = $subscription->cancel($reason);

                        if (!$success) {
                            throw new \Exception('Abonelik iptal işlemi başarısız oldu');
                        }

                        DB::commit();

                        $this->record->clearSubscriptionCache();
                        $this->loadSubscriptionData();

                        Notification::make()
                            ->title('Abonelik iptal edildi!')
                            ->body("Plan: {$planName}\nNeden: {$data['reason']}")
                            ->warning()
                            ->send();

                        Log::info('Subscription cancelled by admin', [
                            'subscription_id' => $subscription->id,
                            'user_id' => $this->record->id,
                            'plan' => $planName,
                            'reason' => $data['reason'],
                            'cancelled_by' => auth()->id()
                        ]);

                    } catch (\Exception $e) {
                        DB::rollBack();

                        Log::error('Subscription cancellation failed', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'user_id' => $this->record->id,
                            'data' => $data
                        ]);

                        Notification::make()
                            ->title('Hata!')
                            ->body('Abonelik iptal edilemedi: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalHeading('Aboneliği iptal et?')
                ->modalDescription('Kullanıcının aboneliği hemen sonlandırılacak.'),
        ];
    }

    public function getTitle(): string
    {
        return $this->record->name . ' - Abonelik Yönetimi';
    }
}
