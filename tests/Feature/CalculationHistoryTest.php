<?php

use App\Livewire\Cabinet\CalculationHistory;
use App\Livewire\PortionCalculator;
use App\Models\CalculatorSession;
use App\Models\Recipe;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->user = User::factory()->create();
    $this->user->assignRole('user');
    $this->user->profile()->create();

    $this->author = User::factory()->create();
    $this->unit = Unit::create(['code' => 'g', 'name' => 'gram', 'type' => 'mass', 'to_base_factor' => 1]);
});

test('save button appears for authenticated user with scaled calculation', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $recipe->updateQuietly(['total_kcal' => 1000, 'nutrition_cached_at' => now()]);

    Livewire::actingAs($this->user)
        ->test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->set('targetServings', 8)
        ->assertSee(__('calculator.save_calculation'));
});

test('save button does not appear for guests', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->set('targetServings', 8)
        ->assertDontSee(__('calculator.save_calculation'));
});

test('save button does not appear when not scaled', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    Livewire::actingAs($this->user)
        ->test(PortionCalculator::class, ['recipe' => $recipe])
        ->assertDontSee(__('calculator.save_calculation'));
});

test('save calculation creates a database record', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $recipe->updateQuietly([
        'total_kcal' => 1000,
        'total_protein_g' => 50,
        'total_fat_g' => 40,
        'total_carbs_g' => 120,
        'total_fiber_g' => 10,
        'kcal_per_serving' => 250,
        'nutrition_cached_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->set('targetServings', 8)
        ->call('saveCalculation')
        ->assertSet('saved', true);

    expect(CalculatorSession::count())->toBe(1);

    $session = CalculatorSession::first();
    expect($session->user_id)->toBe($this->user->id)
        ->and($session->recipe_id)->toBe($recipe->id)
        ->and($session->mode)->toBe('servings')
        ->and((float) $session->input_value)->toBe(8.0)
        ->and((float) $session->scale_factor)->toBe(2.0);
});

test('save calculation stores kcal mode correctly', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    $recipe->updateQuietly([
        'total_kcal' => 1000,
        'nutrition_cached_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->call('setMode', 'kcal')
        ->set('targetKcal', 500)
        ->call('saveCalculation');

    $session = CalculatorSession::first();
    expect($session->mode)->toBe('kcal')
        ->and((float) $session->input_value)->toBe(500.0)
        ->and((float) $session->scale_factor)->toBe(0.5);
});

test('save calculation stores totals as JSON', function () {
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

    Livewire::actingAs($this->user)
        ->test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->set('targetServings', 4)
        ->call('saveCalculation');

    $session = CalculatorSession::first();
    $totals = $session->totals;

    expect($totals)->toBeArray()
        ->and((float) $totals['kcal'])->toBe(1600.0)
        ->and((float) $totals['protein_g'])->toBe(80.0);
});

test('save does nothing for guest user', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->set('targetServings', 8)
        ->call('saveCalculation');

    expect(CalculatorSession::count())->toBe(0);
});

test('save does nothing when not scaled', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    Livewire::actingAs($this->user)
        ->test(PortionCalculator::class, ['recipe' => $recipe])
        ->call('saveCalculation');

    expect(CalculatorSession::count())->toBe(0);
});

// --- Calculation History page tests ---

test('calculation history page loads for authenticated user', function () {
    $this->actingAs($this->user)
        ->get(route('cabinet.calculations'))
        ->assertOk()
        ->assertSeeLivewire(CalculationHistory::class);
});

test('calculation history requires authentication', function () {
    $this->get(route('cabinet.calculations'))
        ->assertRedirect(route('login'));
});

test('calculation history shows saved sessions', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'title' => 'Chicken Salad',
    ]);

    CalculatorSession::create([
        'user_id' => $this->user->id,
        'recipe_id' => $recipe->id,
        'mode' => 'servings',
        'input_value' => 8,
        'scale_factor' => 2.0,
        'totals' => ['kcal' => 1600, 'protein_g' => 80, 'fat_g' => 60, 'carbs_g' => 200, 'fiber_g' => 20],
    ]);

    Livewire::actingAs($this->user)
        ->test(CalculationHistory::class)
        ->assertSee('Chicken Salad')
        ->assertSee('1,600')
        ->assertSee(__('calculator.mode_servings'));
});

test('calculation history shows empty state', function () {
    Livewire::actingAs($this->user)
        ->test(CalculationHistory::class)
        ->assertSee(__('cabinet.no_calculations'));
});

test('delete removes a calculation session', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
    ]);

    $session = CalculatorSession::create([
        'user_id' => $this->user->id,
        'recipe_id' => $recipe->id,
        'mode' => 'servings',
        'input_value' => 6,
        'scale_factor' => 1.5,
        'totals' => ['kcal' => 1200],
    ]);

    Livewire::actingAs($this->user)
        ->test(CalculationHistory::class)
        ->call('delete', $session->id);

    expect(CalculatorSession::count())->toBe(0);
});

test('user cannot delete another user calculation', function () {
    $otherUser = User::factory()->create();
    $otherUser->assignRole('user');

    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
    ]);

    $session = CalculatorSession::create([
        'user_id' => $otherUser->id,
        'recipe_id' => $recipe->id,
        'mode' => 'kcal',
        'input_value' => 500,
        'scale_factor' => 0.5,
        'totals' => ['kcal' => 500],
    ]);

    Livewire::actingAs($this->user)
        ->test(CalculationHistory::class)
        ->call('delete', $session->id);

    expect(CalculatorSession::count())->toBe(1);
});

test('dashboard shows calculations link', function () {
    $this->actingAs($this->user)
        ->get(route('cabinet'))
        ->assertSee(__('cabinet.calculations'))
        ->assertSee(route('cabinet.calculations'));
});

test('save then list shows the saved calculation', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
        'title' => 'Test Pasta',
    ]);

    $recipe->updateQuietly([
        'total_kcal' => 1000,
        'total_protein_g' => 50,
        'kcal_per_serving' => 250,
        'nutrition_cached_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(PortionCalculator::class, ['recipe' => $recipe->fresh()])
        ->set('targetServings', 8)
        ->call('saveCalculation');

    Livewire::actingAs($this->user)
        ->test(CalculationHistory::class)
        ->assertSee('Test Pasta')
        ->assertSee(__('calculator.mode_servings'));
});

test('cascade deletes sessions when recipe is deleted', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
    ]);

    CalculatorSession::create([
        'user_id' => $this->user->id,
        'recipe_id' => $recipe->id,
        'mode' => 'servings',
        'input_value' => 6,
        'scale_factor' => 1.5,
        'totals' => ['kcal' => 1200],
    ]);

    $recipe->forceDelete();
    expect(CalculatorSession::count())->toBe(0);
});

test('cascade deletes sessions when user is deleted', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
    ]);

    $tempUser = User::factory()->create();
    $tempUser->assignRole('user');

    CalculatorSession::create([
        'user_id' => $tempUser->id,
        'recipe_id' => $recipe->id,
        'mode' => 'kcal',
        'input_value' => 500,
        'scale_factor' => 0.5,
        'totals' => ['kcal' => 500],
    ]);

    $tempUser->forceDelete();
    expect(CalculatorSession::count())->toBe(0);
});
