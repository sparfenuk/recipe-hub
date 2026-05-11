<?php

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Unit;
use App\Services\Nutrition\NutritionCalculator;
use App\Services\Nutrition\NutritionTotals;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UnitSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(UnitSeeder::class);
    $this->calculator = new NutritionCalculator;
});

function gram(): Unit
{
    return Unit::where('code', 'g')->firstOrFail();
}

function ml(): Unit
{
    return Unit::where('code', 'ml')->firstOrFail();
}

function tbsp(): Unit
{
    return Unit::where('code', 'tbsp')->firstOrFail();
}

function assertWithinPercent(float $expected, float $actual, float $percent = 1.0): void
{
    if ($expected == 0.0) {
        expect($actual)->toBe(0.0);

        return;
    }
    $diff = abs($actual - $expected) / abs($expected) * 100;
    expect($diff)->toBeLessThanOrEqual($percent,
        "Expected ~{$expected}, got {$actual} (diff {$diff}%)"
    );
}

// ----------------------------------------------------------------
// Recipe 1: Simple — mass units only, no overrides
//
// 200g chicken breast (cooked): kcal=165, P=31.02, F=3.57, C=0, fiber=0
// 300g white rice (raw):        kcal=365, P=7.13,  F=0.66, C=79.95, fiber=1.3
//
// Chicken: 200/100 * [165, 31.02, 3.57, 0, 0] = [330, 62.04, 7.14, 0, 0]
// Rice:    300/100 * [365, 7.13, 0.66, 79.95, 1.3] = [1095, 21.39, 1.98, 239.85, 3.9]
// Totals:  [1425, 83.43, 9.12, 239.85, 3.9]
// Per serving (2): [712.5, 41.715, 4.56, 119.925, 1.95]
// ----------------------------------------------------------------
it('computes totals for a simple recipe with mass units', function () {
    $chicken = Ingredient::factory()->create([
        'kcal_per_100g' => 165, 'protein_g' => 31.02, 'fat_g' => 3.57,
        'carbs_g' => 0, 'fiber_g' => 0,
    ]);
    $rice = Ingredient::factory()->create([
        'kcal_per_100g' => 365, 'protein_g' => 7.13, 'fat_g' => 0.66,
        'carbs_g' => 79.95, 'fiber_g' => 1.3,
    ]);

    $recipe = Recipe::factory()->create(['servings' => 2]);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id, 'ingredient_id' => $chicken->id,
        'position' => 1, 'amount' => 200, 'unit_id' => gram()->id,
    ]);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id, 'ingredient_id' => $rice->id,
        'position' => 2, 'amount' => 300, 'unit_id' => gram()->id,
    ]);

    $totals = $this->calculator->totalsFor($recipe);

    expect($totals)->toBeInstanceOf(NutritionTotals::class);
    assertWithinPercent(1425.0, $totals->kcal);
    assertWithinPercent(83.43, $totals->protein_g);
    assertWithinPercent(9.12, $totals->fat_g);
    assertWithinPercent(239.85, $totals->carbs_g);
    assertWithinPercent(3.9, $totals->fiber_g);
    assertWithinPercent(712.5, $totals->kcal_per_serving);
    assertWithinPercent(41.715, $totals->protein_per_serving_g);
    expect($totals->servings)->toBe(2);
});

// ----------------------------------------------------------------
// Recipe 2: With liquids — volume conversion via density
//
// 250ml whole milk, density 1.03 g/ml → 257.5g
//   kcal=61, P=3.15, F=3.25, C=4.8, fiber=0
//   257.5/100 * [61, 3.15, 3.25, 4.8, 0] = [157.075, 8.11125, 8.36875, 12.36, 0]
//
// 100g egg (whole, raw)
//   kcal=143, P=12.56, F=9.51, C=0.72, fiber=0
//   100/100 * [143, 12.56, 9.51, 0.72, 0] = [143, 12.56, 9.51, 0.72, 0]
//
// Totals: [300.075, 20.67125, 17.87875, 13.08, 0]
// Per serving (1): same as totals
// ----------------------------------------------------------------
it('computes totals for a recipe with volume units and density', function () {
    $milk = Ingredient::factory()->create([
        'kcal_per_100g' => 61, 'protein_g' => 3.15, 'fat_g' => 3.25,
        'carbs_g' => 4.8, 'fiber_g' => 0,
        'density_g_per_ml' => 1.03,
    ]);
    $egg = Ingredient::factory()->create([
        'kcal_per_100g' => 143, 'protein_g' => 12.56, 'fat_g' => 9.51,
        'carbs_g' => 0.72, 'fiber_g' => 0,
    ]);

    $recipe = Recipe::factory()->create(['servings' => 1]);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id, 'ingredient_id' => $milk->id,
        'position' => 1, 'amount' => 250, 'unit_id' => ml()->id,
    ]);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id, 'ingredient_id' => $egg->id,
        'position' => 2, 'amount' => 100, 'unit_id' => gram()->id,
    ]);

    $totals = $this->calculator->totalsFor($recipe);

    assertWithinPercent(300.075, $totals->kcal);
    assertWithinPercent(20.67125, $totals->protein_g);
    assertWithinPercent(17.87875, $totals->fat_g);
    assertWithinPercent(13.08, $totals->carbs_g);
    assertWithinPercent(0.0, $totals->fiber_g);
    expect($totals->servings)->toBe(1);
});

