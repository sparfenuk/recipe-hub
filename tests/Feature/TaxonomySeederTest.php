<?php

use App\Models\Allergen;
use App\Models\Category;
use App\Models\Cuisine;
use App\Models\IngredientCategory;
use App\Models\Tag;
use Database\Seeders\AllergenSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CuisineSeeder;
use Database\Seeders\IngredientCategorySeeder;
use Database\Seeders\TagSeeder;

test('ingredient category seeder creates expected records', function () {
    $this->seed(IngredientCategorySeeder::class);

    expect(IngredientCategory::count())->toBe(16)
        ->and(IngredientCategory::where('slug', 'vegetables')->exists())->toBeTrue()
        ->and(IngredientCategory::where('slug', 'dairy')->exists())->toBeTrue();
});

test('ingredient category seeder is idempotent', function () {
    $this->seed(IngredientCategorySeeder::class);
    $this->seed(IngredientCategorySeeder::class);

    expect(IngredientCategory::count())->toBe(16);
});

test('cuisine seeder creates expected records', function () {
    $this->seed(CuisineSeeder::class);

    expect(Cuisine::count())->toBe(20)
        ->and(Cuisine::where('slug', 'italian')->exists())->toBeTrue()
        ->and(Cuisine::where('slug', 'ukrainian')->exists())->toBeTrue();
});

test('cuisine seeder is idempotent', function () {
    $this->seed(CuisineSeeder::class);
    $this->seed(CuisineSeeder::class);

    expect(Cuisine::count())->toBe(20);
});

test('tag seeder creates expected records', function () {
    $this->seed(TagSeeder::class);

    expect(Tag::count())->toBe(18)
        ->and(Tag::where('type', 'diet')->count())->toBe(10)
        ->and(Tag::where('type', 'misc')->count())->toBe(8)
        ->and(Tag::where('slug', 'vegan')->exists())->toBeTrue()
        ->and(Tag::where('slug', 'quick')->exists())->toBeTrue();
});

test('tag seeder is idempotent', function () {
    $this->seed(TagSeeder::class);
    $this->seed(TagSeeder::class);

    expect(Tag::count())->toBe(18);
});

test('allergen seeder creates all 9 allergens from spec', function () {
    $this->seed(AllergenSeeder::class);

    $expected = ['gluten', 'lactose', 'nuts', 'soy', 'eggs', 'fish', 'shellfish', 'sesame', 'mustard'];

    expect(Allergen::count())->toBe(9);

    foreach ($expected as $slug) {
        expect(Allergen::where('slug', $slug)->exists())->toBeTrue();
    }
});

test('allergen seeder is idempotent', function () {
    $this->seed(AllergenSeeder::class);
    $this->seed(AllergenSeeder::class);

    expect(Allergen::count())->toBe(9);
});

test('category seeder creates expected records', function () {
    $this->seed(CategorySeeder::class);

    expect(Category::count())->toBe(14)
        ->and(Category::where('slug', 'breakfast')->exists())->toBeTrue()
        ->and(Category::where('slug', 'desserts')->exists())->toBeTrue();
});

test('category seeder is idempotent', function () {
    $this->seed(CategorySeeder::class);
    $this->seed(CategorySeeder::class);

    expect(Category::count())->toBe(14);
});
