<?php

use App\Console\Commands\AutoTagRecipes;
use App\Models\Allergen;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Tag;
use App\Models\Unit;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UnitSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(UnitSeeder::class);
    $this->gram = Unit::where('code', 'g')->firstOrFail();
    AutoTagRecipes::$rulesPathOverride = base_path('tests/fixtures/diet-rules.json');
});

afterEach(function () {
    AutoTagRecipes::$rulesPathOverride = null;
});

function recipeWithIngredient(Ingredient $ingredient, Unit $unit, string $title): Recipe
{
    $recipe = Recipe::factory()->published()->create(['title' => $title]);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $unit->id,
        'amount' => 100,
        'position' => 1,
    ]);

    return $recipe;
}

it('tags recipes whose ingredients pass the exclusion rules', function () {
    Tag::create(['slug' => 'gluten-free', 'name' => 'Gluten free', 'type' => 'diet']);
    Tag::create(['slug' => 'nut-free', 'name' => 'Nut free', 'type' => 'diet']);

    $rice = Ingredient::factory()->create(['name' => 'Rice']);
    $recipe = recipeWithIngredient($rice, $this->gram, 'Plain Rice');

    $this->artisan('recipes:auto-tag')->assertSuccessful();

    expect($recipe->tags()->pluck('slug')->all())->toContain('gluten-free', 'nut-free');
});

it('does not tag a recipe whose ingredient carries the excluded allergen', function () {
    Tag::create(['slug' => 'gluten-free', 'name' => 'Gluten free', 'type' => 'diet']);
    $gluten = Allergen::create(['slug' => 'gluten', 'name' => 'Gluten']);
    $wheat = Ingredient::factory()->create(['name' => 'Wheat']);
    $wheat->allergens()->attach($gluten);
    $recipe = recipeWithIngredient($wheat, $this->gram, 'Wheat Bread');

    $this->artisan('recipes:auto-tag')->assertSuccessful();

    expect($recipe->tags()->pluck('slug')->all())->not->toContain('gluten-free');
});

it('dry-run writes no tags', function () {
    Tag::create(['slug' => 'gluten-free', 'name' => 'Gluten free', 'type' => 'diet']);
    $rice = Ingredient::factory()->create(['name' => 'Rice']);
    $recipe = recipeWithIngredient($rice, $this->gram, 'Plain Rice');

    $this->artisan('recipes:auto-tag', ['--dry-run' => true])->assertSuccessful();

    expect($recipe->tags()->count())->toBe(0);
});

it('fails loudly on malformed rules JSON', function () {
    AutoTagRecipes::$rulesPathOverride = base_path('tests/fixtures/broken-rules.json');

    $this->artisan('recipes:auto-tag')->run();
})->throws(JsonException::class);
