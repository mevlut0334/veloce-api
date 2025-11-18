<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kullanıcı Bilgileri')
                    ->schema([
                        TextInput::make('name')
                            ->label('Ad Soyad')
                            ->required()
                            ->maxLength(100)
                            ->columnSpanFull(),

                        TextInput::make('email')
                            ->label('E-posta')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(150)
                            ->columnSpan(1),

                        FileUpload::make('avatar')
                            ->label('Avatar')
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->maxSize(2048)
                            ->directory('avatars')
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make('Yetkiler ve Durum')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Pasif kullanıcılar platforma giriş yapamaz')
                            ->inline(false),

                        Toggle::make('is_admin')
                            ->label('Admin')
                            ->default(false)
                            ->helperText('Admin kullanıcılar yönetim paneline erişebilir')
                            ->inline(false),
                    ])
                    ->columns(2),
            ]);
    }
}
