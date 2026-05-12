<?php

namespace App\Observers;

use App\Jobs\RecalculateRecipeNutrition;
use App\Models\RecipeIngredient;

class RecipeIngredientObserver
{
    public function saved(RecipeIngredient $recipeIngredient): void
    {
        RecalculateRecipeNutrition::dispatch($recipeIngredient->recipe_id);
    }

    public function deleted(RecipeIngredient $recipeIngredient): void
    {
        RecalculateRecipeNutrition::dispatch($recipeIngredient->recipe_id);
    }
}
