<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasTaxonomyResource;
use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;

class CategoryResource extends Resource
{
    use HasTaxonomyResource;
    use Translatable;

    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Taxonomies';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Recipe Categories';

    protected static ?string $modelLabel = 'Recipe Category';

    protected static ?string $pluralModelLabel = 'Recipe Categories';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCategories::route('/'),
        ];
    }

    /** @return array<int, Component> */
    protected static function extraFormFields(): array
    {
        return [
            Select::make('parent_id')
                ->label('Parent category')
                ->relationship('parent', 'slug')
                ->getOptionLabelFromRecordUsing(fn (Category $record): string => $record->name)
                ->searchable()
                ->preload()
                ->nullable(),
        ];
    }

    /** @return array<int, Column> */
    protected static function extraTableColumns(): array
    {
        return [
            TextColumn::make('parent.name')->label('Parent')->placeholder('--'),
        ];
    }
}
