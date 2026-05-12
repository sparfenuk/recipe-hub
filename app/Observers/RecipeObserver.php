<?php

namespace App\Observers;

use App\Jobs\RecalculateRecipeNutrition;
use App\Models\Recipe;

class RecipeObserver
{
    public function saved(Recipe $recipe): void
    {
        RecalculateRecipeNutrition::dispatch($recipe->id);
    }
}
