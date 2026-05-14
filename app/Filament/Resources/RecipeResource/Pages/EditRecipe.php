<?php

namespace App\Filament\Resources\RecipeResource\Pages;

use App\Filament\Resources\RecipeResource;
use App\Jobs\RecalculateRecipeNutrition;
use App\Models\Recipe;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecipe extends EditRecord
{
    protected static string $resource = RecipeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    /** @param  array<string, mixed>  $data */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['total_time_min'] = ((int) ($data['prep_time_min'] ?? 0)) + ((int) ($data['cook_time_min'] ?? 0));

        /** @var Recipe $recipe */
        $recipe = $this->record;

        if (($data['status'] ?? '') === 'published' && $recipe->published_at === null) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Recipe $recipe */
        $recipe = $this->record;
        RecalculateRecipeNutrition::dispatchSync($recipe->id);
        $recipe->refresh();
    }
}
