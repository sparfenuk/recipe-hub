<?php

namespace App\Filament\Resources;

use App\Enums\TagType;
use App\Filament\Concerns\HasTaxonomyResource;
use App\Filament\Resources\TagResource\Pages;
use App\Models\Tag;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;

class TagResource extends Resource
{
    use HasTaxonomyResource;
    use Translatable;

    protected static ?string $model = Tag::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Taxonomies';

    protected static ?int $navigationSort = 3;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTags::route('/'),
        ];
    }

    /** @return array<int, Component> */
    protected static function extraFormFields(): array
    {
        return [
            Select::make('type')
                ->options(TagType::class)
                ->required(),
        ];
    }

    /** @return array<int, Column> */
    protected static function extraTableColumns(): array
    {
        return [
            TextColumn::make('type')->badge(),
        ];
    }

    /** @return array<int, BaseFilter> */
    protected static function extraTableFilters(): array
    {
        return [
            SelectFilter::make('type')
                ->options([
                    'diet' => 'Diet',
                    'cuisine' => 'Cuisine',
                    'misc' => 'Misc',
                ]),
        ];
    }
}
