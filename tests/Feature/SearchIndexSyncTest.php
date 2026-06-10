<?php

use App\Jobs\RecalculateRecipeNutrition;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Unit;
use App\Services\Nutrition\NutritionCalculator;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Queue;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\Engine;
use Mockery\MockInterface;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(UnitSeeder::class);
    $this->gram = Unit::where('code', 'g')->firstOrFail();
});

/**
 * Swap Scout's collection engine for a spy so we can assert when models are
 * (re-)indexed without booting Meilisearch.
 */
function spyScoutEngine(): MockInterface
{
    $engine = Mockery::spy(Engine::class);
    app(EngineManager::class)->extend('collection', fn () => $engine);

    return $engine;
}

function recipeUpdateMatcher(int $recipeId): Closure
{
    return fn ($models) => $models instanceof EloquentCollection
        && $models->contains(fn ($model) => $model instanceof Recipe && (int) $model->getKey() === $recipeId);
}

function assertRecipeIndexed(MockInterface $engine, int $recipeId): void
{
    $engine->shouldHaveReceived('update')->withArgs(recipeUpdateMatcher($recipeId));
}

function assertRecipeNotIndexed(MockInterface $engine, int $recipeId): void
{
    // Ingredients are searchable too, so the engine may receive other update()
    // calls; assert only that this recipe was never among them.
    $engine->shouldNotHaveReceived('update', [Mockery::on(recipeUpdateMatcher($recipeId))]);
}

function nutritiousIngredient(): Ingredient
{
    return Ingredient::factory()->create([
        'kcal_per_100g' => 100,
        'protein_g' => 5,
        'fat_g' => 2,
        'carbs_g' => 10,
        'fiber_g' => 1,
    ]);
}

it('re-indexes the recipe after the recompute job runs', function () {
    $engine = spyScoutEngine();

    $ingredient = nutritiousIngredient();
    $recipe = Recipe::factory()->published()->create(['servings' => 1]);
    RecipeIngredient::withoutEvents(fn () => RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $this->gram->id,
        'amount' => 200,
        'position' => 1,
    ]));

    (new RecalculateRecipeNutrition($recipe->id))->handle(new NutritionCalculator);

    assertRecipeIndexed($engine, $recipe->id);
});

it('does not re-index a draft recipe after recompute', function () {
    $engine = spyScoutEngine();

    $ingredient = nutritiousIngredient();
    $recipe = Recipe::factory()->create(['status' => 'draft', 'servings' => 1]);
    RecipeIngredient::withoutEvents(fn () => RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $this->gram->id,
        'amount' => 200,
        'position' => 1,
    ]));

    (new RecalculateRecipeNutrition($recipe->id))->handle(new NutritionCalculator);

    assertRecipeNotIndexed($engine, $recipe->id);
});

it('re-indexes the recipe when a recipe ingredient is added', function () {
    $engine = spyScoutEngine();

    $ingredient = nutritiousIngredient();
    $recipe = Recipe::factory()->published()->create(['servings' => 1]);

    // QUEUE_CONNECTION=sync: the observer dispatches the recompute job inline,
    // which now ends with searchable().
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $this->gram->id,
        'amount' => 200,
        'position' => 1,
    ]);

    assertRecipeIndexed($engine, $recipe->id);
});

it('re-indexes affected recipes when an ingredient is renamed, without recomputing nutrition', function () {
    $engine = spyScoutEngine();

    $ingredient = nutritiousIngredient();
    $recipe = Recipe::factory()->published()->create(['servings' => 1]);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $this->gram->id,
        'amount' => 200,
        'position' => 1,
    ]);

    Queue::fake();
    $ingredient->update(['name' => 'Renamed Ingredient']);

    Queue::assertNotPushed(RecalculateRecipeNutrition::class);
    assertRecipeIndexed($engine, $recipe->id);
});
