<?php

use App\Jobs\RecalculateRecipeNutrition;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Unit;
use App\Services\Nutrition\NutritionCalculator;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(UnitSeeder::class);
    $this->gram = Unit::where('code', 'g')->firstOrFail();
});

it('dispatches recompute job when recipe is created', function () {
    Queue::fake();

    $recipe = Recipe::factory()->create();

    Queue::assertPushed(
        RecalculateRecipeNutrition::class,
        fn (RecalculateRecipeNutrition $job) => $job->recipeId === $recipe->id
    );
});

it('dispatches recompute job when recipe is updated', function () {
    $recipe = Recipe::factory()->create(['servings' => 4]);

    Queue::fake();
    $recipe->update(['servings' => 8]);

    Queue::assertPushed(
        RecalculateRecipeNutrition::class,
        fn (RecalculateRecipeNutrition $job) => $job->recipeId === $recipe->id
    );
});

it('dispatches recompute job when recipe ingredient is added', function () {
    $recipe = Recipe::factory()->create();
    $ingredient = Ingredient::factory()->create();

    Queue::fake();
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $this->gram->id,
        'amount' => 200,
        'position' => 1,
    ]);

    Queue::assertPushed(
        RecalculateRecipeNutrition::class,
        fn (RecalculateRecipeNutrition $job) => $job->recipeId === $recipe->id
    );
});

it('dispatches recompute job when recipe ingredient is deleted', function () {
    $recipe = Recipe::factory()->create();
    $ingredient = Ingredient::factory()->create();
    $ri = RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $this->gram->id,
        'amount' => 200,
        'position' => 1,
    ]);

    Queue::fake();
    $ri->delete();

    Queue::assertPushed(
        RecalculateRecipeNutrition::class,
        fn (RecalculateRecipeNutrition $job) => $job->recipeId === $recipe->id
    );
});

it('dispatches recompute for all recipes when ingredient nutrition changes', function () {
    $ingredient = Ingredient::factory()->create(['kcal_per_100g' => 100]);
    $recipe1 = Recipe::factory()->create();
    $recipe2 = Recipe::factory()->create();

    RecipeIngredient::create([
        'recipe_id' => $recipe1->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $this->gram->id,
        'amount' => 200,
        'position' => 1,
    ]);
    RecipeIngredient::create([
        'recipe_id' => $recipe2->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $this->gram->id,
        'amount' => 100,
        'position' => 1,
    ]);

    Queue::fake();
    $ingredient->update(['kcal_per_100g' => 150]);

    Queue::assertPushed(
        RecalculateRecipeNutrition::class,
        fn (RecalculateRecipeNutrition $job) => $job->recipeId === $recipe1->id
    );
    Queue::assertPushed(
        RecalculateRecipeNutrition::class,
        fn (RecalculateRecipeNutrition $job) => $job->recipeId === $recipe2->id
    );
});

it('does not dispatch when non-nutrition ingredient columns change', function () {
    $ingredient = Ingredient::factory()->create();

    Queue::fake();
    $ingredient->update(['name' => 'Updated Name', 'slug' => 'updated-name']);

    Queue::assertNotPushed(RecalculateRecipeNutrition::class);
});

it('stores computed nutrition and cached_at on recipe', function () {
    $ingredient = Ingredient::factory()->create([
        'kcal_per_100g' => 200,
        'protein_g' => 20,
        'fat_g' => 10,
        'carbs_g' => 25,
        'fiber_g' => 3,
    ]);

    $recipe = Recipe::factory()->create(['servings' => 2]);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $this->gram->id,
        'amount' => 300,
        'position' => 1,
    ]);

    (new RecalculateRecipeNutrition($recipe->id))->handle(new NutritionCalculator);
    $recipe->refresh();

    expect((float) $recipe->total_kcal)->toBe(600.0)
        ->and((float) $recipe->total_protein_g)->toBe(60.0)
        ->and((float) $recipe->total_fat_g)->toBe(30.0)
        ->and((float) $recipe->total_carbs_g)->toBe(75.0)
        ->and((float) $recipe->total_fiber_g)->toBe(9.0)
        ->and((float) $recipe->kcal_per_serving)->toBe(300.0)
        ->and((float) $recipe->protein_per_serving_g)->toBe(30.0)
        ->and((float) $recipe->fat_per_serving_g)->toBe(15.0)
        ->and((float) $recipe->carbs_per_serving_g)->toBe(37.5)
        ->and((float) $recipe->fiber_per_serving_g)->toBe(4.5)
        ->and($recipe->nutrition_cached_at)->not->toBeNull();
});

it('gracefully handles a deleted recipe', function () {
    $job = new RecalculateRecipeNutrition(999999);

    $job->handle(new NutritionCalculator);

    expect(true)->toBeTrue();
});

it('recomputes all recipes when ingredient nutrition is edited end-to-end', function () {
    $ingredient = Ingredient::factory()->create([
        'kcal_per_100g' => 100,
        'protein_g' => 10,
        'fat_g' => 5,
        'carbs_g' => 20,
        'fiber_g' => 2,
    ]);

    $recipe1 = Recipe::factory()->create(['servings' => 1]);
    $recipe2 = Recipe::factory()->create(['servings' => 2]);

    RecipeIngredient::create([
        'recipe_id' => $recipe1->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $this->gram->id,
        'amount' => 200,
        'position' => 1,
    ]);
    RecipeIngredient::create([
        'recipe_id' => $recipe2->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $this->gram->id,
        'amount' => 400,
        'position' => 1,
    ]);

    $recipe1->refresh();
    $recipe2->refresh();
    expect((float) $recipe1->total_kcal)->toBe(200.0)
        ->and((float) $recipe2->total_kcal)->toBe(400.0);

    $ingredient->update(['kcal_per_100g' => 200]);

    $recipe1->refresh();
    $recipe2->refresh();
    expect((float) $recipe1->total_kcal)->toBe(400.0)
        ->and((float) $recipe1->kcal_per_serving)->toBe(400.0)
        ->and((float) $recipe2->total_kcal)->toBe(800.0)
        ->and((float) $recipe2->kcal_per_serving)->toBe(400.0)
        ->and($recipe1->nutrition_cached_at)->not->toBeNull()
        ->and($recipe2->nutrition_cached_at)->not->toBeNull();
});
