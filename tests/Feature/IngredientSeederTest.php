<?php

use App\Models\Ingredient;
use Database\Seeders\AllergenSeeder;
use Database\Seeders\IngredientCategorySeeder;
use Database\Seeders\IngredientSeeder;
use Database\Seeders\UnitSeeder;

beforeEach(function () {
    $this->seed(UnitSeeder::class);
    $this->seed(IngredientCategorySeeder::class);
    $this->seed(AllergenSeeder::class);
});

it('seeds all ingredients from the curated CSV', function () {
    $this->seed(IngredientSeeder::class);

    expect(Ingredient::count())->toBe(14);
});

it('is idempotent — re-seeding does not duplicate', function () {
    $this->seed(IngredientSeeder::class);
    $this->seed(IngredientSeeder::class);

    expect(Ingredient::count())->toBe(14);
});

it('applies enrichment data (densities, allergens)', function () {
    $this->seed(IngredientSeeder::class);

    expect(Ingredient::whereNotNull('density_g_per_ml')->count())->toBeGreaterThan(0)
        ->and(Ingredient::whereHas('allergens')->count())->toBeGreaterThan(0);
});

it('stores correct nutrition for egg', function () {
    $this->seed(IngredientSeeder::class);

    $egg = Ingredient::where('source', 'USDA FDC #174230')->firstOrFail();

    expect((float) $egg->kcal_per_100g)->toBe(143.0)
        ->and((float) $egg->protein_g)->toBe(12.56)
        ->and((float) $egg->fat_g)->toBe(9.51)
        ->and((float) $egg->carbs_g)->toBe(0.72);
});

it('stores correct nutrition for broccoli', function () {
    $this->seed(IngredientSeeder::class);

    $broccoli = Ingredient::where('source', 'USDA FDC #168917')->firstOrFail();

    expect((float) $broccoli->kcal_per_100g)->toBe(34.0)
        ->and((float) $broccoli->protein_g)->toBe(2.82)
        ->and((float) $broccoli->fat_g)->toBe(0.37)
        ->and((float) $broccoli->carbs_g)->toBe(6.64);
});

it('marks inactive ingredients correctly', function () {
    $this->seed(IngredientSeeder::class);

    expect(Ingredient::where('is_active', true)->count())->toBe(12)
        ->and(Ingredient::where('is_active', false)->count())->toBe(2);
});

it('works within migrate:fresh --seed flow', function () {
    $this->artisan('migrate:fresh', ['--seed' => true])
        ->assertSuccessful();

    // RecipeSeeder may add stubs for ingredients not in the curated USDA set,
    // so we assert the USDA-sourced subset rather than total count.
    expect(Ingredient::where('source', 'like', 'USDA FDC #%')->count())->toBe(14);
});
