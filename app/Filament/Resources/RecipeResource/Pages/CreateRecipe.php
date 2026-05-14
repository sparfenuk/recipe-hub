<?php

namespace App\Filament\Resources\RecipeResource\Pages;

use App\Filament\Resources\RecipeResource;
use App\Jobs\RecalculateRecipeNutrition;
use App\Models\Recipe;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateRecipe extends CreateRecord
{
    use Translatable;

    protected static string $resource = RecipeResource::class;

    /** @param  array<string, mixed>  $data */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['author_id'] = auth()->id();
        $data['total_time_min'] = ((int) ($data['prep_time_min'] ?? 0)) + ((int) ($data['cook_time_min'] ?? 0));

        if (($data['status'] ?? 'draft') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Recipe $recipe */
        $recipe = $this->record;
        RecalculateRecipeNutrition::dispatchSync($recipe->id);
        $recipe->refresh();
    }
}
