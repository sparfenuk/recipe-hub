<?php

use App\Models\Recipe;
use App\Models\User;
use Database\Seeders\AllergenSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\IngredientCategorySeeder;
use Database\Seeders\IngredientSeeder;
use Database\Seeders\RecipeSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UnitSeeder;

beforeEach(function () {
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

afterEach(function () {
    RecipeSeeder::$dataPathOverride = null;
    RecipeSeeder::$imagesRootOverride = null;
});

it('reseeds recipes from the fixture', function () {
    $this->artisan('recipes:reseed')->assertSuccessful();

    expect(Recipe::count())->toBe(3);
});

it('is idempotent — re-running keeps the same recipe count', function () {
    $this->artisan('recipes:reseed')->assertSuccessful();
    $this->artisan('recipes:reseed')->assertSuccessful();

    expect(Recipe::count())->toBe(3);
});
