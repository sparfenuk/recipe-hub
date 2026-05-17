<?php

namespace App\Filament\Resources\AllergenResource\Pages;

use App\Filament\Resources\AllergenResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Resources\Pages\ManageRecords\Concerns\Translatable;

class ManageAllergens extends ManageRecords
{
    use Translatable;

    protected static string $resource = AllergenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}
