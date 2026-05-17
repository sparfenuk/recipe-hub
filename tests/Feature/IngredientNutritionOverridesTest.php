<?php

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Unit;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(UnitSeeder::class);
    Queue::fake();
});

it('backfills nutrition from USDA via fdc_overrides', function () {
    Ingredient::factory()->create([
        'source' => 'USDA FDC #171287',
        'name' => ['en' => 'Egg, whole, raw, fresh'],
        'kcal_per_100g' => 143,
        'protein_g' => 12.56,
        'fat_g' => 9.51,
        'carbs_g' => 0.72,
    ]);

    $stub = Ingredient::factory()->create([
        'source' => 'RecipeSeeder fixture (no USDA match)',
        'name' => ['en' => 'Egg'],
        'kcal_per_100g' => null,
    ]);

    $this->artisan('ingredients:apply-overrides', ['--no-recompute' => true])
        ->assertSuccessful();

    $stub->refresh();
    expect((float) $stub->kcal_per_100g)->toBe(143.0);
    expect((float) $stub->protein_g)->toBe(12.56);
});

it('backfills nutrition from direct_nutrition for items missing from USDA', function () {
    $stub = Ingredient::factory()->create([
        'source' => 'RecipeSeeder fixture (no USDA match)',
        'name' => ['en' => 'Honey'],
        'kcal_per_100g' => null,
    ]);

    $this->artisan('ingredients:apply-overrides', ['--no-recompute' => true])
        ->assertSuccessful();

    $stub->refresh();
    expect((float) $stub->kcal_per_100g)->toBe(304.0);
    expect((float) $stub->sugar_g)->toBeGreaterThan(80.0);
});

it('handles compound names via candidate-key splitting', function () {
    $stub = Ingredient::factory()->create([
        'name' => ['en' => 'Olive oil or coconut oil'],
        'kcal_per_100g' => null,
        'source' => 'RecipeSeeder fixture (no USDA match)',
    ]);

    Ingredient::factory()->create([
        'source' => 'USDA FDC #171413',
        'name' => ['en' => 'Oil, olive, salad or cooking'],
        'kcal_per_100g' => 884,
    ]);

    $this->artisan('ingredients:apply-overrides', ['--no-recompute' => true])
        ->assertSuccessful();

    $stub->refresh();
    expect((float) $stub->kcal_per_100g)->toBe(884.0);
});

it('fills piece weights and densities on existing ingredients', function () {
    $egg = Ingredient::factory()->create([
        'name' => ['en' => 'Egg'],
        'piece_weight_g' => null,
    ]);
    $oliveOil = Ingredient::factory()->create([
        'name' => ['en' => 'Olive oil'],
        'density_g_per_ml' => null,
    ]);

    $this->artisan('ingredients:apply-overrides', ['--no-recompute' => true])
        ->assertSuccessful();

    $egg->refresh();
    $oliveOil->refresh();
    expect((float) $egg->piece_weight_g)->toBe(50.0);
    expect((float) $oliveOil->density_g_per_ml)->toBe(0.91);
});

it('un-marks recipe_ingredient rows as non-optional when conversion becomes possible', function () {
    $egg = Ingredient::factory()->create([
        'name' => ['en' => 'Egg'],
        'kcal_per_100g' => 143,
        'piece_weight_g' => null,
    ]);
    $recipe = Recipe::factory()->create();
    $pieceUnit = Unit::where('code', 'piece')->firstOrFail();

    $ri = RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $egg->id,
        'unit_id' => $pieceUnit->id,
        'amount' => 1,
        'position' => 1,
        'is_optional' => true,
    ]);

    $this->artisan('ingredients:apply-overrides', ['--no-recompute' => true])
        ->assertSuccessful();

    $ri->refresh();
    expect((bool) $ri->is_optional)->toBeFalse();
});

it('is idempotent — second run does not change ingredients', function () {
    Ingredient::factory()->create([
        'source' => 'USDA FDC #171287',
        'name' => ['en' => 'Egg, whole, raw, fresh'],
        'kcal_per_100g' => 143,
        'protein_g' => 12.56,
    ]);
    $stub = Ingredient::factory()->create([
        'source' => 'RecipeSeeder fixture (no USDA match)',
        'name' => ['en' => 'Egg'],
        'kcal_per_100g' => null,
    ]);

    $this->artisan('ingredients:apply-overrides', ['--no-recompute' => true])->assertSuccessful();

    $stub->refresh();
    $kcalAfterFirst = $stub->kcal_per_100g;
    $proteinAfterFirst = $stub->protein_g;
    $updatedAtAfterFirst = $stub->updated_at;

    $this->artisan('ingredients:apply-overrides', ['--no-recompute' => true])->assertSuccessful();

    $stub->refresh();
    expect($stub->kcal_per_100g)->toEqual($kcalAfterFirst);
    expect($stub->protein_g)->toEqual($proteinAfterFirst);
    expect($stub->updated_at?->equalTo($updatedAtAfterFirst))->toBeTrue();
});

it('dry-run reports counts but does not write', function () {
    $stub = Ingredient::factory()->create([
        'source' => 'RecipeSeeder fixture (no USDA match)',
        'name' => ['en' => 'Honey'],
        'kcal_per_100g' => null,
    ]);

    $this->artisan('ingredients:apply-overrides', ['--dry-run' => true])
        ->assertSuccessful();

    $stub->refresh();
    expect($stub->kcal_per_100g)->toBeNull();
});
