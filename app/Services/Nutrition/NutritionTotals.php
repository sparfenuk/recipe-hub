<?php

namespace App\Services\Nutrition;

final readonly class NutritionTotals
{
    public float $kcal_per_serving;

    public float $protein_per_serving_g;

    public float $fat_per_serving_g;

    public float $carbs_per_serving_g;

    public float $fiber_per_serving_g;

    public function __construct(
        public float $kcal,
        public float $protein_g,
        public float $fat_g,
        public float $carbs_g,
        public float $fiber_g,
        public int $servings,
    ) {
        $div = max($servings, 1);
        $this->kcal_per_serving = round($kcal / $div, 2);
        $this->protein_per_serving_g = round($protein_g / $div, 2);
        $this->fat_per_serving_g = round($fat_g / $div, 2);
        $this->carbs_per_serving_g = round($carbs_g / $div, 2);
        $this->fiber_per_serving_g = round($fiber_g / $div, 2);
    }
}
