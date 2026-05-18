<?php

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use App\Models\Unit;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);
});

// --- Meta tags on landing page ---

test('landing page has meta description', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<meta name="description"', false);
});

test('landing page has open graph tags', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<meta property="og:type" content="website">', false)
        ->assertSee('<meta property="og:title"', false)
        ->assertSee('<meta property="og:description"', false)
        ->assertSee('<meta property="og:url"', false)
        ->assertSee('<meta property="og:site_name"', false);
});

test('landing page has twitter card tags', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
        ->assertSee('<meta name="twitter:title"', false)
        ->assertSee('<meta name="twitter:description"', false);
});

test('landing page has hreflang tags', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('hreflang="en"', false)
        ->assertSee('hreflang="uk"', false)
        ->assertSee('hreflang="x-default"', false);
});

test('landing page has canonical tag', function () {
    $response = $this->get('/');
    $response->assertOk()
        ->assertSee('<link rel="canonical"', false);
});

// --- Meta tags on recipe catalog ---

test('catalog page has meta description from translation', function () {
    $this->get(route('recipes.index'))
        ->assertOk()
        ->assertSee('<meta name="description" content="Browse our collection of curated recipes.">', false);
});

test('catalog page has canonical url', function () {
    $this->get(route('recipes.index'))
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('recipes.index').'">', false);
});

test('catalog page title includes app name', function () {
    $this->get(route('recipes.index'))
        ->assertOk()
        ->assertSee('<title>Recipes — Recipe Hub</title>', false);
});

// --- Meta tags on recipe detail ---

test('recipe detail has og:type article', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => User::factory()->create()->id,
        'slug' => 'seo-test',
        'title' => 'SEO Test Recipe',
        'summary' => 'A recipe for testing SEO.',
    ]);

    $this->get(route('recipes.show', 'seo-test'))
        ->assertOk()
        ->assertSee('<meta property="og:type" content="article">', false);
});

test('recipe detail has meta description from summary', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => User::factory()->create()->id,
        'slug' => 'seo-desc',
        'title' => 'Description Test',
        'summary' => 'This is the recipe summary for SEO.',
    ]);

    $this->get(route('recipes.show', 'seo-desc'))
        ->assertOk()
        ->assertSee('<meta name="description" content="This is the recipe summary for SEO.">', false);
});

test('recipe detail has canonical url', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => User::factory()->create()->id,
        'slug' => 'canonical-test',
    ]);

    $this->get(route('recipes.show', 'canonical-test'))
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('recipes.show', 'canonical-test').'">', false);
});

// --- JSON-LD structured data ---

test('recipe detail has json-ld structured data', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => User::factory()->create()->id,
        'slug' => 'jsonld-test',
        'title' => 'JSON-LD Recipe',
        'summary' => 'A structured data test.',
        'servings' => 4,
        'prep_time_min' => 15,
        'cook_time_min' => 30,
        'total_time_min' => 45,
    ]);

    $this->get(route('recipes.show', 'jsonld-test'))
        ->assertOk()
        ->assertSee('application/ld+json', false)
        ->assertSee('"@type":"Recipe"', false)
        ->assertSee('"name":"JSON-LD Recipe"', false);
});

test('json-ld contains prep and cook times in iso 8601', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => User::factory()->create()->id,
        'slug' => 'time-test',
        'prep_time_min' => 10,
        'cook_time_min' => 20,
        'total_time_min' => 30,
    ]);

    $this->get(route('recipes.show', 'time-test'))
        ->assertOk()
        ->assertSee('"prepTime":"PT10M"', false)
        ->assertSee('"cookTime":"PT20M"', false)
        ->assertSee('"totalTime":"PT30M"', false);
});

test('json-ld contains recipe ingredients', function () {
    $author = User::factory()->create();
    $recipe = Recipe::factory()->published()->create(['author_id' => $author->id, 'slug' => 'ingr-ld']);
    $unit = Unit::firstOrCreate(['code' => 'g'], ['name' => 'gram', 'type' => 'mass', 'to_base_factor' => 1.0]);
    $ingredient = Ingredient::factory()->create(['name' => 'Chicken Breast']);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $unit->id,
        'amount' => 200,
        'position' => 1,
    ]);

    $this->get(route('recipes.show', 'ingr-ld'))
        ->assertOk()
        ->assertSee('"recipeIngredient"', false)
        ->assertSee('Chicken Breast', false);
});

test('json-ld contains recipe steps', function () {
    $author = User::factory()->create();
    $recipe = Recipe::factory()->published()->create(['author_id' => $author->id, 'slug' => 'steps-ld']);
    RecipeStep::create(['recipe_id' => $recipe->id, 'position' => 1, 'body' => 'Preheat oven to 180C.']);

    $this->get(route('recipes.show', 'steps-ld'))
        ->assertOk()
        ->assertSee('"HowToStep"', false)
        ->assertSee('Preheat oven to 180C.', false);
});

test('json-ld contains nutrition when available', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => User::factory()->create()->id,
        'slug' => 'nutr-ld',
        'kcal_per_serving' => 350,
        'protein_per_serving_g' => 25,
        'fat_per_serving_g' => 12,
        'carbs_per_serving_g' => 40,
        'fiber_per_serving_g' => 5,
    ]);

    $this->get(route('recipes.show', 'nutr-ld'))
        ->assertOk()
        ->assertSee('"NutritionInformation"', false)
        ->assertSee('"calories"', false);
});

test('json-ld contains author name', function () {
    $author = User::factory()->create(['name' => 'Chef Test']);
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $author->id,
        'slug' => 'author-ld',
    ]);

    $this->get(route('recipes.show', 'author-ld'))
        ->assertOk()
        ->assertSee('"@type":"Person"', false)
        ->assertSee('"name":"Chef Test"', false);
});

// --- Sitemap removed during lockdown (2026-05-17). See docs/lockdown-plan.md. ---

test('sitemap.xml is not exposed', function () {
    $this->get('/sitemap.xml')->assertNotFound();
});

// --- robots.txt disallows everything during lockdown ---

test('robots.txt disallows all crawling', function () {
    $content = file_get_contents(public_path('robots.txt'));

    expect($content)
        ->toContain('User-agent: *')
        ->toContain('Disallow: /');
});

test('robots.txt does not advertise sitemap', function () {
    $content = file_get_contents(public_path('robots.txt'));

    expect($content)->not->toContain('Sitemap:');
});

// --- noindex meta on layouts ---

test('app layout emits noindex robots meta', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">', false);
});

// --- hreflang on public pages ---

test('recipe detail has hreflang tags', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => User::factory()->create()->id,
        'slug' => 'hreflang-test',
    ]);

    $this->get(route('recipes.show', 'hreflang-test'))
        ->assertOk()
        ->assertSee('hreflang="en"', false)
        ->assertSee('hreflang="uk"', false)
        ->assertSee('hreflang="x-default"', false);
});

test('catalog has hreflang tags', function () {
    $this->get(route('recipes.index'))
        ->assertOk()
        ->assertSee('hreflang="en"', false)
        ->assertSee('hreflang="uk"', false)
        ->assertSee('hreflang="x-default"', false);
});
