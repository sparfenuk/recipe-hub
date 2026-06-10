<?php

namespace App\Services\Nutrition;

use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Throwable;

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
            $contribution = $this->contributionFor($ri);

            if ($contribution === null) {
                continue;
            }

            $kcal += $contribution->kcal;
            $protein += $contribution->protein_g;
            $fat += $contribution->fat_g;
            $carbs += $contribution->carbs_g;
            $fiber += $contribution->fiber_g;
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

    /**
     * One ingredient row's unscaled contribution to the recipe, or null when the
     * row should be skipped: optional, missing ingredient, or an amount that
     * cannot be converted to grams (e.g. a volume unit with no density). Skipping
     * unconvertible rows is the shared, safer failure mode for both the cached
     * recipe totals and the live portion calculator (CQ.8).
     */
    public function contributionFor(RecipeIngredient $ri): ?IngredientContribution
    {
        if ($ri->is_optional) {
            return null;
        }

        $ingredient = $ri->ingredient;

        if ($ingredient === null) {
            return null;
        }

        if ($ri->grams_override !== null) {
            $grams = (float) $ri->grams_override;
        } elseif ($ri->unit === null) {
            return null;
        } else {
            try {
                $grams = UnitConverter::toGrams(
                    (float) $ri->amount,
                    $ri->unit,
                    $ingredient->density_g_per_ml !== null ? (float) $ingredient->density_g_per_ml : null,
                    $ingredient->piece_weight_g !== null ? (float) $ingredient->piece_weight_g : null,
                );
            } catch (Throwable) {
                return null;
            }
        }

        $factor = $grams / 100;

        return new IngredientContribution(
            kcal: $factor * (float) ($ingredient->kcal_per_100g ?? 0),
            protein_g: $factor * (float) ($ingredient->protein_g ?? 0),
            fat_g: $factor * (float) ($ingredient->fat_g ?? 0),
            carbs_g: $factor * (float) ($ingredient->carbs_g ?? 0),
            fiber_g: $factor * (float) ($ingredient->fiber_g ?? 0),
        );
    }
}
