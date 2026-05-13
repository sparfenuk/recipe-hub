<?php

use App\Livewire\RecipeDetail;
use App\Models\Category;
use App\Models\Cuisine;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use App\Models\Tag;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->author = User::factory()->create();
});

test('recipe detail page loads for published recipe', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'test-recipe',
        'title' => 'Test Recipe Title',
    ]);

    $this->get(route('recipes.show', 'test-recipe'))
        ->assertOk()
        ->assertSeeLivewire(RecipeDetail::class);
});

test('recipe detail shows title and summary', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'delicious-pasta',
        'title' => 'Delicious Pasta',
        'summary' => 'A wonderful pasta dish',
    ]);

    Livewire::test(RecipeDetail::class, ['slug' => 'delicious-pasta'])
        ->assertSee('Delicious Pasta')
        ->assertSee('A wonderful pasta dish');
});

test('recipe detail shows meta badges', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'quick-salad',
        'title' => 'Quick Salad',
        'prep_time_min' => 10,
        'cook_time_min' => 0,
        'total_time_min' => 10,
        'servings' => 4,
        'difficulty' => 'easy',
    ]);

    Livewire::test(RecipeDetail::class, ['slug' => 'quick-salad'])
        ->assertSee('10')
        ->assertSee('4')
        ->assertSee(__('recipes.difficulty_easy'));
});

test('recipe detail shows ingredients', function () {
    $unit = Unit::create(['code' => 'g', 'name' => 'gram', 'type' => 'mass', 'to_base_factor' => 1]);
    $ingredient = Ingredient::factory()->create(['name' => 'Chicken Breast']);
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'chicken-dish',
    ]);

    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $unit->id,
        'amount' => 500,
        'position' => 1,
    ]);

    Livewire::test(RecipeDetail::class, ['slug' => 'chicken-dish'])
        ->assertSee('Chicken Breast')
        ->assertSee('500')
        ->assertSee('g');
});

test('recipe detail shows optional ingredients', function () {
    $unit = Unit::create(['code' => 'g', 'name' => 'gram', 'type' => 'mass', 'to_base_factor' => 1]);
    $ingredient = Ingredient::factory()->create(['name' => 'Parsley']);
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'parsley-dish',
    ]);

    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $unit->id,
        'amount' => 10,
        'position' => 1,
        'is_optional' => true,
    ]);

    Livewire::test(RecipeDetail::class, ['slug' => 'parsley-dish'])
        ->assertSee('Parsley')
        ->assertSee(__('recipes.optional'));
});

test('recipe detail shows ingredient notes', function () {
    $unit = Unit::create(['code' => 'g', 'name' => 'gram', 'type' => 'mass', 'to_base_factor' => 1]);
    $ingredient = Ingredient::factory()->create(['name' => 'Onion']);
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'onion-dish',
    ]);

    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $unit->id,
        'amount' => 200,
        'position' => 1,
        'note' => 'finely diced',
    ]);

    Livewire::test(RecipeDetail::class, ['slug' => 'onion-dish'])
        ->assertSee('Onion')
        ->assertSee('finely diced');
});

test('recipe detail shows numbered steps', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'step-recipe',
    ]);

    RecipeStep::create([
        'recipe_id' => $recipe->id,
        'position' => 1,
        'body' => 'Preheat the oven to 180C.',
    ]);

    RecipeStep::create([
        'recipe_id' => $recipe->id,
        'position' => 2,
        'body' => 'Mix all ingredients together.',
    ]);

    Livewire::test(RecipeDetail::class, ['slug' => 'step-recipe'])
        ->assertSee('Preheat the oven to 180C.')
        ->assertSee('Mix all ingredients together.')
        ->assertSeeInOrder(['1', 'Preheat', '2', 'Mix']);
});

