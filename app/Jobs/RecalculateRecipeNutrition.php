<?php

namespace App\Jobs;

use App\Models\Recipe;
use App\Services\Nutrition\NutritionCalculator;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecalculateRecipeNutrition implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public int $recipeId) {}

    public function uniqueId(): string
    {
        return (string) $this->recipeId;
    }

    public function handle(NutritionCalculator $calculator): void
    {
        $recipe = Recipe::find($this->recipeId);

        if (! $recipe) {
            return;
        }

        $totals = $calculator->totalsFor($recipe);

        $recipe->forceFill([
            'total_kcal' => $totals->kcal,
            'total_protein_g' => $totals->protein_g,
            'total_fat_g' => $totals->fat_g,
            'total_carbs_g' => $totals->carbs_g,
            'total_fiber_g' => $totals->fiber_g,
            'kcal_per_serving' => $totals->kcal_per_serving,
            'protein_per_serving_g' => $totals->protein_per_serving_g,
            'fat_per_serving_g' => $totals->fat_per_serving_g,
            'carbs_per_serving_g' => $totals->carbs_per_serving_g,
            'fiber_per_serving_g' => $totals->fiber_per_serving_g,
            'nutrition_cached_at' => now(),
        ])->saveQuietly();
    }
}
