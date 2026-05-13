<?php

use App\Livewire\RecipeBrowser;
use App\Models\Category;
use App\Models\Cuisine;
use App\Models\Recipe;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->author = User::factory()->create();
});

test('recipe catalog page loads', function () {
    $this->get(route('recipes.index'))
        ->assertOk();
});

test('catalog shows published recipes', function () {
    $published = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Published Recipe',
    ]);

    $draft = Recipe::factory()->create([
        'author_id' => $this->author->id,
        'title' => 'Draft Recipe',
        'status' => 'draft',
    ]);

    Livewire::test(RecipeBrowser::class)
        ->assertSee('Published Recipe')
        ->assertDontSee('Draft Recipe');
});

test('catalog shows archived recipes are excluded', function () {
    $archived = Recipe::factory()->archived()->create([
        'author_id' => $this->author->id,
        'title' => 'Archived Recipe',
    ]);

    Livewire::test(RecipeBrowser::class)
        ->assertDontSee('Archived Recipe');
});

test('catalog filters by category', function () {
    $mains = Category::create(['slug' => 'mains', 'name' => 'Main Dishes']);
    $desserts = Category::create(['slug' => 'desserts', 'name' => 'Desserts']);

    $recipe1 = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Pasta Dish',
        'category_id' => $mains->id,
    ]);

    $recipe2 = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Chocolate Cake',
        'category_id' => $desserts->id,
    ]);

    Livewire::test(RecipeBrowser::class)
        ->set('category_id', $mains->id)
        ->assertSee('Pasta Dish')
        ->assertDontSee('Chocolate Cake');
});

test('catalog filters by cuisine', function () {
    $italian = Cuisine::create(['slug' => 'italian', 'name' => 'Italian']);
    $japanese = Cuisine::create(['slug' => 'japanese', 'name' => 'Japanese']);

    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Spaghetti',
        'cuisine_id' => $italian->id,
    ]);

    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Sushi',
        'cuisine_id' => $japanese->id,
    ]);

    Livewire::test(RecipeBrowser::class)
        ->set('cuisine_id', $japanese->id)
        ->assertSee('Sushi')
        ->assertDontSee('Spaghetti');
});

test('catalog shows empty state when no recipes', function () {
    Livewire::test(RecipeBrowser::class)
        ->assertSee(__('recipes.no_recipes'));
});

test('catalog paginates results', function () {
    Recipe::factory()->published()->count(15)->create([
        'author_id' => $this->author->id,
    ]);

    Livewire::test(RecipeBrowser::class)
        ->assertViewHas('recipes', fn ($recipes) => $recipes->count() === 12);
});

test('catalog clear filters resets both filters', function () {
    $category = Category::create(['slug' => 'test', 'name' => 'Test']);
    $cuisine = Cuisine::create(['slug' => 'test', 'name' => 'Test']);

    Livewire::test(RecipeBrowser::class)
        ->set('category_id', $category->id)
        ->set('cuisine_id', $cuisine->id)
        ->call('clearFilters')
        ->assertSet('category_id', null)
        ->assertSet('cuisine_id', null);
});

test('catalog displays recipe metadata', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Test Recipe',
        'prep_time_min' => 20,
        'difficulty' => 'easy',
    ]);

    $recipe->updateQuietly(['kcal_per_serving' => 350]);

    Livewire::test(RecipeBrowser::class)
        ->assertSee('Test Recipe')
        ->assertSee('350')
        ->assertSee('20')
        ->assertSee('Easy');
});

test('catalog is accessible without login', function () {
    $this->get(route('recipes.index'))
        ->assertOk()
        ->assertSeeLivewire(RecipeBrowser::class);
});
