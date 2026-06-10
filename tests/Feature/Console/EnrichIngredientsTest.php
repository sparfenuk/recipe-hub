<?php

use App\Console\Commands\EnrichIngredients;
use App\Models\Allergen;
use App\Models\Ingredient;
use App\Models\IngredientCategory;

beforeEach(function () {
    EnrichIngredients::$rulesPathOverride = base_path('tests/fixtures/allergen-rules.json');
});

afterEach(function () {
    EnrichIngredients::$rulesPathOverride = null;
});

it('attaches allergens from keyword and category rules', function () {
    Allergen::create(['slug' => 'gluten', 'name' => 'Gluten']);
    Allergen::create(['slug' => 'nuts', 'name' => 'Nuts']);
    Allergen::create(['slug' => 'lactose', 'name' => 'Lactose']);
    $dairy = IngredientCategory::create(['slug' => 'dairy', 'name' => 'Dairy']);

    $flour = Ingredient::factory()->create(['name' => 'Wheat Flour']);
    $almond = Ingredient::factory()->create(['name' => 'Almond Butter']);
    $milk = Ingredient::factory()->create(['name' => 'Whole Milk', 'category_id' => $dairy->id]);

    $this->artisan('ingredients:enrich')->assertSuccessful();

    expect($flour->allergens()->pluck('slug')->all())->toContain('gluten')
        ->and($almond->allergens()->pluck('slug')->all())->toContain('nuts')
        ->and($milk->allergens()->pluck('slug')->all())->toContain('lactose');
});

it('dry-run reports matches but writes nothing', function () {
    Allergen::create(['slug' => 'gluten', 'name' => 'Gluten']);
    $flour = Ingredient::factory()->create(['name' => 'Wheat Flour']);

    $this->artisan('ingredients:enrich', ['--dry-run' => true])->assertSuccessful();

    expect($flour->allergens()->count())->toBe(0);
});

it('is idempotent — second run does not duplicate allergens', function () {
    Allergen::create(['slug' => 'gluten', 'name' => 'Gluten']);
    $flour = Ingredient::factory()->create(['name' => 'Wheat Flour']);

    $this->artisan('ingredients:enrich')->assertSuccessful();
    $this->artisan('ingredients:enrich')->assertSuccessful();

    expect($flour->allergens()->count())->toBe(1);
});

it('fails loudly on malformed rules JSON', function () {
    EnrichIngredients::$rulesPathOverride = base_path('tests/fixtures/broken-rules.json');

    $this->artisan('ingredients:enrich')->run();
})->throws(JsonException::class);
