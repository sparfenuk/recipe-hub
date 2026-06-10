<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasTaxonomyResource;
use App\Filament\Resources\IngredientCategoryResource\Pages;
use App\Models\IngredientCategory;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;

class IngredientCategoryResource extends Resource
{
    use HasTaxonomyResource;
    use Translatable;

    protected static ?string $model = IngredientCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Taxonomies';

    protected static ?int $navigationSort = 1;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageIngredientCategories::route('/'),
        ];
    }

    /** @return array<int, Component> */
    protected static function extraFormFields(): array
    {
        return [
            Select::make('parent_id')
                ->label('Parent category')
                ->relationship('parent', 'slug')
                ->getOptionLabelFromRecordUsing(fn (IngredientCategory $record): string => $record->name)
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
