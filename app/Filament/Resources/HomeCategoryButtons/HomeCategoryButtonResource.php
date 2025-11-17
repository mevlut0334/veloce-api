<?php

namespace App\Filament\Resources\HomeCategoryButtons;

use App\Filament\Resources\HomeCategoryButtons\Pages\CreateHomeCategoryButton;
use App\Filament\Resources\HomeCategoryButtons\Pages\EditHomeCategoryButton;
use App\Filament\Resources\HomeCategoryButtons\Pages\ListHomeCategoryButtons;
use App\Filament\Resources\HomeCategoryButtons\Schemas\HomeCategoryButtonForm;
use App\Filament\Resources\HomeCategoryButtons\Tables\HomeCategoryButtonsTable;
use App\Models\HomeCategoryButton;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class HomeCategoryButtonResource extends Resource
{
    protected static ?string $model = HomeCategoryButton::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cursor-arrow-rays';

    protected static ?string $navigationLabel = 'Kategori Butonları';

    protected static ?string $modelLabel = 'Kategori Butonu';

    protected static ?string $pluralModelLabel = 'Kategori Butonları';

    protected static ?string $recordTitleAttribute = 'position';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return HomeCategoryButtonForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomeCategoryButtonsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomeCategoryButtons::route('/'),
            'create' => CreateHomeCategoryButton::route('/create'),
            'edit' => EditHomeCategoryButton::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::count();
        return $count > 0 ? "{$count}/2" : null;
    }
}
