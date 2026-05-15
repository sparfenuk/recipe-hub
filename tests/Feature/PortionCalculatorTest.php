<?php

use App\Livewire\PortionCalculator;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->author = User::factory()->create();
    $this->unit = Unit::create(['code' => 'g', 'name' => 'gram', 'type' => 'mass', 'to_base_factor' => 1]);
});

test('calculator renders with default servings', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->assertSet('originalServings', 4)
        ->assertSet('targetServings', 4)
        ->assertSee(__('calculator.title'));
});

test('calculator shows scaled ingredient amounts when servings doubled', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 2,
    ]);

    $ingredient = Ingredient::factory()->create(['name' => 'Flour']);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $this->unit->id,
        'amount' => 200,
        'position' => 1,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->assertSee('200')
        ->assertSee('Flour')
        ->set('targetServings', 4)
        ->assertSee('400')
        ->assertSee('Flour');
});

test('calculator shows scaled nutrition totals', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 2,
    ]);

    $recipe->updateQuietly([
        'total_kcal' => 800,
        'total_protein_g' => 40,
        'total_fat_g' => 30,
        'total_carbs_g' => 100,
        'total_fiber_g' => 10,
        'kcal_per_serving' => 400,
        'protein_per_serving_g' => 20,
        'fat_per_serving_g' => 15,
        'carbs_per_serving_g' => 50,
        'fiber_per_serving_g' => 5,
        'nutrition_cached_at' => now(),
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->set('targetServings', 4)
        ->assertSee('1,600')
        ->assertSee('80.0')
        ->assertSee('60.0')
        ->assertSee('200.0')
        ->assertSee('20.0');
});

test('per-serving nutrition stays constant when scaling by servings', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $recipe->updateQuietly([
        'total_kcal' => 2000,
        'kcal_per_serving' => 500,
        'protein_per_serving_g' => 25,
        'fat_per_serving_g' => 20,
        'carbs_per_serving_g' => 60,
        'fiber_per_serving_g' => 8,
        'nutrition_cached_at' => now(),
    ]);

    $component = Livewire::test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->set('targetServings', 8);

    $nutrition = $component->get('scaledNutrition');
    expect($nutrition['kcal_per_serving'])->toBe(500.0)
        ->and($nutrition['protein_per_serving_g'])->toBe(25.0)
        ->and($nutrition['fat_per_serving_g'])->toBe(20.0)
        ->and($nutrition['carbs_per_serving_g'])->toBe(60.0)
        ->and($nutrition['fiber_per_serving_g'])->toBe(8.0);
});

test('increment and decrement buttons work', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->assertSet('targetServings', 4)
        ->call('increment')
        ->assertSet('targetServings', 5)
        ->call('decrement')
        ->call('decrement')
        ->assertSet('targetServings', 3);
});

test('decrement does not go below 1', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 1,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->call('decrement')
        ->assertSet('targetServings', 1);
});

test('increment does not exceed 100', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 100,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->call('increment')
        ->assertSet('targetServings', 100);
});

test('reset restores original servings', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->set('targetServings', 10)
        ->call('resetServings')
        ->assertSet('targetServings', 4);
});

test('calculator shows reset link only when scaled in servings mode', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->assertDontSee(__('calculator.reset', ['servings' => 4]))
        ->set('targetServings', 6)
        ->assertSee(__('calculator.reset', ['servings' => 4]));
});

test('calculator handles multiple ingredients with grouping', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 2,
    ]);

    $flour = Ingredient::factory()->create(['name' => 'Flour']);
    $butter = Ingredient::factory()->create(['name' => 'Butter']);

    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $flour->id,
        'unit_id' => $this->unit->id,
        'amount' => 300,
        'position' => 1,
        'group_label' => 'Dough',
    ]);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $butter->id,
        'unit_id' => $this->unit->id,
        'amount' => 100,
        'position' => 2,
        'group_label' => 'Dough',
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->set('targetServings', 6)
        ->assertSee('900')
        ->assertSee('300')
        ->assertSee('Dough');
});

test('calculator shows optional ingredients', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 2,
    ]);

    $parsley = Ingredient::factory()->create(['name' => 'Parsley']);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $parsley->id,
        'unit_id' => $this->unit->id,
        'amount' => 10,
        'position' => 1,
        'is_optional' => true,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->set('targetServings', 4)
        ->assertSee('20')
        ->assertSee('Parsley')
        ->assertSee(__('recipes.optional'));
});

