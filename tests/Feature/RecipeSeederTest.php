<?php

declare(strict_types=1);

use App\Models\Recipe;
use App\Models\User;
use Database\Seeders\AllergenSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\IngredientCategorySeeder;
use Database\Seeders\IngredientSeeder;
use Database\Seeders\RecipeSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UnitSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->seed(UnitSeeder::class);
    $this->seed(IngredientCategorySeeder::class);
    $this->seed(AllergenSeeder::class);
    $this->seed(CategorySeeder::class);
    $this->seed(IngredientSeeder::class);

    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $admin->assignRole('admin');

    RecipeSeeder::$dataPathOverride = base_path('tests/fixtures/recipes-seed-sample.json');
    RecipeSeeder::$imagesRootOverride = base_path('tests/fixtures');
});

afterEach(function (): void {
    RecipeSeeder::$dataPathOverride = null;
    RecipeSeeder::$imagesRootOverride = null;
});

it('seeds bilingual recipes from the fixture', function (): void {
    $this->seed(RecipeSeeder::class);

    expect(Recipe::count())->toBe(3);

    $first = Recipe::orderBy('id')->first();

    expect($first->getTranslation('title', 'en'))->toBe('Healthy crepes with Greek yogurt, cherries and cocoa')
        ->and($first->getTranslation('title', 'uk'))->toBe('Хелзі млинці з грецьким йогуртом, вишнями та какао')
        ->and($first->status)->toBe('published')
        ->and($first->published_at)->not->toBeNull();
});

it('attaches the hero image when the file exists', function (): void {
    $this->seed(RecipeSeeder::class);

    $first = Recipe::orderBy('id')->first();

    expect($first->getMedia('hero')->count())->toBe(1);
});

it('does not crash when the hero image is missing', function (): void {
    $this->seed(RecipeSeeder::class);

    $third = Recipe::orderBy('id')->skip(2)->first();

    expect($third)->not->toBeNull()
        ->and($third->getMedia('hero')->count())->toBe(0);
});

it('seeds bilingual recipe steps in order', function (): void {
    $this->seed(RecipeSeeder::class);

    $first = Recipe::orderBy('id')->first();
    $steps = $first->steps()->orderBy('position')->get();

    expect($steps->count())->toBeGreaterThan(0)
        ->and($steps->first()->getTranslation('body', 'en'))->not->toBeEmpty()
        ->and($steps->first()->getTranslation('body', 'uk'))->not->toBeEmpty();
});

it('is idempotent when re-run', function (): void {
    $this->seed(RecipeSeeder::class);
    $this->seed(RecipeSeeder::class);

    expect(Recipe::count())->toBe(3);
});

it('marks rows with unmappable units as optional and uses the taste unit', function (): void {
    $this->seed(RecipeSeeder::class);

    $first = Recipe::orderBy('id')->first();
    $optional = $first->recipeIngredients()->where('is_optional', true)->with('unit')->get();

    expect($optional->count())->toBeGreaterThan(0);

    $tasteRows = $optional->filter(fn ($ri) => $ri->unit?->code === 'taste');

    expect($tasteRows->count())->toBeGreaterThan(0);
});

it('fails with a clear error when no admin user exists', function (): void {
    User::query()->delete();

    expect(fn () => $this->seed(RecipeSeeder::class))
        ->toThrow(RuntimeException::class, 'admin user');
});
