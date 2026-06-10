<?php

namespace App\Services\Nutrition;

/**
 * One recipe-ingredient's unscaled macro contribution to the whole recipe
 * (grams / 100 × the ingredient's per-100g values). Shared by
 * NutritionCalculator::totalsFor() and the portion calculator's per-ingredient
 * breakdown so the grams-conversion + macro math lives in one place (CQ.8).
 */
final readonly class IngredientContribution
{
    public function __construct(
        public float $kcal,
        public float $protein_g,
        public float $fat_g,
        public float $carbs_g,
        public float $fiber_g,
    ) {}
}