test('scale factor is 1 when target equals original', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $component = Livewire::test(PortionCalculator::class, ['recipe' => $recipe]);
    expect($component->get('scaleFactor'))->toBe(1.0);
});

test('scale factor handles null target gracefully', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $component = Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->set('targetServings', null);

    expect($component->get('scaleFactor'))->toBe(1.0);
});

test('calculator is embedded on recipe detail page', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'calc-embed-test',
        'servings' => 4,
    ]);

    $this->get(route('recipes.show', 'calc-embed-test'))
        ->assertOk()
        ->assertSeeLivewire(PortionCalculator::class);
});

test('total nutrition label changes when scaled', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $recipe->updateQuietly([
        'total_kcal' => 1000,
        'nutrition_cached_at' => now(),
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->assertSee(__('recipes.nutrition_total'))
        ->set('targetServings', 8)
        ->assertSee(__('calculator.total_for_servings', ['servings' => 8]));
});

test('fractional scaling produces correct amounts', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $ingredient = Ingredient::factory()->create(['name' => 'Sugar']);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $this->unit->id,
        'amount' => 100,
        'position' => 1,
    ]);

    $component = Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->set('targetServings', 3);

    $ingredients = collect($component->get('scaledIngredients'));
    expect($ingredients[0]['amount'])->toBe(75.0);
});

// --- Calorie mode tests ---

test('mode tabs are displayed', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->assertSee(__('calculator.mode_servings'))
        ->assertSee(__('calculator.mode_kcal'))
        ->assertSee(__('calculator.mode_daily_pct'));
});

test('switching to kcal mode shows calorie input', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->call('setMode', 'kcal')
        ->assertSet('mode', 'kcal')
        ->assertSee(__('calculator.target_kcal'));
});

test('kcal mode scales ingredients by calorie ratio', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $ingredient = Ingredient::factory()->create(['name' => 'Rice']);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $this->unit->id,
        'amount' => 400,
        'position' => 1,
    ]);

    $recipe->updateQuietly([
        'total_kcal' => 1000,
        'nutrition_cached_at' => now(),
    ]);

    $component = Livewire::test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->call('setMode', 'kcal')
        ->set('targetKcal', 500);

    $ingredients = collect($component->get('scaledIngredients'));
    expect($ingredients[0]['amount'])->toBe(200.0);
});

test('kcal mode scale factor is target / total', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 2,
    ]);

    $recipe->updateQuietly([
        'total_kcal' => 800,
        'nutrition_cached_at' => now(),
    ]);

    $component = Livewire::test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->call('setMode', 'kcal')
        ->set('targetKcal', 1200);

    expect($component->get('scaleFactor'))->toBe(1.5);
});

test('kcal mode scales nutrition totals correctly', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 2,
    ]);

    $recipe->updateQuietly([
        'total_kcal' => 1000,
        'total_protein_g' => 50,
        'total_fat_g' => 40,
        'total_carbs_g' => 120,
        'total_fiber_g' => 10,
        'kcal_per_serving' => 500,
        'protein_per_serving_g' => 25,
        'fat_per_serving_g' => 20,
        'carbs_per_serving_g' => 60,
        'fiber_per_serving_g' => 5,
        'nutrition_cached_at' => now(),
    ]);

    $component = Livewire::test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->call('setMode', 'kcal')
        ->set('targetKcal', 500);

    $nutrition = $component->get('scaledNutrition');
    expect($nutrition['kcal'])->toBe(500.0)
        ->and($nutrition['protein_g'])->toBe(25.0)
        ->and($nutrition['fat_g'])->toBe(20.0)
        ->and($nutrition['carbs_g'])->toBe(60.0)
        ->and($nutrition['fiber_g'])->toBe(5.0);
});

test('kcal mode updates per-serving values', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $recipe->updateQuietly([
        'total_kcal' => 2000,
        'total_protein_g' => 100,
        'kcal_per_serving' => 500,
        'protein_per_serving_g' => 25,
        'nutrition_cached_at' => now(),
    ]);

    $component = Livewire::test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->call('setMode', 'kcal')
        ->set('targetKcal', 1000);

    $nutrition = $component->get('scaledNutrition');
    expect($nutrition['kcal_per_serving'])->toBe(250.0)
        ->and($nutrition['protein_per_serving_g'])->toBe(12.5);
});

test('kcal mode returns factor 1 when no target set', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $recipe->updateQuietly(['total_kcal' => 1000, 'nutrition_cached_at' => now()]);

    $component = Livewire::test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->call('setMode', 'kcal');

    expect($component->get('scaleFactor'))->toBe(1.0);
});