// ----------------------------------------------------------------
// Recipe 3: With grams_override
//
// 2 tbsp olive oil: 2 * 14.7868 = 29.5736ml * density 0.92 = 27.2077g
//   kcal=884, P=0, F=100, C=0, fiber=0
//   27.2077/100 * [884, 0, 100, 0, 0] = [240.516, 0, 27.2077, 0, 0]
//
// 150g broccoli with grams_override=145 → use 145g
//   kcal=34, P=2.82, F=0.37, C=6.64, fiber=2.6
//   145/100 * [34, 2.82, 0.37, 6.64, 2.6] = [49.3, 4.089, 0.5365, 9.628, 3.77]
//
// Totals: [289.816, 4.089, 27.7442, 9.628, 3.77]
// Per serving (2): [144.908, 2.0445, 13.8721, 4.814, 1.885]
// ----------------------------------------------------------------
it('computes totals with grams_override bypassing conversion', function () {
    $oil = Ingredient::factory()->create([
        'kcal_per_100g' => 884, 'protein_g' => 0, 'fat_g' => 100,
        'carbs_g' => 0, 'fiber_g' => 0,
        'density_g_per_ml' => 0.92,
    ]);
    $broccoli = Ingredient::factory()->create([
        'kcal_per_100g' => 34, 'protein_g' => 2.82, 'fat_g' => 0.37,
        'carbs_g' => 6.64, 'fiber_g' => 2.6,
    ]);

    $recipe = Recipe::factory()->create(['servings' => 2]);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id, 'ingredient_id' => $oil->id,
        'position' => 1, 'amount' => 2, 'unit_id' => tbsp()->id,
    ]);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id, 'ingredient_id' => $broccoli->id,
        'position' => 2, 'amount' => 150, 'unit_id' => gram()->id,
        'grams_override' => 145,
    ]);

    $totals = $this->calculator->totalsFor($recipe);

    assertWithinPercent(289.816, $totals->kcal);
    assertWithinPercent(4.089, $totals->protein_g);
    assertWithinPercent(27.744, $totals->fat_g);
    assertWithinPercent(9.628, $totals->carbs_g);
    assertWithinPercent(3.77, $totals->fiber_g);
    assertWithinPercent(144.908, $totals->kcal_per_serving);
    assertWithinPercent(2.0445, $totals->protein_per_serving_g);
    expect($totals->servings)->toBe(2);
});

it('skips optional ingredients', function () {
    $chicken = Ingredient::factory()->create([
        'kcal_per_100g' => 165, 'protein_g' => 31.02, 'fat_g' => 3.57,
        'carbs_g' => 0, 'fiber_g' => 0,
    ]);
    $garnish = Ingredient::factory()->create([
        'kcal_per_100g' => 50, 'protein_g' => 2, 'fat_g' => 0.5,
        'carbs_g' => 10, 'fiber_g' => 3,
    ]);

    $recipe = Recipe::factory()->create(['servings' => 1]);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id, 'ingredient_id' => $chicken->id,
        'position' => 1, 'amount' => 100, 'unit_id' => gram()->id,
    ]);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id, 'ingredient_id' => $garnish->id,
        'position' => 2, 'amount' => 50, 'unit_id' => gram()->id,
        'is_optional' => true,
    ]);

    $totals = $this->calculator->totalsFor($recipe);

    assertWithinPercent(165.0, $totals->kcal);
    assertWithinPercent(31.02, $totals->protein_g);
});

it('returns zeroes for a recipe with no ingredients', function () {
    $recipe = Recipe::factory()->create(['servings' => 4]);

    $totals = $this->calculator->totalsFor($recipe);

    expect($totals->kcal)->toBe(0.0)
        ->and($totals->protein_g)->toBe(0.0)
        ->and($totals->fat_g)->toBe(0.0)
        ->and($totals->carbs_g)->toBe(0.0)
        ->and($totals->fiber_g)->toBe(0.0)
        ->and($totals->kcal_per_serving)->toBe(0.0)
        ->and($totals->servings)->toBe(4);
});

it('handles null nutrition values gracefully', function () {
    $ingredient = Ingredient::factory()->create([
        'kcal_per_100g' => null, 'protein_g' => null, 'fat_g' => null,
        'carbs_g' => null, 'fiber_g' => null,
    ]);

    $recipe = Recipe::factory()->create(['servings' => 1]);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id, 'ingredient_id' => $ingredient->id,
        'position' => 1, 'amount' => 100, 'unit_id' => gram()->id,
    ]);

    $totals = $this->calculator->totalsFor($recipe);

    expect($totals->kcal)->toBe(0.0)
        ->and($totals->protein_g)->toBe(0.0);
});

it('per-serving divides correctly with multiple servings', function () {
    $ingredient = Ingredient::factory()->create([
        'kcal_per_100g' => 100, 'protein_g' => 10, 'fat_g' => 5,
        'carbs_g' => 20, 'fiber_g' => 3,
    ]);

    $recipe = Recipe::factory()->create(['servings' => 4]);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id, 'ingredient_id' => $ingredient->id,
        'position' => 1, 'amount' => 400, 'unit_id' => gram()->id,
    ]);

    $totals = $this->calculator->totalsFor($recipe);

    expect($totals->kcal)->toBe(400.0)
        ->and($totals->kcal_per_serving)->toBe(100.0)
        ->and($totals->protein_per_serving_g)->toBe(10.0)
        ->and($totals->fat_per_serving_g)->toBe(5.0)
        ->and($totals->carbs_per_serving_g)->toBe(20.0)
        ->and($totals->fiber_per_serving_g)->toBe(3.0);
});
