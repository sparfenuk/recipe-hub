<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasTaxonomyResource;
use App\Filament\Resources\AllergenResource\Pages;
use App\Models\Allergen;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;

class AllergenResource extends Resource
{
    use HasTaxonomyResource;
    use Translatable;

    protected static ?string $model = Allergen::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Taxonomies';

    protected static ?int $navigationSort = 4;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAllergens::route('/'),
        ];
    }
}
