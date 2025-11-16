<?php

namespace App\Filament\Resources\Videos\Schemas;

use App\Models\Category;
use App\Models\Tag;
use App\Models\Video;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VideoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Video Bilgileri')
                    ->schema([
                        TextInput::make('title')
                            ->label('Başlık')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Örn: Aksiyon Sahneleri')
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Açıklama')
                            ->maxLength(5000)
                            ->rows(4)
                            ->placeholder('Video hakkında detaylı açıklama...')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Section::make('Dosyalar')
                    ->schema([
                        FileUpload::make('video')
                            ->label('Video Dosyası')
                            ->disk('public')
                            ->directory('videos/temp')
                            ->acceptedFileTypes(['video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo'])
                            ->maxSize(2097152) // 2GB
                            ->required()
                            ->helperText('Desteklenen formatlar: MP4, MPEG, MOV, AVI (Max: 2GB)')
                            ->columnSpanFull(),

                        FileUpload::make('thumbnail')
                            ->label('Kapak Görseli (Opsiyonel)')
                            ->disk('public')
                            ->directory('thumbnails/temp')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])
                            ->maxSize(5120) // 5MB
                            ->helperText('Yüklemezseniz otomatik oluşturulacak. Desteklenen formatlar: JPEG, PNG, WEBP (Max: 5MB)')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Section::make('Video Özellikleri')
                    ->schema([
                        Group::make([
                            Select::make('orientation')
                                ->label('Yönelim')
                                ->options([
                                    Video::ORIENTATION_HORIZONTAL_STRING => 'Yatay (Horizontal)',
                                    Video::ORIENTATION_VERTICAL_STRING => 'Dikey (Vertical)',
                                ])
                                ->default(Video::ORIENTATION_HORIZONTAL_STRING)
                                ->required()
                                ->native(false)
                                ->helperText('Video ekran yönelimi'),

                            Toggle::make('is_premium')
                                ->label('Premium İçerik')
                                ->default(false)
                                ->helperText('Sadece premium üyeler izleyebilir'),
                        ])->columns(2),
                    ]),

                Section::make('Kategoriler ve Etiketler')
                    ->schema([
                        Select::make('category_ids')
                            ->label('Kategoriler')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                return Category::where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Kategori Adı')
                                    ->required()
                                    ->maxLength(100),
                                Textarea::make('description')
                                    ->label('Açıklama')
                                    ->maxLength(500),
                                Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),
                            ])
                            ->createOptionUsing(function (array $data) {
                                $category = Category::create([
                                    'name' => $data['name'],
                                    'slug' => \Illuminate\Support\Str::slug($data['name']),
                                    'description' => $data['description'] ?? null,
                                    'is_active' => $data['is_active'] ?? true,
                                ]);
                                return $category->id;
                            })
                            ->helperText('Videoyu kategorilere atayın veya yeni kategori oluşturun')
                            ->columnSpanFull(),

                        Select::make('tag_ids')
                            ->label('Etiketler')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                return Tag::where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Etiket Adı')
                                    ->required()
                                    ->maxLength(50),
                                Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),
                            ])
                            ->createOptionUsing(function (array $data) {
                                $tag = Tag::create([
                                    'name' => $data['name'],
                                    'slug' => \Illuminate\Support\Str::slug($data['name']),
                                    'is_active' => $data['is_active'] ?? true,
                                ]);
                                return $tag->id;
                            })
                            ->helperText('Videoyu etiketleyin veya yeni etiket oluşturun')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Section::make('Durum')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(false)
                            ->helperText('Video işlendikten sonra aktif olacak')
                            ->disabled(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