test('kcal mode returns factor 1 when recipe has zero kcal', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $recipe->updateQuietly(['total_kcal' => 0, 'nutrition_cached_at' => now()]);

    $component = Livewire::test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->call('setMode', 'kcal')
        ->set('targetKcal', 500);

    expect($component->get('scaleFactor'))->toBe(1.0);
});

test('setMode rejects invalid mode', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->call('setMode', 'invalid')
        ->assertSet('mode', 'servings');
});

test('resetCalculator restores all inputs', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $recipe->updateQuietly(['total_kcal' => 1000, 'nutrition_cached_at' => now()]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->set('targetServings', 10)
        ->call('setMode', 'kcal')
        ->set('targetKcal', 500)
        ->call('resetCalculator')
        ->assertSet('targetServings', 4)
        ->assertSet('targetKcal', null)
        ->assertSet('targetDailyPct', null);
});

test('daily_pct shows prompt when user has no daily target', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->call('setMode', 'daily_pct')
        ->assertSee(__('calculator.daily_pct_no_target'))
        ->assertSee(__('calculator.set_daily_target'));
});

test('kcal mode shows original recipe kcal hint', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $recipe->updateQuietly(['total_kcal' => 1500, 'nutrition_cached_at' => now()]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->call('setMode', 'kcal')
        ->assertSee(__('calculator.original_kcal', ['kcal' => '1,500']));
});

test('total label changes to scaled total in kcal mode', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $recipe->updateQuietly(['total_kcal' => 1000, 'nutrition_cached_at' => now()]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->call('setMode', 'kcal')
        ->set('targetKcal', 500)
        ->assertSee(__('calculator.total_scaled'));
});

// --- Daily % mode tests ---

test('daily_pct mode shows input when user has daily target', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->profile()->create(['daily_kcal_target' => 2000]);

    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    Livewire::actingAs($user)
        ->test(PortionCalculator::class, ['recipe' => $recipe])
        ->call('setMode', 'daily_pct')
        ->assertSee(__('calculator.target_daily_pct'))
        ->assertSee(__('calculator.daily_target_info', ['kcal' => '2,000']));
});

test('daily_pct mode scales by percentage of daily target', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->profile()->create(['daily_kcal_target' => 2000]);

    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $recipe->updateQuietly([
        'total_kcal' => 1000,
        'total_protein_g' => 50,
        'nutrition_cached_at' => now(),
    ]);

    $component = Livewire::actingAs($user)
        ->test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->call('setMode', 'daily_pct')
        ->set('targetDailyPct', 30);

    $nutrition = $component->get('scaledNutrition');
    expect($nutrition['kcal'])->toBe(600.0)
        ->and($nutrition['protein_g'])->toBe(30.0);
});

test('daily_pct scale factor is (daily_target * pct / 100) / total_kcal', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->profile()->create(['daily_kcal_target' => 2000]);

    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $recipe->updateQuietly(['total_kcal' => 500, 'nutrition_cached_at' => now()]);

    $component = Livewire::actingAs($user)
        ->test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->call('setMode', 'daily_pct')
        ->set('targetDailyPct', 25);

    expect($component->get('scaleFactor'))->toBe(1.0);
});

test('daily_pct mode scales ingredients', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->profile()->create(['daily_kcal_target' => 2000]);

    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $ingredient = Ingredient::factory()->create(['name' => 'Pasta']);
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $this->unit->id,
        'amount' => 200,
        'position' => 1,
    ]);

    $recipe->updateQuietly(['total_kcal' => 1000, 'nutrition_cached_at' => now()]);

    $component = Livewire::actingAs($user)
        ->test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->call('setMode', 'daily_pct')
        ->set('targetDailyPct', 50);

    $ingredients = collect($component->get('scaledIngredients'));
    expect($ingredients[0]['amount'])->toBe(200.0);
});

test('daily_pct returns factor 1 when pct below minimum', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->profile()->create(['daily_kcal_target' => 2000]);

    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $recipe->updateQuietly(['total_kcal' => 1000, 'nutrition_cached_at' => now()]);

    $component = Livewire::actingAs($user)
        ->test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->call('setMode', 'daily_pct')
        ->set('targetDailyPct', 3);

    expect($component->get('scaleFactor'))->toBe(1.0);
});

