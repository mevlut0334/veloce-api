<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Schemas\UserSubscriptionForm;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\SubscriptionPlan;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ManageUserSubscription extends Page
{
    protected static string $resource = UserResource::class;

    protected string $view = 'filament.resources.users.pages.manage-user-subscription';

    public User $record;

    public function mount(int|string $record): void
    {
        $this->record = User::with(['activeSubscription.plan', 'userSubscriptions.plan'])->findOrFail($record);
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
                    $plan = SubscriptionPlan::findOrFail($data['plan_id']);

                    UserSubscription::create([
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

                    $this->record->clearSubscriptionCache();

                    Notification::make()
                        ->title('Abonelik oluşturuldu!')
                        ->success()
                        ->send();

                    $this->redirect($this->getUrl(['record' => $this->record]));
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
                    $subscription = $this->record->activeSubscription;

                    if ($subscription) {
                        $subscription->extend($data['days']);

                        if (!empty($data['reason'])) {
                            $subscription->update([
                                'admin_note' => $subscription->admin_note
                                    ? $subscription->admin_note . "\n[" . now()->format('d.m.Y H:i') . "] Uzatıldı: " . $data['reason']
                                    : "[" . now()->format('d.m.Y H:i') . "] Uzatıldı: " . $data['reason']
                            ]);
                        }

                        Notification::make()
                            ->title("Abonelik {$data['days']} gün uzatıldı!")
                            ->success()
                            ->send();

                        $this->redirect($this->getUrl(['record' => $this->record]));
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
                    $subscription = $this->record->activeSubscription;

                    if ($subscription) {
                        $subscription->cancel($data['reason']);

                        Notification::make()
                            ->title('Abonelik iptal edildi!')
                            ->warning()
                            ->send();

                        $this->redirect($this->getUrl(['record' => $this->record]));
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
