<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasTaxonomyResource;
use App\Filament\Resources\CuisineResource\Pages;
use App\Models\Cuisine;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;

class CuisineResource extends Resource
{
    use HasTaxonomyResource;
    use Translatable;

    protected static ?string $model = Cuisine::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Taxonomies';

    protected static ?int $navigationSort = 2;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCuisines::route('/'),
        ];
    }
}
