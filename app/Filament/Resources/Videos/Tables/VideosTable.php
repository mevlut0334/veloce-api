<?php

namespace App\Filament\Resources\Videos\Tables;

use App\Models\Video;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VideosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail_path')
                    ->label('Kapak')
                    ->disk('public')
                    ->width(80)
                    ->height(60),

                TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),

                TextColumn::make('duration_human')
                    ->label('Süre')
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('orientation')
                    ->label('Yönelim')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'horizontal' => 'Yatay',
                        'vertical' => 'Dikey',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match($state) {
                        'horizontal' => 'info',
                        'vertical' => 'success',
                        default => 'gray',
                    })
                    ->alignCenter(),

                IconColumn::make('is_premium')
                    ->label('Premium')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->alignCenter(),

                TextColumn::make('view_count')
                    ->label('Görüntülenme')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => number_format($state)),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                IconColumn::make('is_processed')
                    ->label('İşlendi')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Güncellenme')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('orientation')
                    ->label('Yönelim')
                    ->options([
                        'horizontal' => 'Yatay',
                        'vertical' => 'Dikey',
                    ]),

                SelectFilter::make('is_premium')
                    ->label('Premium')
                    ->options([
                        1 => 'Premium',
                        0 => 'Ücretsiz',
                    ]),

                SelectFilter::make('is_active')
                    ->label('Durum')
                    ->options([
                        1 => 'Aktif',
                        0 => 'Pasif',
                    ]),

                SelectFilter::make('is_processed')
                    ->label('İşlem Durumu')
                    ->options([
                        1 => 'İşlendi',
                        0 => 'İşleniyor',
                    ]),
            ])
            ->recordActions([
                Action::make('toggle_active')
                    ->label(fn (Video $record) => $record->is_active ? 'Pasif Yap' : 'Aktif Yap')
                    ->icon('heroicon-o-eye')
                    ->color(fn (Video $record) => $record->is_active ? 'warning' : 'success')
                    ->action(function (Video $record) {
                        $record->update(['is_active' => !$record->is_active]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn (Video $record) => $record->is_active ? 'Videoyu pasif yap?' : 'Videoyu aktif yap?')
                    ->modalDescription(fn (Video $record) => $record->is_active
                        ? 'Bu video kullanıcılar tarafından görüntülenemeyecek.'
                        : 'Bu video kullanıcılar tarafından görüntülenebilecek.'),

                Action::make('toggle_premium')
                    ->label(fn (Video $record) => $record->is_premium ? 'Ücretsiz Yap' : 'Premium Yap')
                    ->icon('heroicon-o-star')
                    ->color(fn (Video $record) => $record->is_premium ? 'gray' : 'warning')
                    ->action(function (Video $record) {
                        $record->update(['is_premium' => !$record->is_premium]);
                    })
                    ->requiresConfirmation(),

                Action::make('regenerate_thumbnail')
                    ->label('Thumbnail Yenile')
                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->action(function (Video $record) {
                        $record->dispatchThumbnailGeneration();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Thumbnail yeniden oluşturulsun mu?')
                    ->modalDescription('Mevcut thumbnail silinecek ve yenisi oluşturulacak.')
                    ->visible(fn (Video $record) => $record->is_processed),

                EditAction::make(),

                Action::make('delete')
                    ->label('Sil')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Video $record) => $record->delete()),
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
