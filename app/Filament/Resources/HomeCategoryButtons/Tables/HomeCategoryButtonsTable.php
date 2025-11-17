<?php

namespace App\Filament\Resources\HomeCategoryButtons\Tables;

use App\Models\HomeCategoryButton;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HomeCategoryButtonsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('position')
                    ->label('Pozisyon')
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => match($state) {
                        1 => 'Sol Buton',
                        2 => 'Sağ Buton',
                        default => $state,
                    })
                    ->color(fn (int $state): string => match($state) {
                        1 => 'info',
                        2 => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

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
                SelectFilter::make('position')
                    ->label('Pozisyon')
                    ->options([
                        1 => 'Sol Buton',
                        2 => 'Sağ Buton',
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
                    ->label(fn (HomeCategoryButton $record) => $record->is_active ? 'Pasif Yap' : 'Aktif Yap')
                    ->icon('heroicon-o-eye')
                    ->color(fn (HomeCategoryButton $record) => $record->is_active ? 'warning' : 'success')
                    ->action(function (HomeCategoryButton $record) {
                        $record->update(['is_active' => !$record->is_active]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn (HomeCategoryButton $record) => $record->is_active ? 'Butonu pasif yap?' : 'Butonu aktif yap?')
                    ->modalDescription(fn (HomeCategoryButton $record) => $record->is_active
                        ? 'Bu buton ana sayfada görünmeyecek.'
                        : 'Bu buton ana sayfada görünecek.'),

                EditAction::make(),

                Action::make('delete')
                    ->label('Sil')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (HomeCategoryButton $record) => $record->delete()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('position', 'asc')
            ->paginated([10, 25, 50]);
    }
}
