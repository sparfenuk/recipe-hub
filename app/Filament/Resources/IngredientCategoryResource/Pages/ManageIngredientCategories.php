<?php

namespace App\Filament\Resources\IngredientCategoryResource\Pages;

use App\Filament\Resources\IngredientCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Resources\Pages\ManageRecords\Concerns\Translatable;

class ManageIngredientCategories extends ManageRecords
{
    use Translatable;

    protected static string $resource = IngredientCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}
