<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-avatar.png')),

                TextColumn::make('name')
                    ->label('Ad Soyad')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->label('E-posta')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),

                TextColumn::make('activeSubscription.plan.name')
                    ->label('Abonelik Durumu')
                    ->badge()
                    ->default('Abone Değil')
                    ->formatStateUsing(fn ($state, User $record) => $record->getSubscriptionBadgeText())
                    ->color(fn (User $record) => $record->getSubscriptionBadgeColor()),

                TextColumn::make('activeSubscription.expires_at')
                    ->label('Bitiş Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('remaining_days')
                    ->label('Kalan Gün')
                    ->state(fn (User $record) => $record->remainingSubscriptionDays())
                    ->badge()
                    ->color(fn (int $state): string => match(true) {
                        $state === 0 => 'danger',
                        $state <= 7 => 'warning',
                        $state <= 30 => 'info',
                        default => 'success',
                    })
                    ->formatStateUsing(fn (int $state) => $state > 0 ? "{$state} gün" : 'Yok'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->alignCenter(),

                TextColumn::make('last_activity_at')
                    ->label('Son Aktivite')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Kayıt Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('subscription_status')
                    ->label('Abonelik Durumu')
                    ->options([
                        'subscriber' => 'Aktif Aboneler',
                        'non_subscriber' => 'Abone Olmayanlar',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'subscriber') {
                            return $query->subscribers();
                        }
                        if ($data['value'] === 'non_subscriber') {
                            return $query->nonSubscribers();
                        }
                        return $query;
                    }),

                SelectFilter::make('is_active')
                    ->label('Durum')
                    ->options([
                        1 => 'Aktif',
                        0 => 'Pasif',
                    ]),

                SelectFilter::make('is_admin')
                    ->label('Admin')
                    ->options([
                        1 => 'Admin',
                        0 => 'Normal Kullanıcı',
                    ]),

                Filter::make('subscribed_this_month')
                    ->label('Bu Ay Abone Olanlar')
                    ->query(fn (Builder $query) => $query->subscribedThisMonth()),

                Filter::make('subscribed_this_week')
                    ->label('Bu Hafta Abone Olanlar')
                    ->query(fn (Builder $query) => $query->subscribedThisWeek()),

                Filter::make('subscription_expiring_soon')
                    ->label('Aboneliği Yakında Bitenler (30 gün)')
                    ->query(fn (Builder $query) => $query->subscriptionExpiringSoon(30)),

                Filter::make('subscription_expiring_7days')
                    ->label('Aboneliği 7 Gün İçinde Bitenler')
                    ->query(fn (Builder $query) => $query->subscriptionExpiringSoon(7)),

                Filter::make('recent_activity')
                    ->label('Son 30 Günde Aktif')
                    ->query(fn (Builder $query) => $query->recentActivity(30)),
            ])
            ->recordActions([
                Action::make('manage_subscription')
                    ->label('Abonelik Yönet')
                    ->icon('heroicon-o-credit-card')
                    ->color('primary')
                    ->url(fn (User $record) => UserResource::getUrl('subscription', ['record' => $record])),

                Action::make('toggle_active')
                    ->label(fn (User $record) => $record->is_active ? 'Pasif Yap' : 'Aktif Yap')
                    ->icon('heroicon-o-eye')
                    ->color(fn (User $record) => $record->is_active ? 'warning' : 'success')
                    ->action(function (User $record) {
                        $record->update(['is_active' => !$record->is_active]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record) => $record->is_active ? 'Kullanıcıyı pasif yap?' : 'Kullanıcıyı aktif yap?')
                    ->modalDescription(fn (User $record) => $record->is_active
                        ? 'Kullanıcı platforma giriş yapamayacak.'
                        : 'Kullanıcı platforma giriş yapabilecek.'),

                EditAction::make(),

                Action::make('delete')
                    ->label('Sil')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $record->delete())
                    ->visible(fn (User $record) => !$record->is_admin),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100]);
    }
}
