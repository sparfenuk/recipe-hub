<?php

use App\Enums\RecipeStatus;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Tag;
use App\Models\Unit;
use App\Models\User;
use App\Services\RecipeDuplicationService;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UnitSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(UnitSeeder::class);
    $this->service = new RecipeDuplicationService;
});

test('duplicate clones a recipe as a draft with copied relations', function () {
    $author = User::factory()->create();
    $unit = Unit::where('code', 'g')->firstOrFail();
    $ingredient = Ingredient::factory()->create();

    $original = Recipe::factory()->create([
        'title' => 'Source Recipe',
        'slug' => 'source-recipe',
        'author_id' => $author->id,
        'status' => 'published',
        'published_at' => now(),
    ]);
    $original->recipeIngredients()->create([
        'ingredient_id' => $ingredient->id, 'amount' => 200, 'unit_id' => $unit->id, 'position' => 0,
    ]);
    $original->steps()->create(['position' => 0, 'body' => 'Do the thing.']);
    $tag = Tag::create(['slug' => 'svc-tag', 'name' => 'Svc Tag', 'type' => 'misc']);
    $original->tags()->attach($tag);

    $clone = $this->service->duplicate($original);

    expect($clone->is($original))->toBeFalse()
        ->and($clone->status)->toBe(RecipeStatus::Draft)
        ->and($clone->published_at)->toBeNull()
        ->and($clone->nutrition_cached_at)->toBeNull()
        ->and($clone->slug)->toBe('source-recipe-copy')
        ->and($clone->title)->toBe('Source Recipe (Copy)')
        ->and($clone->recipeIngredients()->count())->toBe(1)
        ->and($clone->steps()->count())->toBe(1)
        ->and($clone->tags()->count())->toBe(1);
});

test('duplicate de-duplicates the slug on collision', function () {
    $recipe = Recipe::factory()->create(['title' => 'Collide', 'slug' => 'collide']);

    $first = $this->service->duplicate($recipe);
    $second = $this->service->duplicate($recipe);

    expect($first->slug)->toBe('collide-copy')
        ->and($second->slug)->toBe('collide-copy-2');
});
