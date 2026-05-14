<?php

use App\Livewire\IngredientAutocomplete;
use App\Livewire\RecipeBrowser;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->unit = Unit::create(['code' => 'g', 'name' => 'gram', 'type' => 'mass', 'to_base_factor' => 1]);
    $this->author = User::factory()->create();
});

test('autocomplete renders with include mode', function () {
    Livewire::test(IngredientAutocomplete::class, ['mode' => 'include'])
        ->assertSee(__('recipes.include_ingredients'))
        ->assertDontSee(__('recipes.exclude_ingredients'));
});

test('autocomplete renders with exclude mode', function () {
    Livewire::test(IngredientAutocomplete::class, ['mode' => 'exclude'])
        ->assertSee(__('recipes.exclude_ingredients'))
        ->assertDontSee(__('recipes.include_ingredients'));
});

test('autocomplete returns results for query of 2+ characters', function () {
    Ingredient::factory()->create(['name' => 'Chicken Breast', 'is_active' => true]);
    Ingredient::factory()->create(['name' => 'Chickpeas', 'is_active' => true]);
    Ingredient::factory()->create(['name' => 'Beef Steak', 'is_active' => true]);

    Livewire::test(IngredientAutocomplete::class, ['mode' => 'include'])
        ->set('query', 'Ch')
        ->assertSee('Chicken Breast')
        ->assertSee('Chickpeas')
        ->assertDontSee('Beef Steak');
});

test('autocomplete does not search for queries shorter than 2 characters', function () {
    Ingredient::factory()->create(['name' => 'Chicken Breast', 'is_active' => true]);

    Livewire::test(IngredientAutocomplete::class, ['mode' => 'include'])
        ->set('query', 'C')
        ->assertDontSee('Chicken Breast');
});

test('autocomplete excludes inactive ingredients', function () {
    Ingredient::factory()->create(['name' => 'Active Rice', 'is_active' => true]);
    Ingredient::factory()->create(['name' => 'Inactive Rice', 'is_active' => false]);

    Livewire::test(IngredientAutocomplete::class, ['mode' => 'include'])
        ->set('query', 'Rice')
        ->assertSee('Active Rice')
        ->assertDontSee('Inactive Rice');
});

test('selecting an ingredient adds it to selected and dispatches event', function () {
    $chicken = Ingredient::factory()->create(['name' => 'Chicken', 'is_active' => true]);

    Livewire::test(IngredientAutocomplete::class, ['mode' => 'include'])
        ->call('selectIngredient', $chicken->id, 'Chicken')
        ->assertSet('selected', [$chicken->id => 'Chicken'])
        ->assertSet('query', '')
        ->assertDispatched('ingredient-filter-updated', mode: 'include', ids: [$chicken->id]);
});

test('removing an ingredient dispatches updated event', function () {
    $chicken = Ingredient::factory()->create(['name' => 'Chicken', 'is_active' => true]);

    Livewire::test(IngredientAutocomplete::class, ['mode' => 'exclude'])
        ->call('selectIngredient', $chicken->id, 'Chicken')
        ->call('removeIngredient', $chicken->id)
        ->assertSet('selected', [])
        ->assertDispatched('ingredient-filter-updated', mode: 'exclude', ids: []);
});

test('selected ingredients are excluded from results', function () {
    $chicken = Ingredient::factory()->create(['name' => 'Chicken Breast', 'is_active' => true]);
    $thigh = Ingredient::factory()->create(['name' => 'Chicken Thigh', 'is_active' => true]);

    $component = Livewire::test(IngredientAutocomplete::class, ['mode' => 'include'])
        ->call('selectIngredient', $chicken->id, 'Chicken Breast')
        ->set('query', 'Chicken');

    $results = $component->viewData('results');
    expect($results->pluck('id')->toArray())->not->toContain($chicken->id);
    expect($results->pluck('id')->toArray())->toContain($thigh->id);
});

test('clear-ingredient-filters event resets selection', function () {
    $chicken = Ingredient::factory()->create(['name' => 'Chicken', 'is_active' => true]);

    Livewire::test(IngredientAutocomplete::class, ['mode' => 'include'])
        ->call('selectIngredient', $chicken->id, 'Chicken')
        ->assertSet('selected', [$chicken->id => 'Chicken'])
        ->dispatch('clear-ingredient-filters')
        ->assertSet('selected', [])
        ->assertSet('query', '');
});

