<?php

namespace App\Observers;

use App\Jobs\RecalculateRecipeNutrition;
use App\Models\Ingredient;
use App\Models\Recipe;
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

    /**
     * Columns that change a recipe's searchable payload (ingredient_names_*)
     * without affecting its nutrition.
     */
    private const SEARCH_COLUMNS = ['name'];

    public function updated(Ingredient $ingredient): void
    {
        $nutritionChanged = $ingredient->wasChanged(self::NUTRITION_COLUMNS);
        $searchChanged = $ingredient->wasChanged(self::SEARCH_COLUMNS);

        if (! $nutritionChanged && ! $searchChanged) {
            return;
        }

        $recipeIds = RecipeIngredient::where('ingredient_id', $ingredient->id)
            ->distinct()
            ->pluck('recipe_id');

        if ($recipeIds->isEmpty()) {
            return;
        }

        if ($nutritionChanged) {
            // The recompute job ends with searchable(), so dispatching it also
            // refreshes the search index — one path covers nutrition + rename.
            foreach ($recipeIds as $recipeId) {
                RecalculateRecipeNutrition::dispatch($recipeId);
            }

            return;
        }

        // Name-only change: no nutrition recompute needed, just refresh the index.
        // Filter on shouldBeSearchable() so drafts stay out (the per-model
        // searchable() does not check it the way Scout's save observer does).
        Recipe::whereKey($recipeIds)->get()->each(function (Recipe $recipe): void {
            if ($recipe->shouldBeSearchable()) {
                $recipe->searchable();
            }
        });
    }
}
