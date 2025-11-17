<?php

namespace App\Filament\Resources\HomeCategoryButtons\Schemas;

use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HomeCategoryButtonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Buton Ayarları')
                    ->schema([
                        Select::make('position')
                            ->label('Pozisyon')
                            ->options([
                                1 => 'Sol Buton',
                                2 => 'Sağ Buton',
                            ])
                            ->required()
                            ->helperText('Hangi buton olacağını seçin')
                            ->disabled(fn ($record) => $record !== null), // Edit'te değiştirilemez

                        Select::make('category_id')
                            ->label('Kategori')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->options(function () {
                                return Category::where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->helperText('Bu buton hangi kategoriye yönlendirecek')
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Pasif yapılan buton ana sayfada görünmez'),
                    ])
                    ->columns(1),
            ]);
    }
}
