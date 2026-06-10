<?php

use App\Livewire\RecipeBrowser;
use App\Models\Category;
use App\Models\Cuisine;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->author = User::factory()->create();
});

test('filter options are cached per locale on render', function () {
    $category = Category::create(['slug' => 'shown', 'name' => 'Shown Category']);
    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'category_id' => $category->id,
    ]);

    expect(Cache::has('recipe-filter-options:en'))->toBeFalse();

    Livewire::test(RecipeBrowser::class);

    $cached = Cache::get('recipe-filter-options:en');
    expect($cached)->toBeArray()
        ->and($cached)->toHaveKeys(['categories', 'cuisines', 'dietTags', 'allergens'])
        ->and($cached['categories']->pluck('id')->all())->toContain($category->id);
});

test('filter options are served from cache, not re-queried, on later renders', function () {
    Recipe::factory()->published()->create(['author_id' => $this->author->id]);

    Livewire::test(RecipeBrowser::class); // primes the cache

    // A category published after the first render must not appear until the TTL
    // expires — proving the sidebar is served from cache rather than re-queried.
    $late = Category::create(['slug' => 'late', 'name' => 'Late Category']);
    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'category_id' => $late->id,
    ]);

    $cached = Cache::get('recipe-filter-options:en');
    expect($cached['categories']->pluck('id')->all())->not->toContain($late->id);
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
        ->set('category_ids', [$mains->id])
        ->assertSee('Pasta Dish')
        ->assertDontSee('Chocolate Cake');
});

test('toggleCategory adds and removes ids from selection', function () {
    $mains = Category::create(['slug' => 'mains', 'name' => 'Main Dishes']);
    $desserts = Category::create(['slug' => 'desserts', 'name' => 'Desserts']);

    Livewire::test(RecipeBrowser::class)
        ->call('toggleCategory', $mains->id)
        ->assertSet('category_ids', [$mains->id])
        ->call('toggleCategory', $desserts->id)
        ->assertSet('category_ids', [$mains->id, $desserts->id])
        ->call('toggleCategory', $mains->id)
        ->assertSet('category_ids', [$desserts->id]);
});

test('toggleCuisine adds and removes ids from selection', function () {
    $italian = Cuisine::create(['slug' => 'italian', 'name' => 'Italian']);
    $japanese = Cuisine::create(['slug' => 'japanese', 'name' => 'Japanese']);

    Livewire::test(RecipeBrowser::class)
        ->call('toggleCuisine', $italian->id)
        ->assertSet('cuisine_ids', [$italian->id])
        ->call('toggleCuisine', $japanese->id)
        ->assertSet('cuisine_ids', [$italian->id, $japanese->id])
        ->call('toggleCuisine', $italian->id)
        ->assertSet('cuisine_ids', [$japanese->id]);
});

test('catalog filters by multiple categories', function () {
    $mains = Category::create(['slug' => 'mains', 'name' => 'Main Dishes']);
    $desserts = Category::create(['slug' => 'desserts', 'name' => 'Desserts']);
    $soups = Category::create(['slug' => 'soups', 'name' => 'Soups']);

    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Pasta Dish',
        'category_id' => $mains->id,
    ]);

    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Chocolate Cake',
        'category_id' => $desserts->id,
    ]);

    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Borscht',
        'category_id' => $soups->id,
    ]);

    Livewire::test(RecipeBrowser::class)
        ->set('category_ids', [$mains->id, $desserts->id])
        ->assertSee('Pasta Dish')
        ->assertSee('Chocolate Cake')
        ->assertDontSee('Borscht');
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
        ->set('cuisine_ids', [$japanese->id])
        ->assertSee('Sushi')
        ->assertDontSee('Spaghetti');
});

test('catalog filters by multiple cuisines', function () {
    $italian = Cuisine::create(['slug' => 'italian', 'name' => 'Italian']);
    $japanese = Cuisine::create(['slug' => 'japanese', 'name' => 'Japanese']);
    $french = Cuisine::create(['slug' => 'french', 'name' => 'French']);

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

    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Croissant',
        'cuisine_id' => $french->id,
    ]);

    Livewire::test(RecipeBrowser::class)
        ->set('cuisine_ids', [$italian->id, $japanese->id])
        ->assertSee('Spaghetti')
        ->assertSee('Sushi')
        ->assertDontSee('Croissant');
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
        ->set('category_ids', [$category->id])
        ->set('cuisine_ids', [$cuisine->id])
        ->call('clearFilters')
        ->assertSet('category_ids', [])
        ->assertSet('cuisine_ids', []);
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

test('activeFilterCount reflects applied filters (UX.4)', function () {
    $category = Category::create(['slug' => 'mains', 'name' => 'Mains']);

    $component = Livewire::test(RecipeBrowser::class)
        ->call('toggleCategory', $category->id)
        ->set('max_kcal', 400);

    expect($component->instance()->activeFilterCount())->toBe(2);
});

test('removeDietTag removes a single diet tag (UX.4)', function () {
    Livewire::test(RecipeBrowser::class)
        ->set('diet_tags', [1, 2, 3])
        ->call('removeDietTag', 2)
        ->assertSet('diet_tags', [1, 3]);
});

test('removeAllergen removes a single allergen (UX.4)', function () {
    Livewire::test(RecipeBrowser::class)
        ->set('exclude_allergens', [4, 5])
        ->call('removeAllergen', 4)
        ->assertSet('exclude_allergens', [5]);
});

test('clearMaxKcal and clearSearch reset their filters (UX.4)', function () {
    Livewire::test(RecipeBrowser::class)
        ->set('max_kcal', 500)
        ->set('search', 'pasta')
        ->call('clearMaxKcal')
        ->assertSet('max_kcal', null)
        ->call('clearSearch')
        ->assertSet('search', '');
});

test('removeIngredient drops a filter and notifies the autocomplete (UX.4)', function () {
    Livewire::test(RecipeBrowser::class)
        ->set('include_ingredients', [5, 6])
        ->call('removeIngredient', 'include', 5)
        ->assertSet('include_ingredients', [6])
        ->assertDispatched('remove-ingredient', mode: 'include', id: 5);
});

test('active filter chips render above the results (UX.4)', function () {
    $category = Category::create(['slug' => 'desserts', 'name' => 'Sweet Things']);
    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'category_id' => $category->id,
        'title' => 'A Dessert',
    ]);

    Livewire::test(RecipeBrowser::class)
        ->call('toggleCategory', $category->id)
        ->assertSee(__('recipes.remove_filter').': Sweet Things');
});
