<?php

namespace App\Filament\Resources\CuisineResource\Pages;

use App\Filament\Resources\CuisineResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Resources\Pages\ManageRecords\Concerns\Translatable;

class ManageCuisines extends ManageRecords
{
    use Translatable;

    protected static string $resource = CuisineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}