test('recipe detail shows nutrition panel', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'nutritious-meal',
    ]);

    $recipe->updateQuietly([
        'kcal_per_serving' => 450.50,
        'protein_per_serving_g' => 30.2,
        'fat_per_serving_g' => 18.5,
        'carbs_per_serving_g' => 42.1,
        'fiber_per_serving_g' => 5.8,
        'total_kcal' => 1802,
        'total_protein_g' => 120.8,
        'total_fat_g' => 74,
        'total_carbs_g' => 168.4,
        'total_fiber_g' => 23.2,
        'nutrition_cached_at' => now(),
    ]);

    Livewire::test(RecipeDetail::class, ['slug' => 'nutritious-meal'])
        ->assertSee(__('recipes.nutrition_per_serving'))
        ->assertSee('451')
        ->assertSee('30.2')
        ->assertSee('18.5')
        ->assertSee('42.1')
        ->assertSee('5.8');
});

test('recipe detail shows tags', function () {
    $tag = Tag::create(['slug' => 'vegan', 'name' => 'Vegan', 'type' => 'diet']);
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'vegan-dish',
    ]);
    $recipe->tags()->attach($tag);

    Livewire::test(RecipeDetail::class, ['slug' => 'vegan-dish'])
        ->assertSee('Vegan');
});

test('recipe detail shows category and cuisine', function () {
    $category = Category::create(['slug' => 'soups', 'name' => 'Soups']);
    $cuisine = Cuisine::create(['slug' => 'french', 'name' => 'French']);

    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'french-soup',
        'category_id' => $category->id,
        'cuisine_id' => $cuisine->id,
    ]);

    Livewire::test(RecipeDetail::class, ['slug' => 'french-soup'])
        ->assertSee('Soups')
        ->assertSee('French');
});

test('recipe detail shows author name', function () {
    $author = User::factory()->create(['name' => 'Chef Gordon']);
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $author->id,
        'slug' => 'gordon-recipe',
    ]);

    Livewire::test(RecipeDetail::class, ['slug' => 'gordon-recipe'])
        ->assertSee('Chef Gordon');
});

test('recipe detail returns 404 for draft recipe', function () {
    Recipe::factory()->create([
        'author_id' => $this->author->id,
        'slug' => 'draft-recipe',
        'status' => 'draft',
    ]);

    $this->get(route('recipes.show', 'draft-recipe'))
        ->assertNotFound();
});

test('recipe detail returns 404 for archived recipe', function () {
    Recipe::factory()->archived()->create([
        'author_id' => $this->author->id,
        'slug' => 'archived-recipe',
    ]);

    $this->get(route('recipes.show', 'archived-recipe'))
        ->assertNotFound();
});

test('recipe detail returns 404 for non-existent slug', function () {
    $this->get(route('recipes.show', 'does-not-exist'))
        ->assertNotFound();
});

test('recipe detail has breadcrumb link to catalog', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'bread-recipe',
    ]);

    Livewire::test(RecipeDetail::class, ['slug' => 'bread-recipe'])
        ->assertSee(__('recipes.catalog'))
        ->assertSeeHtml(route('recipes.index'));
});

test('recipe detail is accessible without login', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'public-recipe',
    ]);

    $this->get(route('recipes.show', 'public-recipe'))
        ->assertOk();
});

test('recipe detail shows ingredient group labels', function () {
    $unit = Unit::create(['code' => 'g', 'name' => 'gram', 'type' => 'mass', 'to_base_factor' => 1]);
    $flour = Ingredient::factory()->create(['name' => 'Flour']);
    $sugar = Ingredient::factory()->create(['name' => 'Sugar']);
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'grouped-recipe',
    ]);

    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $flour->id,
        'unit_id' => $unit->id,
        'amount' => 200,
        'position' => 1,
        'group_label' => 'For the dough',
    ]);

    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $sugar->id,
        'unit_id' => $unit->id,
        'amount' => 50,
        'position' => 2,
        'group_label' => 'For the filling',
    ]);

    Livewire::test(RecipeDetail::class, ['slug' => 'grouped-recipe'])
        ->assertSee('For the dough')
        ->assertSee('For the filling')
        ->assertSee('Flour')
        ->assertSee('Sugar');
});

test('recipe detail shows print button', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'printable-recipe',
    ]);

    Livewire::test(RecipeDetail::class, ['slug' => 'printable-recipe'])
        ->assertSee(__('recipes.print'));
});
