<?php

use App\Filament\Resources\RecipeResource;
use App\Filament\Resources\RecipeResource\Pages\CreateRecipe;
use App\Filament\Resources\RecipeResource\Pages\EditRecipe;
use App\Filament\Resources\RecipeResource\Pages\ListRecipes;
use App\Models\Category;
use App\Models\Cuisine;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Tag;
use App\Models\Unit;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->unit = Unit::create([
        'code' => 'g',
        'name' => 'gram',
        'type' => 'mass',
        'to_base_factor' => 1,
    ]);

    $this->ingredient = Ingredient::factory()->create([
        'is_active' => true,
        'kcal_per_100g' => 100,
        'protein_g' => 10,
        'fat_g' => 5,
        'carbs_g' => 20,
        'fiber_g' => 3,
    ]);
});

test('admin can list recipes', function () {
    $recipe = Recipe::factory()->create(['author_id' => $this->admin->id]);

    Livewire::actingAs($this->admin)
        ->test(ListRecipes::class)
        ->assertCanSeeTableRecords([$recipe]);
});

test('admin can create a recipe', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateRecipe::class)
        ->fillForm([
            'title' => 'Test Recipe',
            'slug' => 'test-recipe',
            'servings' => 4,
            'difficulty' => 'easy',
            'status' => 'draft',
            'prep_time_min' => 15,
            'cook_time_min' => 30,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $recipe = Recipe::where('slug', 'test-recipe')->first();
    expect($recipe)->not->toBeNull()
        ->and($recipe->title)->toBe('Test Recipe')
        ->and($recipe->servings)->toBe(4)
        ->and($recipe->difficulty)->toBe('easy')
        ->and($recipe->author_id)->toBe($this->admin->id)
        ->and($recipe->total_time_min)->toBe(45);
});

