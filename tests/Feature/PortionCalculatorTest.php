<?php

use App\Livewire\PortionCalculator;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->author = User::factory()->create();
    $this->unit = Unit::create(['code' => 'g', 'name' => 'gram', 'type' => 'mass', 'to_base_factor' => 1]);
});

test('calculator renders with default servings', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->assertSet('originalServings', 4)
        ->assertSet('targetServings', 4)
        ->assertSee(__('calculator.title'));
});

test('calculator shows scaled ingredient amounts when servings doubled', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 2,
    ]);

    $ingredient = Ingredient::factory()->create(['name' => 'Flour']);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $this->unit->id,
        'amount' => 200,
        'position' => 1,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->assertSee('200')
        ->assertSee('Flour')
        ->set('targetServings', 4)
        ->assertSee('400')
        ->assertSee('Flour');
});

test('calculator shows scaled nutrition totals', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 2,
    ]);

    $recipe->updateQuietly([
        'total_kcal' => 800,
        'total_protein_g' => 40,
        'total_fat_g' => 30,
        'total_carbs_g' => 100,
        'total_fiber_g' => 10,
        'kcal_per_serving' => 400,
        'protein_per_serving_g' => 20,
        'fat_per_serving_g' => 15,
        'carbs_per_serving_g' => 50,
        'fiber_per_serving_g' => 5,
        'nutrition_cached_at' => now(),
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->set('targetServings', 4)
        ->assertSee('1,600')
        ->assertSee('80.0')
        ->assertSee('60.0')
        ->assertSee('200.0')
        ->assertSee('20.0');
});

test('per-serving nutrition stays constant when scaling by servings', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $recipe->updateQuietly([
        'total_kcal' => 2000,
        'kcal_per_serving' => 500,
        'protein_per_serving_g' => 25,
        'fat_per_serving_g' => 20,
        'carbs_per_serving_g' => 60,
        'fiber_per_serving_g' => 8,
        'nutrition_cached_at' => now(),
    ]);

    $component = Livewire::test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->set('targetServings', 8);

    $nutrition = $component->get('scaledNutrition');
    expect($nutrition['kcal_per_serving'])->toBe(500.0)
        ->and($nutrition['protein_per_serving_g'])->toBe(25.0)
        ->and($nutrition['fat_per_serving_g'])->toBe(20.0)
        ->and($nutrition['carbs_per_serving_g'])->toBe(60.0)
        ->and($nutrition['fiber_per_serving_g'])->toBe(8.0);
});

test('increment and decrement buttons work', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->assertSet('targetServings', 4)
        ->call('increment')
        ->assertSet('targetServings', 5)
        ->call('decrement')
        ->call('decrement')
        ->assertSet('targetServings', 3);
});

test('decrement does not go below 1', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 1,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->call('decrement')
        ->assertSet('targetServings', 1);
});

test('increment does not exceed 100', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 100,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->call('increment')
        ->assertSet('targetServings', 100);
});

test('reset restores original servings', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->set('targetServings', 10)
        ->call('resetServings')
        ->assertSet('targetServings', 4);
});

test('calculator shows reset link only when scaled', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->assertDontSee(__('calculator.reset', ['servings' => 4]))
        ->set('targetServings', 6)
        ->assertSee(__('calculator.reset', ['servings' => 4]));
});

test('calculator handles multiple ingredients with grouping', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 2,
    ]);

    $flour = Ingredient::factory()->create(['name' => 'Flour']);
    $butter = Ingredient::factory()->create(['name' => 'Butter']);

    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $flour->id,
        'unit_id' => $this->unit->id,
        'amount' => 300,
        'position' => 1,
        'group_label' => 'Dough',
    ]);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $butter->id,
        'unit_id' => $this->unit->id,
        'amount' => 100,
        'position' => 2,
        'group_label' => 'Dough',
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->set('targetServings', 6)
        ->assertSee('900')
        ->assertSee('300')
        ->assertSee('Dough');
});

test('calculator shows optional ingredients', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 2,
    ]);

    $parsley = Ingredient::factory()->create(['name' => 'Parsley']);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $parsley->id,
        'unit_id' => $this->unit->id,
        'amount' => 10,
        'position' => 1,
        'is_optional' => true,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->set('targetServings', 4)
        ->assertSee('20')
        ->assertSee('Parsley')
        ->assertSee(__('recipes.optional'));
});

test('scale factor is 1 when target equals original', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $component = Livewire::test(PortionCalculator::class, ['recipe' => $recipe]);
    expect($component->get('scaleFactor'))->toBe(1.0);
});

test('scale factor handles null target gracefully', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $component = Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->set('targetServings', null);

    expect($component->get('scaleFactor'))->toBe(1.0);
});

test('calculator is embedded on recipe detail page', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'calc-embed-test',
        'servings' => 4,
    ]);

    $this->get(route('recipes.show', 'calc-embed-test'))
        ->assertOk()
        ->assertSeeLivewire(PortionCalculator::class);
});

test('total nutrition label changes when scaled', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $recipe->updateQuietly([
        'total_kcal' => 1000,
        'nutrition_cached_at' => now(),
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->assertSee(__('recipes.nutrition_total'))
        ->set('targetServings', 8)
        ->assertSee(__('calculator.total_for_servings', ['servings' => 8]));
});

test('fractional scaling produces correct amounts', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $ingredient = Ingredient::factory()->create(['name' => 'Sugar']);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $this->unit->id,
        'amount' => 100,
        'position' => 1,
    ]);

    $component = Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->set('targetServings', 3);

    $ingredients = collect($component->get('scaledIngredients'));
    expect($ingredients[0]['amount'])->toBe(75.0);
});
