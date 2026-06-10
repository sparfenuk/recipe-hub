<?php

use App\Livewire\RecipeDetail;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UnitSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(UnitSeeder::class);
});

/** @return array<string, mixed> */
function jsonLdFor(string $slug): array
{
    return Livewire::test(RecipeDetail::class, ['slug' => $slug])->instance()->jsonLd();
}

test('jsonLd exposes core recipe fields', function () {
    Recipe::factory()->published()->create([
        'author_id' => User::factory()->create()->id,
        'slug' => 'ld-core',
        'title' => 'LD Core',
        'summary' => 'Tasty.',
        'servings' => 4,
        'prep_time_min' => 15,
        'cook_time_min' => 30,
        'total_time_min' => 45,
    ]);

    $ld = jsonLdFor('ld-core');

    expect($ld['@context'])->toBe('https://schema.org')
        ->and($ld['@type'])->toBe('Recipe')
        ->and($ld['name'])->toBe('LD Core')
        ->and($ld['description'])->toBe('Tasty.')
        ->and($ld['prepTime'])->toBe('PT15M')
        ->and($ld['cookTime'])->toBe('PT30M')
        ->and($ld['totalTime'])->toBe('PT45M')
        ->and($ld['recipeYield'])->toBe('4 servings');
});

test('jsonLd lists ingredients and steps', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => User::factory()->create()->id, 'slug' => 'ld-rel',
    ]);
    $ingredient = Ingredient::factory()->create(['name' => 'Chicken Breast']);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id, 'ingredient_id' => $ingredient->id,
        'unit_id' => Unit::where('code', 'g')->firstOrFail()->id,
        'amount' => 200, 'position' => 1,
    ]);
    RecipeStep::create(['recipe_id' => $recipe->id, 'position' => 1, 'body' => 'Preheat oven.']);

    $ld = jsonLdFor('ld-rel');

    expect($ld['recipeIngredient'][0])->toContain('200')->toContain('Chicken Breast')
        ->and($ld['recipeInstructions'][0]['@type'])->toBe('HowToStep')
        ->and($ld['recipeInstructions'][0]['text'])->toBe('Preheat oven.');
});

test('jsonLd omits nutrition when no per-serving data is present', function () {
    Recipe::factory()->published()->create([
        'author_id' => User::factory()->create()->id, 'slug' => 'ld-nonutr',
    ]);

    expect(jsonLdFor('ld-nonutr'))->not->toHaveKey('nutrition');
});

test('jsonLd includes nutrition when per-serving data is available', function () {
    Recipe::factory()->published()->create([
        'author_id' => User::factory()->create()->id, 'slug' => 'ld-nutr',
        'kcal_per_serving' => 350, 'protein_per_serving_g' => 25,
        'fat_per_serving_g' => 12, 'carbs_per_serving_g' => 40, 'fiber_per_serving_g' => 5,
    ]);

    $ld = jsonLdFor('ld-nutr');

    expect($ld)->toHaveKey('nutrition')
        ->and($ld['nutrition']['@type'])->toBe('NutritionInformation')
        ->and($ld['nutrition']['calories'])->toContain('350');
});
