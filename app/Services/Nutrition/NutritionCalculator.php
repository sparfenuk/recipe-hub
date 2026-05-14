<?php

namespace App\Services\Nutrition;

use App\Models\Recipe;

class NutritionCalculator
{
    public function totalsFor(Recipe $recipe): NutritionTotals
    {
        $recipe->loadMissing('recipeIngredients.ingredient', 'recipeIngredients.unit');

        $kcal = 0.0;
        $protein = 0.0;
        $fat = 0.0;
        $carbs = 0.0;
        $fiber = 0.0;

        foreach ($recipe->recipeIngredients as $ri) {
            if ($ri->is_optional) {
                continue;
            }

            $ingredient = $ri->ingredient;

            $grams = $ri->grams_override !== null
                ? (float) $ri->grams_override
                : UnitConverter::toGrams(
                    (float) $ri->amount,
                    $ri->unit,
                    $ingredient->density_g_per_ml !== null ? (float) $ingredient->density_g_per_ml : null,
                    $ingredient->piece_weight_g !== null ? (float) $ingredient->piece_weight_g : null,
                );

            $factor = $grams / 100;

            $kcal += $factor * (float) ($ingredient->kcal_per_100g ?? 0);
            $protein += $factor * (float) ($ingredient->protein_g ?? 0);
            $fat += $factor * (float) ($ingredient->fat_g ?? 0);
            $carbs += $factor * (float) ($ingredient->carbs_g ?? 0);
            $fiber += $factor * (float) ($ingredient->fiber_g ?? 0);
        }

        return new NutritionTotals(
            kcal: round($kcal, 2),
            protein_g: round($protein, 2),
            fat_g: round($fat, 2),
            carbs_g: round($carbs, 2),
            fiber_g: round($fiber, 2),
            servings: $recipe->servings,
        );
    }
}
