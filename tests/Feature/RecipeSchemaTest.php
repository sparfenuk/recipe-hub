<?php

use App\Models\Category;
use App\Models\Cuisine;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use App\Models\Tag;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(UnitSeeder::class);
});

it('can create a recipe with all required fields', function () {
    $recipe = Recipe::factory()->create([
        'title' => 'Cabbage Rolls',
        'slug' => 'cabbage-rolls',
        'servings' => 4,
        'difficulty' => 'medium',
        'status' => 'draft',
    ]);

    expect($recipe->title)->toBe('Cabbage Rolls')
        ->and($recipe->slug)->toBe('cabbage-rolls')
        ->and($recipe->servings)->toBe(4)
        ->and($recipe->difficulty)->toBe('medium')
        ->and($recipe->status)->toBe('draft')
        ->and($recipe->is_featured)->toBeFalse();
});

it('has default values for nutrition columns', function () {
    $recipe = Recipe::factory()->create();

    expect((float) $recipe->total_kcal)->toBe(0.0)
        ->and((float) $recipe->kcal_per_serving)->toBe(0.0)
        ->and((float) $recipe->total_protein_g)->toBe(0.0)
        ->and((float) $recipe->total_fat_g)->toBe(0.0)
        ->and((float) $recipe->total_carbs_g)->toBe(0.0)
        ->and((float) $recipe->total_fiber_g)->toBe(0.0)
        ->and($recipe->nutrition_cached_at)->toBeNull();
});

it('belongs to an author', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create(['author_id' => $user->id]);

    expect($recipe->author->id)->toBe($user->id);
});

it('belongs to a category', function () {
    $category = Category::create(['slug' => 'mains', 'name' => 'Main Dishes']);
    $recipe = Recipe::factory()->create(['category_id' => $category->id]);

    expect($recipe->category->slug)->toBe('mains');
});

it('belongs to a cuisine', function () {
    $cuisine = Cuisine::create(['slug' => 'ukrainian', 'name' => 'Ukrainian']);
    $recipe = Recipe::factory()->create(['cuisine_id' => $cuisine->id]);

    expect($recipe->cuisine->slug)->toBe('ukrainian');
});

it('category and cuisine are nullable', function () {
    $recipe = Recipe::factory()->create(['category_id' => null, 'cuisine_id' => null]);

    expect($recipe->category)->toBeNull()
        ->and($recipe->cuisine)->toBeNull();
});

it('has many recipe ingredients ordered by position', function () {
    $recipe = Recipe::factory()->create();
    $ingredient1 = Ingredient::factory()->create();
    $ingredient2 = Ingredient::factory()->create();
    $gram = Unit::where('code', 'g')->first();

    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient2->id,
        'position' => 2,
        'amount' => 200,
        'unit_id' => $gram->id,
    ]);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient1->id,
        'position' => 1,
        'amount' => 100,
        'unit_id' => $gram->id,
    ]);

    $ingredients = $recipe->recipeIngredients;

    expect($ingredients)->toHaveCount(2)
        ->and($ingredients->first()->position)->toBe(1)
        ->and($ingredients->last()->position)->toBe(2);
});

it('recipe ingredient has all pivot fields', function () {
    $recipe = Recipe::factory()->create();
    $ingredient = Ingredient::factory()->create();
    $gram = Unit::where('code', 'g')->first();

    $ri = RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'position' => 1,
        'amount' => 150.500,
        'unit_id' => $gram->id,
        'grams_override' => 145.00,
        'note' => 'finely chopped',
        'is_optional' => true,
        'group_label' => 'Sauce',
    ]);

    $ri->refresh();

    expect((float) $ri->amount)->toBe(150.5)
        ->and((float) $ri->grams_override)->toBe(145.0)
        ->and($ri->note)->toBe('finely chopped')
        ->and($ri->is_optional)->toBeTrue()
        ->and($ri->group_label)->toBe('Sauce')
        ->and($ri->recipe->id)->toBe($recipe->id)
        ->and($ri->ingredient->id)->toBe($ingredient->id)
        ->and($ri->unit->code)->toBe('g');
});

