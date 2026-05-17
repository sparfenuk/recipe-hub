<?php

use App\Livewire\RecipeBrowser;
use App\Models\Recipe;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->author = User::factory()->create();
});

test('search returns matching recipes by title', function () {
    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Chicken Tikka Masala',
    ]);

    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Vegetable Stir Fry',
    ]);

    Livewire::test(RecipeBrowser::class)
        ->set('search', 'Chicken')
        ->assertSee('Chicken Tikka Masala')
        ->assertDontSee('Vegetable Stir Fry');
});

test('search returns matching recipes by summary', function () {
    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Mystery Dish',
        'summary' => 'A creamy mushroom risotto with parmesan',
    ]);

    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Another Dish',
        'summary' => 'Simple grilled steak',
    ]);

    Livewire::test(RecipeBrowser::class)
        ->set('search', 'risotto')
        ->assertSee('Mystery Dish')
        ->assertDontSee('Another Dish');
});

test('empty search shows all published recipes', function () {
    Recipe::factory()->published()->count(3)->create([
        'author_id' => $this->author->id,
    ]);

    $component = Livewire::test(RecipeBrowser::class)
        ->set('search', '');

    $recipes = $component->viewData('recipes');
    expect($recipes->total())->toBe(3);
});

test('search only returns published recipes', function () {
    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Published Pasta',
    ]);

    Recipe::factory()->create([
        'author_id' => $this->author->id,
        'title' => 'Draft Pasta',
        'status' => 'draft',
    ]);

    Livewire::test(RecipeBrowser::class)
        ->set('search', 'Pasta')
        ->assertSee('Published Pasta')
        ->assertDontSee('Draft Pasta');
});

test('recipe model has searchable array with expected fields', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Test Recipe',
        'summary' => 'A test summary',
        'description' => '<p>Test description</p>',
    ]);

    $array = $recipe->toSearchableArray();

    expect($array)->toHaveKeys(['id', 'title_en', 'title_uk', 'summary_en', 'summary_uk', 'description_en', 'description_uk', 'ingredient_names_en', 'ingredient_names_uk'])
        ->and($array['title_en'])->toBe('Test Recipe')
        ->and($array['summary_en'])->toBe('A test summary')
        ->and($array['description_en'])->toBe('Test description');
});

test('recipe shouldBeSearchable only when published', function () {
    $published = Recipe::factory()->published()->create(['author_id' => $this->author->id]);
    $draft = Recipe::factory()->create(['author_id' => $this->author->id, 'status' => 'draft']);

    expect($published->shouldBeSearchable())->toBeTrue()
        ->and($draft->shouldBeSearchable())->toBeFalse();
});
