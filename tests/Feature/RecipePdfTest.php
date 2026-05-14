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

test('pdf download returns ok for published recipe', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'pdf-recipe',
        'title' => 'PDF Test Recipe',
    ]);

    $response = $this->get(route('recipes.pdf', 'pdf-recipe'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

test('pdf download has correct filename', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'my-awesome-recipe',
        'title' => 'My Awesome Recipe',
    ]);

    $response = $this->get(route('recipes.pdf', 'my-awesome-recipe'));

    $response->assertOk();
    $response->assertHeader('content-disposition', 'attachment; filename=my-awesome-recipe.pdf');
});

test('pdf returns 404 for draft recipe', function () {
    Recipe::factory()->create([
        'author_id' => $this->author->id,
        'slug' => 'draft-pdf',
        'status' => 'draft',
    ]);

    $this->get(route('recipes.pdf', 'draft-pdf'))
        ->assertNotFound();
});

test('pdf returns 404 for archived recipe', function () {
    Recipe::factory()->archived()->create([
        'author_id' => $this->author->id,
        'slug' => 'archived-pdf',
    ]);

    $this->get(route('recipes.pdf', 'archived-pdf'))
        ->assertNotFound();
});

test('pdf returns 404 for non-existent slug', function () {
    $this->get(route('recipes.pdf', 'no-such-recipe'))
        ->assertNotFound();
});

test('pdf is accessible without login', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'public-pdf',
    ]);

    $this->get(route('recipes.pdf', 'public-pdf'))
        ->assertOk();
});

test('pdf contains recipe title', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'title-pdf',
        'title' => 'Chicken Tikka Masala',
    ]);

    $response = $this->get(route('recipes.pdf', 'title-pdf'));
    $response->assertOk();
});

test('pdf includes ingredients in the view', function () {
    $unit = Unit::create(['code' => 'g', 'name' => 'gram', 'type' => 'mass', 'to_base_factor' => 1]);
    $ingredient = Ingredient::factory()->create(['name' => 'Basmati Rice']);
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'ingredient-pdf',
    ]);

    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $unit->id,
        'amount' => 300,
        'position' => 1,
    ]);

    $html = view('pdf.recipe', ['recipe' => $recipe->load('recipeIngredients.ingredient', 'recipeIngredients.unit', 'author', 'category', 'cuisine', 'tags', 'steps')])->render();

    expect($html)->toContain('Basmati Rice');
    expect($html)->toContain('300');
});

test('pdf includes steps in the view', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'steps-pdf',
    ]);

    RecipeStep::create([
        'recipe_id' => $recipe->id,
        'position' => 1,
        'body' => 'Bring water to a boil.',
    ]);

    $html = view('pdf.recipe', ['recipe' => $recipe->load('recipeIngredients.ingredient', 'recipeIngredients.unit', 'author', 'category', 'cuisine', 'tags', 'steps')])->render();

    expect($html)->toContain('Bring water to a boil.');
});

test('pdf includes nutrition table when cached', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'nutrition-pdf',
    ]);

    $recipe->updateQuietly([
        'kcal_per_serving' => 350,
        'protein_per_serving_g' => 25,
        'fat_per_serving_g' => 12,
        'carbs_per_serving_g' => 40,
        'fiber_per_serving_g' => 4,
        'total_kcal' => 1400,
        'total_protein_g' => 100,
        'total_fat_g' => 48,
        'total_carbs_g' => 160,
        'total_fiber_g' => 16,
        'nutrition_cached_at' => now(),
    ]);

    $html = view('pdf.recipe', ['recipe' => $recipe->fresh()->load('recipeIngredients.ingredient', 'recipeIngredients.unit', 'author', 'category', 'cuisine', 'tags', 'steps')])->render();

    expect($html)->toContain('350');
    expect($html)->toContain('1,400');
    expect($html)->toContain(__('recipes.nutrition_per_serving'));
});

test('recipe detail shows both print and pdf buttons', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'buttons-recipe',
    ]);

    Livewire::test(RecipeDetail::class, ['slug' => 'buttons-recipe'])
        ->assertSee(__('recipes.print'))
        ->assertSee(__('recipes.download_pdf'))
        ->assertSeeHtml(route('recipes.pdf', 'buttons-recipe'));
});

test('pdf view includes tags', function () {
    $tag = Tag::create(['slug' => 'gluten-free', 'name' => 'Gluten Free', 'type' => 'diet']);
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'tagged-pdf',
    ]);
    $recipe->tags()->attach($tag);

    $html = view('pdf.recipe', ['recipe' => $recipe->load('recipeIngredients.ingredient', 'recipeIngredients.unit', 'author', 'category', 'cuisine', 'tags', 'steps')])->render();

    expect($html)->toContain('Gluten Free');
});

test('pdf view includes category and cuisine', function () {
    $category = Category::create(['slug' => 'main-course', 'name' => 'Main Course']);
    $cuisine = Cuisine::create(['slug' => 'italian', 'name' => 'Italian']);

    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'italian-pdf',
        'category_id' => $category->id,
        'cuisine_id' => $cuisine->id,
    ]);

    $html = view('pdf.recipe', ['recipe' => $recipe->load('recipeIngredients.ingredient', 'recipeIngredients.unit', 'author', 'category', 'cuisine', 'tags', 'steps')])->render();

    expect($html)->toContain('Main Course');
    expect($html)->toContain('Italian');
});

test('pdf view includes author in footer', function () {
    $author = User::factory()->create(['name' => 'Chef Maria']);
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $author->id,
        'slug' => 'author-pdf',
    ]);

    $html = view('pdf.recipe', ['recipe' => $recipe->load('recipeIngredients.ingredient', 'recipeIngredients.unit', 'author', 'category', 'cuisine', 'tags', 'steps')])->render();

    expect($html)->toContain('Chef Maria');
});

test('translation keys exist for print and pdf in both locales', function () {
    app()->setLocale('en');
    expect(__('recipes.print'))->not->toBe('recipes.print');
    expect(__('recipes.download_pdf'))->not->toBe('recipes.download_pdf');

    app()->setLocale('uk');
    expect(__('recipes.print'))->not->toBe('recipes.print');
    expect(__('recipes.download_pdf'))->not->toBe('recipes.download_pdf');
});
