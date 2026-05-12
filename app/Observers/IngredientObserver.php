<?php

namespace App\Observers;

use App\Jobs\RecalculateRecipeNutrition;
use App\Models\Ingredient;
use App\Models\RecipeIngredient;

class IngredientObserver
{
    private const NUTRITION_COLUMNS = [
        'kcal_per_100g',
        'protein_g',
        'fat_g',
        'carbs_g',
        'fiber_g',
        'density_g_per_ml',
        'piece_weight_g',
    ];

    public function updated(Ingredient $ingredient): void
    {
        if (! $ingredient->wasChanged(self::NUTRITION_COLUMNS)) {
            return;
        }

        $recipeIds = RecipeIngredient::where('ingredient_id', $ingredient->id)
            ->distinct()
            ->pluck('recipe_id');

        foreach ($recipeIds as $recipeId) {
            RecalculateRecipeNutrition::dispatch($recipeId);
        }
    }
}
