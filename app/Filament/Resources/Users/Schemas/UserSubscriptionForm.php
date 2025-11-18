<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;

class UserSubscriptionForm
{
    public static function configure(): array
    {
        return [
            Section::make('Abonelik Bilgileri')
                ->schema([
                    Select::make('plan_id')
                        ->label('Abonelik Planı')
                        ->options(SubscriptionPlan::active()->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($set, $get, $state) {
                            if ($state) {
                                $plan = SubscriptionPlan::find($state);
                                if ($plan && !$get('expires_at')) {
                                    $startsAt = $get('starts_at') ?? now();
                                    $set('expires_at', now()->parse($startsAt)->addDays($plan->duration_days));
                                }
                            }
                        })
                        ->helperText('Kullanıcıya verilecek abonelik planını seçin'),

                    Select::make('status')
                        ->label('Durum')
                        ->options([
                            UserSubscription::STATUS_ACTIVE => 'Aktif',
                            UserSubscription::STATUS_EXPIRED => 'Süresi Dolmuş',
                            UserSubscription::STATUS_CANCELLED => 'İptal Edildi',
                            UserSubscription::STATUS_PENDING => 'Beklemede',
                        ])
                        ->default(UserSubscription::STATUS_ACTIVE)
                        ->required()
                        ->helperText('Abonelik durumunu belirleyin'),

                    Select::make('subscription_type')
                        ->label('Abonelik Tipi')
                        ->options([
                            UserSubscription::TYPE_MANUAL => 'Manuel (Admin Tarafından)',
                            UserSubscription::TYPE_PAID => 'Ödeme Yapıldı',
                            UserSubscription::TYPE_TRIAL => 'Deneme',
                        ])
                        ->default(UserSubscription::TYPE_MANUAL)
                        ->required()
                        ->helperText('Aboneliğin nasıl oluşturulduğunu belirtin'),
                ])
                ->columns(3),

            Section::make('Tarih Ayarları')
                ->schema([
                    DateTimePicker::make('starts_at')
                        ->label('Başlangıç Tarihi')
                        ->default(now())
                        ->required()
                        ->seconds(false)
                        ->live()
                        ->afterStateUpdated(function ($set, $get, $state) {
                            $planId = $get('plan_id');
                            if ($planId && $state) {
                                $plan = SubscriptionPlan::find($planId);
                                if ($plan) {
                                    $set('expires_at', now()->parse($state)->addDays($plan->duration_days));
                                }
                            }
                        }),

                    DateTimePicker::make('expires_at')
                        ->label('Bitiş Tarihi')
                        ->required()
                        ->seconds(false)
                        ->minDate(function ($get) {
                            $startsAt = $get('starts_at');
                            return $startsAt ? now()->parse($startsAt) : now();
                        })
                        ->helperText('Aboneliğin sona ereceği tarih'),
                ])
                ->columns(2),

            Section::make('Ödeme Bilgileri')
                ->schema([
                    Select::make('payment_method')
                        ->label('Ödeme Yöntemi')
                        ->options([
                            'credit_card' => 'Kredi Kartı',
                            'bank_transfer' => 'Banka Havalesi',
                            'paypal' => 'PayPal',
                            'manual' => 'Manuel',
                            'free' => 'Ücretsiz',
                        ])
                        ->default('manual')
                        ->helperText('Ödeme nasıl yapıldı?'),

                    Textarea::make('admin_note')
                        ->label('Admin Notu')
                        ->rows(3)
                        ->maxLength(1000)
                        ->helperText('İptal nedeni, özel notlar vb. ekleyebilirsiniz')
                        ->columnSpanFull(),
                ])
                ->columns(1),

            Section::make('Hızlı İşlemler')
                ->schema([
                    Placeholder::make('quick_actions')
                        ->label('')
                        ->content(fn () => 'Aboneliği uzatmak veya yenilemek için aşağıdaki butonları kullanabilirsiniz.')
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record) => $record !== null)
                ->collapsible(),
        ];
    }
}
