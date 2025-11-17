<?php

namespace App\Filament\Resources\HomeSliders\Schemas;

use App\Models\Video;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HomeSliderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Slider Bilgileri')
                    ->schema([
                        TextInput::make('title')
                            ->label('Başlık')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Örn: Yeni Film')
                            ->columnSpanFull(),

                        TextInput::make('subtitle')
                            ->label('Alt Başlık')
                            ->maxLength(500)
                            ->placeholder('Slider açıklaması')
                            ->columnSpanFull(),

                        Group::make([
                            TextInput::make('button_text')
                                ->label('Buton Metni')
                                ->maxLength(100)
                                ->placeholder('Örn: İzle'),

                            TextInput::make('button_link')
                                ->label('Buton Linki')
                                ->url()
                                ->maxLength(500)
                                ->placeholder('https://example.com'),
                        ])->columns(2),

                        FileUpload::make('image')
                            ->label('Slider Görseli')
                            ->image()
                            ->required()
                            ->maxSize(5120)
                            ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])
                            ->helperText('Maksimum 5MB. Format: JPEG, PNG, WebP')
                            ->columnSpanFull(),

                        Select::make('video_id')
                            ->label('İlişkili Video')
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                return Video::active()
                                    ->processed()
                                    ->orderBy('title')
                                    ->pluck('title', 'id');
                            })
                            ->helperText('Opsiyonel: Slider bir videoya yönlendirebilir')
                            ->columnSpanFull(),

                        Group::make([
                            TextInput::make('order')
                                ->label('Sıra')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->helperText('Küçük sayı önce gösterilir'),

                            Toggle::make('is_active')
                                ->label('Aktif')
                                ->default(true)
                                ->helperText('Pasif yapılan slider görünmez'),
                        ])->columns(2),
                    ])
                    ->columns(1),
            ]);
    }
}
