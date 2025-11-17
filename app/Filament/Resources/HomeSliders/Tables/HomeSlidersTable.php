<?php

namespace App\Filament\Resources\HomeSliders\Tables;

use App\Models\HomeSlider;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class HomeSlidersTable
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

                ImageColumn::make('image_path')
                    ->label('Görsel')
                    ->disk('public')
                    ->height(60)
                    ->defaultImageUrl(url('/images/placeholder.jpg')),

                TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(50),

                TextColumn::make('subtitle')
                    ->label('Alt Başlık')
                    ->searchable()
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('video.title')
                    ->label('Video')
                    ->searchable()
                    ->limit(30)
                    ->default('—')
                    ->color('info'),

                TextColumn::make('button_text')
                    ->label('Buton')
                    ->badge()
                    ->color('success')
                    ->default('—')
                    ->toggleable(),

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
                SelectFilter::make('is_active')
                    ->label('Durum')
                    ->options([
                        1 => 'Aktif',
                        0 => 'Pasif',
                    ]),

                SelectFilter::make('video_id')
                    ->label('Video')
                    ->relationship('video', 'title')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('toggle_active')
                    ->label(fn (HomeSlider $record) => $record->is_active ? 'Pasif Yap' : 'Aktif Yap')
                    ->icon('heroicon-o-eye')
                    ->color(fn (HomeSlider $record) => $record->is_active ? 'warning' : 'success')
                    ->action(function (HomeSlider $record) {
                        $record->update(['is_active' => !$record->is_active]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn (HomeSlider $record) => $record->is_active ? 'Slider\'ı pasif yap?' : 'Slider\'ı aktif yap?')
                    ->modalDescription(fn (HomeSlider $record) => $record->is_active
                        ? 'Bu slider ana sayfada görünmeyecek.'
                        : 'Bu slider ana sayfada görünecek.'),

                Action::make('move_up')
                    ->label('Yukarı')
                    ->icon('heroicon-o-arrow-up')
                    ->color('info')
                    ->action(function (HomeSlider $record) {
                        $previousSlider = HomeSlider::where('order', '<', $record->order)
                            ->orderByDesc('order')
                            ->first();

                        if ($previousSlider) {
                            $tempOrder = $record->order;
                            $record->order = $previousSlider->order;
                            $previousSlider->order = $tempOrder;
                            $record->save();
                            $previousSlider->save();
                        }
                    })
                    ->visible(fn (HomeSlider $record) => $record->order > 0),

                Action::make('move_down')
                    ->label('Aşağı')
                    ->icon('heroicon-o-arrow-down')
                    ->color('info')
                    ->action(function (HomeSlider $record) {
                        $nextSlider = HomeSlider::where('order', '>', $record->order)
                            ->orderBy('order')
                            ->first();

                        if ($nextSlider) {
                            $tempOrder = $record->order;
                            $record->order = $nextSlider->order;
                            $nextSlider->order = $tempOrder;
                            $record->save();
                            $nextSlider->save();
                        }
                    }),

                EditAction::make(),

                Action::make('delete')
                    ->label('Sil')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (HomeSlider $record) => $record->delete()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order', 'asc')
            ->reorderable('order')
            ->paginated([10, 25, 50]);
    }
}
