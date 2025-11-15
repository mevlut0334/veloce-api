<?php

namespace App\Filament\Resources\HomeSections;

use App\Filament\Resources\HomeSections\Pages\CreateHomeSection;
use App\Filament\Resources\HomeSections\Pages\EditHomeSection;
use App\Filament\Resources\HomeSections\Pages\ListHomeSections;
use App\Filament\Resources\HomeSections\Schemas\HomeSectionForm;
use App\Filament\Resources\HomeSections\Tables\HomeSectionsTable;
use App\Models\HomeSection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class HomeSectionResource extends Resource
{
    protected static ?string $model = HomeSection::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Ana Sayfa Bölümleri';

    protected static ?string $modelLabel = 'Ana Sayfa Bölümü';

    protected static ?string $pluralModelLabel = 'Ana Sayfa Bölümleri';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return HomeSectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomeSectionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomeSections::route('/'),
            'create' => CreateHomeSection::route('/create'),
            'edit' => EditHomeSection::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