test('daily_pct returns factor 1 when no daily target set', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $recipe->updateQuietly(['total_kcal' => 1000, 'nutrition_cached_at' => now()]);

    $component = Livewire::actingAs($user)
        ->test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->call('setMode', 'daily_pct')
        ->set('targetDailyPct', 30);

    expect($component->get('scaleFactor'))->toBe(1.0);
});

test('daily_pct returns factor 1 for guest user', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $recipe->updateQuietly(['total_kcal' => 1000, 'nutrition_cached_at' => now()]);

    $component = Livewire::test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->call('setMode', 'daily_pct')
        ->set('targetDailyPct', 30);

    expect($component->get('scaleFactor'))->toBe(1.0);
});

test('daily_pct total label shows percentage', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->profile()->create(['daily_kcal_target' => 2000]);

    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $recipe->updateQuietly(['total_kcal' => 1000, 'nutrition_cached_at' => now()]);

    Livewire::actingAs($user)
        ->test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->call('setMode', 'daily_pct')
        ->set('targetDailyPct', 40)
        ->assertSee(__('calculator.total_daily_pct', ['pct' => 40]));
});

test('daily_pct per-serving values are recalculated', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->profile()->create(['daily_kcal_target' => 2000]);

    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $recipe->updateQuietly([
        'total_kcal' => 1000,
        'kcal_per_serving' => 250,
        'nutrition_cached_at' => now(),
    ]);

    $component = Livewire::actingAs($user)
        ->test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->call('setMode', 'daily_pct')
        ->set('targetDailyPct', 50);

    $nutrition = $component->get('scaledNutrition');
    expect($nutrition['kcal_per_serving'])->toBe(250.0);
});

// --- Reference (cookbook PDF) nutrition tests ---

test('ref_* per-serving values override computed cache in display', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 2,
    ]);

    $recipe->updateQuietly([
        // Ingredient-computed cache (USDA-based).
        'total_kcal' => 1000,
        'total_protein_g' => 60,
        'total_fat_g' => 50,
        'total_carbs_g' => 80,
        'kcal_per_serving' => 500,
        'protein_per_serving_g' => 30,
        'fat_per_serving_g' => 25,
        'carbs_per_serving_g' => 40,
        // Cookbook reference (PDF). Different on purpose to prove it wins.
        'ref_kcal_per_serving' => 400,
        'ref_protein_per_serving_g' => 25,
        'ref_fat_per_serving_g' => 18,
        'ref_carbs_per_serving_g' => 45,
        'nutrition_cached_at' => now(),
    ]);

    $component = Livewire::test(PortionCalculator::class, ['recipe' => $recipe->fresh()]);
    $nutrition = $component->get('scaledNutrition');

    expect($nutrition['kcal_per_serving'])->toBe(400.0)
        ->and($nutrition['protein_per_serving_g'])->toBe(25.0)
        ->and($nutrition['fat_per_serving_g'])->toBe(18.0)
        ->and($nutrition['carbs_per_serving_g'])->toBe(45.0)
        // Totals derive from ref × servings.
        ->and($nutrition['kcal'])->toBe(800.0)
        ->and($nutrition['protein_g'])->toBe(50.0);
});

test('kcal mode scales relative to ref total when ref is set', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 2,
    ]);

    $recipe->updateQuietly([
        'total_kcal' => 1000,            // computed (would give a different factor)
        'kcal_per_serving' => 500,
        'ref_kcal_per_serving' => 400,   // displayed → ref_total = 800
        'nutrition_cached_at' => now(),
    ]);

    $component = Livewire::test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->call('setMode', 'kcal')
        ->set('targetKcal', 400);

    // Asking for 400 kcal out of 800 ref-displayed total → 0.5 factor.
    expect($component->get('scaleFactor'))->toBe(0.5);

    $nutrition = $component->get('scaledNutrition');
    expect($nutrition['kcal'])->toBe(400.0)
        ->and($nutrition['kcal_per_serving'])->toBe(200.0);
});

test('display falls back to computed when no ref values seeded', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 1,
    ]);

    $recipe->updateQuietly([
        'total_kcal' => 300,
        'kcal_per_serving' => 300,
        'protein_per_serving_g' => 15,
        'ref_kcal_per_serving' => null,
        'ref_protein_per_serving_g' => null,
        'nutrition_cached_at' => now(),
    ]);

    expect((float) $recipe->fresh()->display_kcal_per_serving)->toBe(300.0)
        ->and((float) $recipe->fresh()->display_protein_per_serving_g)->toBe(15.0);
});
