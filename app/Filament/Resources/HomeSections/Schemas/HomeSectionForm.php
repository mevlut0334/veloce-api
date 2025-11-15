<?php

namespace App\Filament\Resources\HomeSections\Schemas;

use App\Models\HomeSection;
use App\Models\Category;
use App\Models\Video;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HomeSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Genel Bilgiler')
                    ->schema([
                        TextInput::make('title')
                            ->label('Başlık')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Örn: Popüler Filmler')
                            ->columnSpanFull(),

                        Group::make([
                            Select::make('content_type')
                                ->label('İçerik Tipi')
                                ->options([
                                    HomeSection::TYPE_VIDEO_IDS => 'Manuel Seçim',
                                    HomeSection::TYPE_CATEGORY => 'Kategori',
                                    HomeSection::TYPE_TRENDING => 'Trend Videolar',
                                    HomeSection::TYPE_RECENT => 'Son Eklenenler',
                                ])
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn ($state, callable $set) => $set('content_data', []))
                                ->helperText('İçeriğin nasıl seçileceğini belirler'),

                            TextInput::make('limit')
                                ->label('Limit')
                                ->required()
                                ->numeric()
                                ->default(20)
                                ->minValue(1)
                                ->maxValue(50)
                                ->helperText('Gösterilecek maksimum video sayısı'),
                        ])->columns(2),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Pasif yapılan section ana sayfada görünmez'),
                    ])
                    ->columns(1),

                Section::make('İçerik Ayarları')
                    ->schema([
                        // Manuel Video Seçimi (TYPE_VIDEO_IDS)
                        Select::make('content_data.video_ids')
                            ->label('Videolar')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                return Video::active()
                                    ->processed()
                                    ->orderBy('title')
                                    ->pluck('title', 'id');
                            })
                            ->required()
                            ->visible(fn ($get) => $get('content_type') === HomeSection::TYPE_VIDEO_IDS)
                            ->helperText('Sıralamayı korumak için videoları istediğiniz sırada seçin')
                            ->columnSpanFull(),

                        // Kategori Seçimi (TYPE_CATEGORY)
                        Select::make('content_data.category_id')
                            ->label('Kategori')
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                return Category::where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->visible(fn ($get) => $get('content_type') === HomeSection::TYPE_CATEGORY)
                            ->helperText('Seçilen kategorideki tüm videolar gösterilir')
                            ->columnSpanFull(),

                        // Trend Videolar Ayarları (TYPE_TRENDING)
                        TextInput::make('content_data.days')
                            ->label('Gün Sayısı')
                            ->numeric()
                            ->default(7)
                            ->minValue(1)
                            ->maxValue(365)
                            ->required()
                            ->visible(fn ($get) => $get('content_type') === HomeSection::TYPE_TRENDING)
                            ->helperText('Son kaç günün trend videolarını göster')
                            ->columnSpanFull(),

                        // Son Eklenenler için bilgi mesajı
                        Placeholder::make('info')
                            ->label('')
                            ->content('Son eklenen videolar otomatik olarak gösterilecektir. Ek ayar gerekmez.')
                            ->visible(fn ($get) => $get('content_type') === HomeSection::TYPE_RECENT),
                    ])
                    ->visible(fn ($get) => filled($get('content_type'))),
            ]);
    }
}
