<?php

use App\Models\Allergen;
use App\Models\Category;
use App\Models\Cuisine;
use App\Models\IngredientCategory;
use App\Models\Tag;
use Illuminate\Database\QueryException;

test('ingredient category can be created', function () {
    $category = IngredientCategory::create([
        'slug' => 'vegetables',
        'name' => 'Vegetables',
    ]);

    expect($category->slug)->toBe('vegetables')
        ->and($category->name)->toBe('Vegetables')
        ->and($category->parent_id)->toBeNull();
});

test('ingredient category supports parent-child hierarchy', function () {
    $parent = IngredientCategory::create(['slug' => 'vegetables', 'name' => 'Vegetables']);
    $child = IngredientCategory::create(['slug' => 'leafy-greens', 'name' => 'Leafy Greens', 'parent_id' => $parent->id]);

    expect($child->parent->id)->toBe($parent->id)
        ->and($parent->children)->toHaveCount(1)
        ->and($parent->children->first()->slug)->toBe('leafy-greens');
});

test('cuisine can be created', function () {
    $cuisine = Cuisine::create(['slug' => 'italian', 'name' => 'Italian']);

    expect($cuisine->slug)->toBe('italian')
        ->and($cuisine->name)->toBe('Italian');
});

test('tag can be created with type', function () {
    $tag = Tag::create(['slug' => 'vegan', 'name' => 'Vegan', 'type' => 'diet']);

    expect($tag->slug)->toBe('vegan')
        ->and($tag->type)->toBe('diet')
        ->and($tag->isDiet())->toBeTrue()
        ->and($tag->isCuisine())->toBeFalse()
        ->and($tag->isMisc())->toBeFalse();
});

test('tag type helpers work correctly', function () {
    $diet = Tag::create(['slug' => 'keto', 'name' => 'Keto', 'type' => 'diet']);
    $cuisine = Tag::create(['slug' => 'mediterranean', 'name' => 'Mediterranean', 'type' => 'cuisine']);
    $misc = Tag::create(['slug' => 'quick', 'name' => 'Quick', 'type' => 'misc']);

    expect($diet->isDiet())->toBeTrue()
        ->and($cuisine->isCuisine())->toBeTrue()
        ->and($misc->isMisc())->toBeTrue();
});

test('allergen can be created', function () {
    $allergen = Allergen::create(['slug' => 'gluten', 'name' => 'Gluten']);

    expect($allergen->slug)->toBe('gluten')
        ->and($allergen->name)->toBe('Gluten');
});

test('category can be created', function () {
    $category = Category::create(['slug' => 'breakfast', 'name' => 'Breakfast']);

    expect($category->slug)->toBe('breakfast')
        ->and($category->name)->toBe('Breakfast')
        ->and($category->parent_id)->toBeNull();
});

test('category supports parent-child hierarchy', function () {
    $parent = Category::create(['slug' => 'main-courses', 'name' => 'Main Courses']);
    $child = Category::create(['slug' => 'pasta', 'name' => 'Pasta', 'parent_id' => $parent->id]);

    expect($child->parent->id)->toBe($parent->id)
        ->and($parent->children)->toHaveCount(1)
        ->and($parent->children->first()->slug)->toBe('pasta');
});

test('slug uniqueness is enforced on ingredient categories', function () {
    IngredientCategory::create(['slug' => 'vegetables', 'name' => 'Vegetables']);

    expect(fn () => IngredientCategory::create(['slug' => 'vegetables', 'name' => 'Veggies']))
        ->toThrow(QueryException::class);
});

test('slug uniqueness is enforced on cuisines', function () {
    Cuisine::create(['slug' => 'italian', 'name' => 'Italian']);

    expect(fn () => Cuisine::create(['slug' => 'italian', 'name' => 'Italiano']))
        ->toThrow(QueryException::class);
});

test('slug uniqueness is enforced on tags', function () {
    Tag::create(['slug' => 'vegan', 'name' => 'Vegan', 'type' => 'diet']);

    expect(fn () => Tag::create(['slug' => 'vegan', 'name' => 'Vegan 2', 'type' => 'misc']))
        ->toThrow(QueryException::class);
});

test('slug uniqueness is enforced on allergens', function () {
    Allergen::create(['slug' => 'gluten', 'name' => 'Gluten']);

    expect(fn () => Allergen::create(['slug' => 'gluten', 'name' => 'Gluten 2']))
        ->toThrow(QueryException::class);
});

test('slug uniqueness is enforced on categories', function () {
    Category::create(['slug' => 'breakfast', 'name' => 'Breakfast']);

    expect(fn () => Category::create(['slug' => 'breakfast', 'name' => 'Breakfast 2']))
        ->toThrow(QueryException::class);
});