test('multiple ingredients can be selected', function () {
    $chicken = Ingredient::factory()->create(['name' => 'Chicken', 'is_active' => true]);
    $rice = Ingredient::factory()->create(['name' => 'Rice', 'is_active' => true]);

    Livewire::test(IngredientAutocomplete::class, ['mode' => 'include'])
        ->call('selectIngredient', $chicken->id, 'Chicken')
        ->call('selectIngredient', $rice->id, 'Rice')
        ->assertSet('selected', [$chicken->id => 'Chicken', $rice->id => 'Rice'])
        ->assertSee('Chicken')
        ->assertSee('Rice');
});

test('duplicate selection is ignored', function () {
    $chicken = Ingredient::factory()->create(['name' => 'Chicken', 'is_active' => true]);

    Livewire::test(IngredientAutocomplete::class, ['mode' => 'include'])
        ->call('selectIngredient', $chicken->id, 'Chicken')
        ->call('selectIngredient', $chicken->id, 'Chicken')
        ->assertSet('selected', [$chicken->id => 'Chicken']);
});

test('recipe browser filters by included ingredient', function () {
    $chicken = Ingredient::factory()->create(['name' => 'Chicken', 'is_active' => true]);

    $withChicken = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Chicken Curry',
    ]);
    $withChicken->recipeIngredients()->create([
        'ingredient_id' => $chicken->id,
        'amount' => 500,
        'unit_id' => $this->unit->id,
        'position' => 0,
    ]);

    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Veggie Soup',
    ]);

    Livewire::test(RecipeBrowser::class)
        ->set('include_ingredients', [$chicken->id])
        ->assertSee('Chicken Curry')
        ->assertDontSee('Veggie Soup');
});

test('recipe browser filters by excluded ingredient', function () {
    $shrimp = Ingredient::factory()->create(['name' => 'Shrimp', 'is_active' => true]);

    $withShrimp = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Shrimp Pasta',
    ]);
    $withShrimp->recipeIngredients()->create([
        'ingredient_id' => $shrimp->id,
        'amount' => 300,
        'unit_id' => $this->unit->id,
        'position' => 0,
    ]);

    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Tomato Salad',
    ]);

    Livewire::test(RecipeBrowser::class)
        ->set('exclude_ingredients', [$shrimp->id])
        ->assertSee('Tomato Salad')
        ->assertDontSee('Shrimp Pasta');
});

test('recipe browser requires all included ingredients', function () {
    $chicken = Ingredient::factory()->create(['name' => 'Chicken', 'is_active' => true]);
    $rice = Ingredient::factory()->create(['name' => 'Rice', 'is_active' => true]);

    $hasBoth = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Chicken Rice Bowl',
    ]);
    $hasBoth->recipeIngredients()->create([
        'ingredient_id' => $chicken->id,
        'amount' => 500,
        'unit_id' => $this->unit->id,
        'position' => 0,
    ]);
    $hasBoth->recipeIngredients()->create([
        'ingredient_id' => $rice->id,
        'amount' => 200,
        'unit_id' => $this->unit->id,
        'position' => 1,
    ]);

    $hasOnlyChicken = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Grilled Chicken',
    ]);
    $hasOnlyChicken->recipeIngredients()->create([
        'ingredient_id' => $chicken->id,
        'amount' => 500,
        'unit_id' => $this->unit->id,
        'position' => 0,
    ]);

    Livewire::test(RecipeBrowser::class)
        ->set('include_ingredients', [$chicken->id, $rice->id])
        ->assertSee('Chicken Rice Bowl')
        ->assertDontSee('Grilled Chicken');
});

test('ingredient filter event updates recipe browser state', function () {
    $chicken = Ingredient::factory()->create(['name' => 'Chicken', 'is_active' => true]);

    Livewire::test(RecipeBrowser::class)
        ->dispatch('ingredient-filter-updated', mode: 'include', ids: [$chicken->id])
        ->assertSet('include_ingredients', [$chicken->id]);
});

test('clear filters resets ingredient filters', function () {
    $chicken = Ingredient::factory()->create(['name' => 'Chicken', 'is_active' => true]);

    Livewire::test(RecipeBrowser::class)
        ->set('include_ingredients', [$chicken->id])
        ->set('exclude_ingredients', [$chicken->id])
        ->call('clearFilters')
        ->assertSet('include_ingredients', [])
        ->assertSet('exclude_ingredients', [])
        ->assertDispatched('clear-ingredient-filters');
});

test('ingredient filters appear in has active filters check', function () {
    Livewire::test(RecipeBrowser::class)
        ->assertSet('include_ingredients', [])
        ->set('include_ingredients', [1])
        ->call('hasActiveFilters')
        ->assertReturned(true);
});

test('autocomplete component renders in recipe browser sidebar', function () {
    Livewire::test(RecipeBrowser::class)
        ->assertSeeLivewire(IngredientAutocomplete::class);
});