it('has many steps ordered by position', function () {
    $recipe = Recipe::factory()->create();

    RecipeStep::create(['recipe_id' => $recipe->id, 'position' => 2, 'body' => 'Second step']);
    RecipeStep::create(['recipe_id' => $recipe->id, 'position' => 1, 'body' => 'First step']);

    $steps = $recipe->steps;

    expect($steps)->toHaveCount(2)
        ->and($steps->first()->body)->toBe('First step')
        ->and($steps->last()->body)->toBe('Second step');
});

it('has many tags via pivot', function () {
    $recipe = Recipe::factory()->create();
    $tag1 = Tag::create(['slug' => 'vegan', 'name' => 'Vegan', 'type' => 'diet']);
    $tag2 = Tag::create(['slug' => 'quick', 'name' => 'Quick', 'type' => 'misc']);

    $recipe->tags()->attach([$tag1->id, $tag2->id]);

    expect($recipe->tags)->toHaveCount(2);
});

it('slug must be unique', function () {
    Recipe::factory()->create(['slug' => 'test-recipe']);

    Recipe::factory()->create(['slug' => 'test-recipe']);
})->throws(QueryException::class);

it('supports soft deletes', function () {
    $recipe = Recipe::factory()->create();
    $id = $recipe->id;

    $recipe->delete();

    expect(Recipe::find($id))->toBeNull()
        ->and(Recipe::withTrashed()->find($id))->not->toBeNull();
});

it('cascade deletes recipe ingredients when recipe is deleted', function () {
    $recipe = Recipe::factory()->create();
    $ingredient = Ingredient::factory()->create();
    $gram = Unit::where('code', 'g')->first();

    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'position' => 1,
        'amount' => 100,
        'unit_id' => $gram->id,
    ]);

    $recipe->forceDelete();

    expect(RecipeIngredient::where('recipe_id', $recipe->id)->count())->toBe(0);
});

it('cascade deletes recipe steps when recipe is deleted', function () {
    $recipe = Recipe::factory()->create();
    RecipeStep::create(['recipe_id' => $recipe->id, 'position' => 1, 'body' => 'Step 1']);

    $recipe->forceDelete();

    expect(RecipeStep::where('recipe_id', $recipe->id)->count())->toBe(0);
});

it('cascade deletes recipe tags when recipe is deleted', function () {
    $recipe = Recipe::factory()->create();
    $tag = Tag::create(['slug' => 'test', 'name' => 'Test', 'type' => 'misc']);
    $recipe->tags()->attach($tag->id);

    $recipe->forceDelete();

    expect(DB::table('recipe_tag')->where('recipe_id', $recipe->id)->count())->toBe(0);
});

it('factory published state sets status and published_at', function () {
    $recipe = Recipe::factory()->published()->create();

    expect($recipe->status)->toBe('published')
        ->and($recipe->published_at)->not->toBeNull();
});

it('factory archived state sets status', function () {
    $recipe = Recipe::factory()->archived()->create();

    expect($recipe->status)->toBe('archived');
});

it('recipe has media collections for hero and gallery', function () {
    $recipe = Recipe::factory()->create();

    $collections = collect($recipe->getRegisteredMediaCollections())->pluck('name')->all();

    expect($collections)->toContain('hero')
        ->and($collections)->toContain('gallery');
});

it('recipe step has media collection for step photo', function () {
    $recipe = Recipe::factory()->create();
    $step = RecipeStep::create(['recipe_id' => $recipe->id, 'position' => 1, 'body' => 'Step']);

    $collections = collect($step->getRegisteredMediaCollections())->pluck('name')->all();

    expect($collections)->toContain('step_photo');
});

it('total_time_min is stored correctly', function () {
    $recipe = Recipe::factory()->create([
        'prep_time_min' => 15,
        'cook_time_min' => 30,
        'total_time_min' => 45,
    ]);

    expect($recipe->total_time_min)->toBe(45);
});

it('status enum rejects invalid values', function () {
    Recipe::factory()->create(['status' => 'invalid']);
})->throws(QueryException::class);

it('difficulty enum rejects invalid values', function () {
    Recipe::factory()->create(['difficulty' => 'extreme']);
})->throws(QueryException::class);
