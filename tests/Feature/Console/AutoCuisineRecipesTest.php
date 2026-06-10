<?php

use App\Console\Commands\AutoCuisineRecipes;
use App\Models\Cuisine;
use App\Models\Recipe;

beforeEach(function () {
    AutoCuisineRecipes::$rulesPathOverride = base_path('tests/fixtures/cuisine-rules.json');
});

afterEach(function () {
    AutoCuisineRecipes::$rulesPathOverride = null;
});

it('assigns cuisine by an English-title keyword', function () {
    $italian = Cuisine::create(['slug' => 'italian', 'name' => 'Italian']);
    $thai = Cuisine::create(['slug' => 'thai', 'name' => 'Thai']);

    $risotto = Recipe::factory()->published()->create(['title' => 'Creamy Risotto', 'cuisine_id' => null]);
    $padThai = Recipe::factory()->published()->create(['title' => 'Pad Thai Noodles', 'cuisine_id' => null]);
    $plain = Recipe::factory()->published()->create(['title' => 'Buttered Toast', 'cuisine_id' => null]);

    $this->artisan('recipes:auto-cuisine')->assertSuccessful();

    expect($risotto->fresh()->cuisine_id)->toBe($italian->id)
        ->and($padThai->fresh()->cuisine_id)->toBe($thai->id)
        ->and($plain->fresh()->cuisine_id)->toBeNull();
});

it('does not overwrite an existing cuisine assignment', function () {
    Cuisine::create(['slug' => 'italian', 'name' => 'Italian']);
    $thai = Cuisine::create(['slug' => 'thai', 'name' => 'Thai']);
    $recipe = Recipe::factory()->published()->create(['title' => 'Creamy Risotto', 'cuisine_id' => $thai->id]);

    $this->artisan('recipes:auto-cuisine')->assertSuccessful();

    expect($recipe->fresh()->cuisine_id)->toBe($thai->id);
});

it('dry-run assigns nothing', function () {
    Cuisine::create(['slug' => 'italian', 'name' => 'Italian']);
    $risotto = Recipe::factory()->published()->create(['title' => 'Creamy Risotto', 'cuisine_id' => null]);

    $this->artisan('recipes:auto-cuisine', ['--dry-run' => true])->assertSuccessful();

    expect($risotto->fresh()->cuisine_id)->toBeNull();
});

it('fails loudly on malformed rules JSON', function () {
    AutoCuisineRecipes::$rulesPathOverride = base_path('tests/fixtures/broken-rules.json');

    $this->artisan('recipes:auto-cuisine')->run();
})->throws(JsonException::class);
