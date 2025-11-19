<?php

namespace App\Filament\Resources\SubscriptionPlans;

use App\Filament\Resources\SubscriptionPlans\Pages\ManageSubscriptionPlans;
use App\Models\SubscriptionPlan;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static UnitEnum|string|null $navigationGroup = 'Abonelik Yönetimi';

    protected static ?string $navigationLabel = 'Abonelik Planları';

    protected static ?string $modelLabel = 'Abonelik Planı';

    protected static ?string $pluralModelLabel = 'Abonelik Planları';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Plan Adı')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Örn: Aylık Plan, Yıllık Plan'),

                Textarea::make('description')
                    ->label('Açıklama')
                    ->rows(3)
                    ->maxLength(1000)
                    ->placeholder('Plan hakkında kısa açıklama'),

                TextInput::make('price')
                    ->label('Fiyat')
                    ->required()
                    ->numeric()
                    ->prefix('TRY')
                    ->minValue(0)
                    ->step(0.01)
                    ->placeholder('99.99'),

                TextInput::make('duration_days')
                    ->label('Süre (Gün)')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->suffix('gün')
                    ->placeholder('30')
                    ->helperText('Abonelik süresi (gün cinsinden)'),



                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->helperText('Plan satışa açık mı?'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Plan Adı')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('price')
                    ->label('Fiyat')
                    ->money('TRY')
                    ->sortable(),

                TextColumn::make('duration_days')
                    ->label('Süre')
                    ->formatStateUsing(fn ($state) => $state . ' gün')
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Açıklama')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Durum')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

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
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Düzenle'),
                DeleteAction::make()
                    ->label('Sil'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Seçilenleri Sil'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSubscriptionPlans::route('/'),
        ];
    }
}