test('admin can edit a recipe', function () {
    $recipe = Recipe::factory()->create([
        'author_id' => $this->admin->id,
        'title' => 'Old Title',
        'slug' => 'old-title',
    ]);

    Livewire::actingAs($this->admin)
        ->test(EditRecipe::class, ['record' => $recipe->getRouteKey()])
        ->fillForm([
            'title' => 'New Title',
            'slug' => 'new-title',
            'servings' => 6,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($recipe->fresh()->title)->toBe('New Title')
        ->and($recipe->fresh()->servings)->toBe(6);
});

test('admin can delete a recipe (soft delete)', function () {
    $recipe = Recipe::factory()->create(['author_id' => $this->admin->id]);

    Livewire::actingAs($this->admin)
        ->test(EditRecipe::class, ['record' => $recipe->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect(Recipe::find($recipe->id))->toBeNull()
        ->and(Recipe::withTrashed()->find($recipe->id))->not->toBeNull();
});

test('recipe slug must be unique', function () {
    Recipe::factory()->create(['slug' => 'existing-slug', 'author_id' => $this->admin->id]);

    Livewire::actingAs($this->admin)
        ->test(CreateRecipe::class)
        ->fillForm([
            'title' => 'Another Recipe',
            'slug' => 'existing-slug',
            'servings' => 2,
            'difficulty' => 'easy',
            'status' => 'draft',
        ])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);
});

test('recipe sets published_at when status is published', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateRecipe::class)
        ->fillForm([
            'title' => 'Published Recipe',
            'slug' => 'published-recipe',
            'servings' => 4,
            'difficulty' => 'medium',
            'status' => 'published',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $recipe = Recipe::where('slug', 'published-recipe')->first();
    expect($recipe->published_at)->not->toBeNull();
});

test('recipe computes total_time_min on create', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateRecipe::class)
        ->fillForm([
            'title' => 'Time Test',
            'slug' => 'time-test',
            'servings' => 2,
            'difficulty' => 'easy',
            'status' => 'draft',
            'prep_time_min' => 10,
            'cook_time_min' => 25,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Recipe::where('slug', 'time-test')->first()->total_time_min)->toBe(35);
});

test('recipe computes total_time_min on edit', function () {
    $recipe = Recipe::factory()->create([
        'author_id' => $this->admin->id,
        'prep_time_min' => 5,
        'cook_time_min' => 10,
        'total_time_min' => 15,
    ]);

    Livewire::actingAs($this->admin)
        ->test(EditRecipe::class, ['record' => $recipe->getRouteKey()])
        ->fillForm([
            'prep_time_min' => 20,
            'cook_time_min' => 40,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($recipe->fresh()->total_time_min)->toBe(60);
});

test('recipe can have tags attached', function () {
    $vegan = Tag::create(['slug' => 'vegan', 'name' => 'Vegan', 'type' => 'diet']);
    $quick = Tag::create(['slug' => 'quick', 'name' => 'Quick', 'type' => 'misc']);

    Livewire::actingAs($this->admin)
        ->test(CreateRecipe::class)
        ->fillForm([
            'title' => 'Tagged Recipe',
            'slug' => 'tagged-recipe',
            'servings' => 2,
            'difficulty' => 'easy',
            'status' => 'draft',
            'tags' => [$vegan->id, $quick->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $recipe = Recipe::where('slug', 'tagged-recipe')->first();
    expect($recipe->tags)->toHaveCount(2);
});

test('recipe list can be filtered by status', function () {
    $draft = Recipe::factory()->create(['status' => 'draft', 'author_id' => $this->admin->id]);
    $published = Recipe::factory()->published()->create(['author_id' => $this->admin->id]);

    Livewire::actingAs($this->admin)
        ->test(ListRecipes::class)
        ->assertCanSeeTableRecords([$draft, $published])
        ->filterTable('status', 'published')
        ->assertCanSeeTableRecords([$published])
        ->assertCanNotSeeTableRecords([$draft]);
});

test('recipe list can be filtered by category', function () {
    $mainCourse = Category::create(['slug' => 'main-course', 'name' => 'Main Course']);
    $dessert = Category::create(['slug' => 'dessert', 'name' => 'Dessert']);

    $recipe1 = Recipe::factory()->create([
        'author_id' => $this->admin->id,
        'category_id' => $mainCourse->id,
    ]);
    $recipe2 = Recipe::factory()->create([
        'author_id' => $this->admin->id,
        'category_id' => $dessert->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ListRecipes::class)
        ->assertCanSeeTableRecords([$recipe1, $recipe2])
        ->filterTable('category_id', $mainCourse->id)
        ->assertCanSeeTableRecords([$recipe1])
        ->assertCanNotSeeTableRecords([$recipe2]);
});

test('recipe list can be filtered by cuisine', function () {
    $italian = Cuisine::create(['slug' => 'italian', 'name' => 'Italian']);
    $japanese = Cuisine::create(['slug' => 'japanese', 'name' => 'Japanese']);

    $recipe1 = Recipe::factory()->create([
        'author_id' => $this->admin->id,
        'cuisine_id' => $italian->id,
    ]);
    $recipe2 = Recipe::factory()->create([
        'author_id' => $this->admin->id,
        'cuisine_id' => $japanese->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ListRecipes::class)
        ->assertCanSeeTableRecords([$recipe1, $recipe2])
        ->filterTable('cuisine_id', $italian->id)
        ->assertCanSeeTableRecords([$recipe1])
        ->assertCanNotSeeTableRecords([$recipe2]);
});

test('recipe list can be searched by title', function () {
    $pasta = Recipe::factory()->create([
        'title' => 'Pasta Carbonara',
        'slug' => 'pasta-carbonara',
        'author_id' => $this->admin->id,
    ]);
    $salad = Recipe::factory()->create([
        'title' => 'Caesar Salad',
        'slug' => 'caesar-salad',
        'author_id' => $this->admin->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ListRecipes::class)
        ->searchTable('Pasta')
        ->assertCanSeeTableRecords([$pasta])
        ->assertCanNotSeeTableRecords([$salad]);
});

test('bulk publish sets status and published_at', function () {
    $draft1 = Recipe::factory()->create(['status' => 'draft', 'author_id' => $this->admin->id]);
    $draft2 = Recipe::factory()->create(['status' => 'draft', 'author_id' => $this->admin->id]);

    Livewire::actingAs($this->admin)
        ->test(ListRecipes::class)
        ->callTableBulkAction('publish', [$draft1, $draft2]);

    expect($draft1->fresh()->status)->toBe('published')
        ->and($draft1->fresh()->published_at)->not->toBeNull()
        ->and($draft2->fresh()->status)->toBe('published')
        ->and($draft2->fresh()->published_at)->not->toBeNull();
});

test('bulk archive sets status', function () {
    $published = Recipe::factory()->published()->create(['author_id' => $this->admin->id]);

    Livewire::actingAs($this->admin)
        ->test(ListRecipes::class)
        ->callTableBulkAction('archive', [$published]);

    expect($published->fresh()->status)->toBe('archived');
});

test('duplicate action creates a copy', function () {
    $original = Recipe::factory()->create([
        'title' => 'Original Recipe',
        'slug' => 'original-recipe',
        'author_id' => $this->admin->id,
        'status' => 'published',
        'published_at' => now(),
    ]);

    $original->recipeIngredients()->create([
        'ingredient_id' => $this->ingredient->id,
        'amount' => 200,
        'unit_id' => $this->unit->id,
        'position' => 0,
    ]);

    $original->steps()->create([
        'position' => 0,
        'body' => 'Step 1: Cook the thing.',
    ]);

    $tag = Tag::create(['slug' => 'test-tag', 'name' => 'Test Tag', 'type' => 'misc']);
    $original->tags()->attach($tag);

    RecipeResource::duplicateRecipe($original);

    $clone = Recipe::where('slug', 'original-recipe-copy')->first();

    expect($clone)->not->toBeNull()
        ->and($clone->title)->toBe('Original Recipe (Copy)')
        ->and($clone->status)->toBe('draft')
        ->and($clone->published_at)->toBeNull()
        ->and($clone->recipeIngredients)->toHaveCount(1)
        ->and($clone->steps)->toHaveCount(1)
        ->and($clone->tags)->toHaveCount(1);
});

test('duplicate generates unique slug on collision', function () {
    $recipe = Recipe::factory()->create([
        'title' => 'Unique Test',
        'slug' => 'unique-test',
        'author_id' => $this->admin->id,
    ]);

    $clone1 = RecipeResource::duplicateRecipe($recipe);
    $clone2 = RecipeResource::duplicateRecipe($recipe);

    expect($clone1->slug)->toBe('unique-test-copy')
        ->and($clone2->slug)->toBe('unique-test-copy-2');
});

test('non-admin cannot access recipe resource', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(RecipeResource::getUrl('index'))
        ->assertForbidden();
});

test('nutrition section renders on edit page with cached data', function () {
    $recipe = Recipe::factory()->create([
        'author_id' => $this->admin->id,
        'kcal_per_serving' => 250,
        'total_kcal' => 1000,
        'nutrition_cached_at' => now(),
    ]);

    Livewire::actingAs($this->admin)
        ->test(EditRecipe::class, ['record' => $recipe->getRouteKey()])
        ->assertSuccessful()
        ->assertSee('Per serving')
        ->assertSee('Nutrition');
});

test('recipe ingredients are saved via repeater on edit', function () {
    $recipe = Recipe::factory()->create(['author_id' => $this->admin->id]);

    Livewire::actingAs($this->admin)
        ->test(EditRecipe::class, ['record' => $recipe->getRouteKey()])
        ->fillForm([
            'recipeIngredients' => [
                [
                    'ingredient_id' => $this->ingredient->id,
                    'amount' => 150,
                    'unit_id' => $this->unit->id,
                    'note' => 'chopped',
                    'is_optional' => false,
                    'group_label' => '',
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $recipe->refresh();
    expect($recipe->recipeIngredients)->toHaveCount(1)
        ->and((float) $recipe->recipeIngredients->first()->amount)->toBe(150.0)
        ->and($recipe->recipeIngredients->first()->note)->toBe('chopped');
});

test('recipe steps are saved via repeater on edit', function () {
    $recipe = Recipe::factory()->create(['author_id' => $this->admin->id]);

    Livewire::actingAs($this->admin)
        ->test(EditRecipe::class, ['record' => $recipe->getRouteKey()])
        ->fillForm([
            'steps' => [
                ['body' => 'Boil water.'],
                ['body' => 'Add pasta.'],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $recipe->refresh();
    expect($recipe->steps)->toHaveCount(2)
        ->and($recipe->steps->first()->body)->toBe('Boil water.')
        ->and($recipe->steps->last()->body)->toBe('Add pasta.');
});

test('nutrition recomputes after save with ingredients', function () {
    $recipe = Recipe::factory()->create([
        'author_id' => $this->admin->id,
        'servings' => 2,
    ]);

    $recipe->recipeIngredients()->create([
        'ingredient_id' => $this->ingredient->id,
        'amount' => 200,
        'unit_id' => $this->unit->id,
        'position' => 0,
    ]);

    Livewire::actingAs($this->admin)
        ->test(EditRecipe::class, ['record' => $recipe->getRouteKey()])
        ->call('save')
        ->assertHasNoFormErrors();

    $recipe->refresh();
    expect((float) $recipe->total_kcal)->toBe(200.0)
        ->and((float) $recipe->kcal_per_serving)->toBe(100.0)
        ->and($recipe->nutrition_cached_at)->not->toBeNull();
});
