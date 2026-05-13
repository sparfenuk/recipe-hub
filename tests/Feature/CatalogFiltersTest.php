<?php

use App\Livewire\RecipeBrowser;
use App\Models\Allergen;
use App\Models\Ingredient;
use App\Models\Recipe;
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

test('filter by max kcal per serving', function () {
    $low = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Light Salad',
    ]);
    $low->updateQuietly(['kcal_per_serving' => 200]);

    $high = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Heavy Pasta',
    ]);
    $high->updateQuietly(['kcal_per_serving' => 800]);

    Livewire::test(RecipeBrowser::class)
        ->set('max_kcal', 400)
        ->assertSee('Light Salad')
        ->assertDontSee('Heavy Pasta');
});

test('filter by max prep time', function () {
    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Quick Toast',
        'prep_time_min' => 5,
    ]);

    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Slow Roast',
        'prep_time_min' => 60,
    ]);

    Livewire::test(RecipeBrowser::class)
        ->set('max_prep_time', 15)
        ->assertSee('Quick Toast')
        ->assertDontSee('Slow Roast');
});

test('filter by diet tags', function () {
    $vegan = Tag::create(['slug' => 'vegan', 'name' => 'Vegan', 'type' => 'diet']);

    $veganRecipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Vegan Bowl',
    ]);
    $veganRecipe->tags()->attach($vegan);

    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Steak Dinner',
    ]);

    Livewire::test(RecipeBrowser::class)
        ->set('diet_tags', [$vegan->id])
        ->assertSee('Vegan Bowl')
        ->assertDontSee('Steak Dinner');
});

test('filter excludes recipes with allergens', function () {
    $gluten = Allergen::create(['slug' => 'gluten', 'name' => 'Gluten']);
    $unit = Unit::create(['code' => 'g', 'name' => 'gram', 'type' => 'mass', 'to_base_factor' => 1]);

    $wheat = Ingredient::factory()->create(['name' => 'Wheat Flour', 'is_active' => true]);
    $wheat->allergens()->attach($gluten);

    $glutenRecipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Bread Roll',
    ]);
    $glutenRecipe->recipeIngredients()->create([
        'ingredient_id' => $wheat->id,
        'amount' => 200,
        'unit_id' => $unit->id,
        'position' => 0,
    ]);

    $safeRecipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Rice Bowl',
    ]);

    Livewire::test(RecipeBrowser::class)
        ->set('exclude_allergens', [$gluten->id])
        ->assertSee('Rice Bowl')
        ->assertDontSee('Bread Roll');
});

test('sort by lowest kcal', function () {
    $high = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'AAA High Cal',
    ]);
    $high->updateQuietly(['kcal_per_serving' => 900]);

    $low = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'ZZZ Low Cal',
    ]);
    $low->updateQuietly(['kcal_per_serving' => 100]);

    $component = Livewire::test(RecipeBrowser::class)
        ->set('sort', 'lowest_kcal');

    $recipes = $component->viewData('recipes');
    expect($recipes->first()->title)->toBe('ZZZ Low Cal');
});

test('sort by shortest prep', function () {
    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Slow Recipe',
        'prep_time_min' => 60,
    ]);

    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Fast Recipe',
        'prep_time_min' => 5,
    ]);

    $component = Livewire::test(RecipeBrowser::class)
        ->set('sort', 'shortest_prep');

    $recipes = $component->viewData('recipes');
    expect($recipes->first()->title)->toBe('Fast Recipe');
});

test('clear filters resets all advanced filters', function () {
    $tag = Tag::create(['slug' => 'keto', 'name' => 'Keto', 'type' => 'diet']);

    Livewire::test(RecipeBrowser::class)
        ->set('max_kcal', 500)
        ->set('max_prep_time', 30)
        ->set('diet_tags', [$tag->id])
        ->set('sort', 'lowest_kcal')
        ->call('clearFilters')
        ->assertSet('max_kcal', null)
        ->assertSet('max_prep_time', null)
        ->assertSet('diet_tags', [])
        ->assertSet('exclude_allergens', [])
        ->assertSet('sort', 'newest');
});
