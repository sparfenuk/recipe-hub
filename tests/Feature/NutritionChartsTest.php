<?php

use App\Livewire\PortionCalculator;
use App\Models\Recipe;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->author = User::factory()->create();

    $this->recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);
    $this->recipe->updateQuietly([
        'total_kcal' => 800,
        'total_protein_g' => 60,
        'total_fat_g' => 30,
        'total_carbs_g' => 80,
        'total_fiber_g' => 10,
        'kcal_per_serving' => 200,
        'protein_per_serving_g' => 15,
        'fat_per_serving_g' => 7.5,
        'carbs_per_serving_g' => 20,
        'fiber_per_serving_g' => 2.5,
        'nutrition_cached_at' => now(),
    ]);
});

test('chart section renders when recipe has nutrition data', function () {
    Livewire::test(PortionCalculator::class, ['recipe' => $this->recipe->fresh()])
        ->assertSee(__('calculator.chart_macro_split'));
});

test('chart section does not render when recipe has no nutrition', function () {
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'servings' => 4,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $recipe])
        ->assertDontSee(__('calculator.chart_macro_split'));
});

test('chart section does not render when total kcal is zero', function () {
    $this->recipe->updateQuietly([
        'total_kcal' => 0,
        'total_protein_g' => 0,
        'total_fat_g' => 0,
        'total_carbs_g' => 0,
        // RecalculateRecipeNutrition writes both totals and per-serving together; mirror that
        // so the display chain doesn't pick stale per-serving values when totals go to zero.
        'kcal_per_serving' => 0,
        'protein_per_serving_g' => 0,
        'fat_per_serving_g' => 0,
        'carbs_per_serving_g' => 0,
    ]);

    Livewire::test(PortionCalculator::class, ['recipe' => $this->recipe->fresh()])
        ->assertDontSee(__('calculator.chart_macro_split'));
});

test('donut chart container renders with nutritionCharts Alpine component', function () {
    Livewire::test(PortionCalculator::class, ['recipe' => $this->recipe->fresh()])
        ->assertSeeHtml('x-data="nutritionCharts(')
        ->assertSeeHtml('x-ref="donut"');
});

test('bar chart does not render for guest users', function () {
    Livewire::test(PortionCalculator::class, ['recipe' => $this->recipe->fresh()])
        ->assertDontSee(__('calculator.chart_vs_target'))
        ->assertDontSeeHtml('x-ref="bar"');
});

test('bar chart does not render for user without daily target', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->profile()->create();

    Livewire::actingAs($user)
        ->test(PortionCalculator::class, ['recipe' => $this->recipe->fresh()])
        ->assertDontSee(__('calculator.chart_vs_target'))
        ->assertDontSeeHtml('x-ref="bar"');
});

test('bar chart renders for user with daily target and macro targets', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->profile()->create([
        'daily_kcal_target' => 2000,
        'p_pct' => 30,
        'f_pct' => 30,
        'c_pct' => 40,
    ]);

    Livewire::actingAs($user)
        ->test(PortionCalculator::class, ['recipe' => $this->recipe->fresh()])
        ->assertSee(__('calculator.chart_vs_target'))
        ->assertSeeHtml('x-ref="bar"');
});

test('macroTargets returns null for guest', function () {
    $component = Livewire::test(PortionCalculator::class, ['recipe' => $this->recipe->fresh()]);
    expect($component->instance()->macroTargets())->toBeNull();
});

test('macroTargets returns null when user has no daily target', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->profile()->create();

    $component = Livewire::actingAs($user)
        ->test(PortionCalculator::class, ['recipe' => $this->recipe->fresh()]);
    expect($component->instance()->macroTargets())->toBeNull();
});

test('macroTargets computes correct gram values from percentage split', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->profile()->create([
        'daily_kcal_target' => 2000,
        'p_pct' => 30,
        'f_pct' => 30,
        'c_pct' => 40,
    ]);

    $component = Livewire::actingAs($user)
        ->test(PortionCalculator::class, ['recipe' => $this->recipe->fresh()]);

    $targets = $component->instance()->macroTargets();

    expect($targets)->toBeArray()
        ->and($targets['protein_g'])->toBe(150.0)
        ->and($targets['fat_g'])->toBe(66.7)
        ->and($targets['carbs_g'])->toBe(200.0);
});

test('scaled nutrition reflects doubled servings for chart data', function () {
    $component = Livewire::test(PortionCalculator::class, ['recipe' => $this->recipe->fresh()])
        ->set('targetServings', 8);

    $nutrition = $component->instance()->scaledNutrition();
    expect($nutrition['protein_g'])->toBe(120.0)
        ->and($nutrition['fat_g'])->toBe(60.0)
        ->and($nutrition['carbs_g'])->toBe(160.0);
});

test('scaled nutrition updates when servings change', function () {
    $component = Livewire::test(PortionCalculator::class, ['recipe' => $this->recipe->fresh()]);

    $nutrition = $component->instance()->scaledNutrition();
    expect($nutrition['protein_g'])->toBe(60.0);

    $component->set('targetServings', 8);
    $nutrition = $component->instance()->scaledNutrition();
    expect($nutrition['protein_g'])->toBe(120.0);
});

test('scaled nutrition updates when mode changes to kcal', function () {
    $component = Livewire::test(PortionCalculator::class, ['recipe' => $this->recipe->fresh()])
        ->call('setMode', 'kcal')
        ->set('targetKcal', 400);

    $nutrition = $component->instance()->scaledNutrition();
    expect($nutrition['protein_g'])->toBe(30.0)
        ->and($nutrition['fat_g'])->toBe(15.0)
        ->and($nutrition['carbs_g'])->toBe(40.0);
});
