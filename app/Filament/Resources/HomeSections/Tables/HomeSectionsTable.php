<?php

namespace App\Filament\Resources\HomeSections\Tables;

use App\Models\HomeSection;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class HomeSectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order')
                    ->label('Sıra')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('content_type')
                    ->label('İçerik Tipi')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        HomeSection::TYPE_VIDEO_IDS => 'Manuel Seçim',
                        HomeSection::TYPE_CATEGORY => 'Kategori',
                        HomeSection::TYPE_TRENDING => 'Trend',
                        HomeSection::TYPE_RECENT => 'Son Eklenenler',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match($state) {
                        HomeSection::TYPE_VIDEO_IDS => 'info',
                        HomeSection::TYPE_CATEGORY => 'success',
                        HomeSection::TYPE_TRENDING => 'warning',
                        HomeSection::TYPE_RECENT => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('limit')
                    ->label('Limit')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
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
                SelectFilter::make('content_type')
                    ->label('İçerik Tipi')
                    ->options([
                        HomeSection::TYPE_VIDEO_IDS => 'Manuel Seçim',
                        HomeSection::TYPE_CATEGORY => 'Kategori',
                        HomeSection::TYPE_TRENDING => 'Trend',
                        HomeSection::TYPE_RECENT => 'Son Eklenenler',
                    ]),

                SelectFilter::make('is_active')
                    ->label('Durum')
                    ->options([
                        1 => 'Aktif',
                        0 => 'Pasif',
                    ]),
            ])
            ->recordActions([
                Action::make('toggle_active')
                    ->label(fn (HomeSection $record) => $record->is_active ? 'Pasif Yap' : 'Aktif Yap')
                    ->icon('heroicon-o-eye')
                    ->color(fn (HomeSection $record) => $record->is_active ? 'warning' : 'success')
                    ->action(function (HomeSection $record) {
                        $record->update(['is_active' => !$record->is_active]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn (HomeSection $record) => $record->is_active ? 'Section\'ı pasif yap?' : 'Section\'ı aktif yap?')
                    ->modalDescription(fn (HomeSection $record) => $record->is_active
                        ? 'Bu section ana sayfada görünmeyecek.'
                        : 'Bu section ana sayfada görünecek.'),

                Action::make('move_up')
                    ->label('Yukarı')
                    ->icon('heroicon-o-arrow-up')
                    ->color('info')
                    ->action(fn (HomeSection $record) => $record->moveUp())
                    ->visible(fn (HomeSection $record) => $record->order > 1),

                Action::make('move_down')
                    ->label('Aşağı')
                    ->icon('heroicon-o-arrow-down')
                    ->color('info')
                    ->action(fn (HomeSection $record) => $record->moveDown()),

                EditAction::make(),

                Action::make('delete')
                    ->label('Sil')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (HomeSection $record) => $record->delete()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order', 'asc')
            ->reorderable('order')
            ->paginated([10, 25, 50, 100]);
    }
}
