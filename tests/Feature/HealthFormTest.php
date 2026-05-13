<?php

use App\Livewire\Cabinet\HealthForm;
use App\Models\User;
use App\Services\Nutrition\BmrCalculator;
use Carbon\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->user = User::factory()->create();
    $this->user->assignRole('user');
    $this->user->profile()->create(['user_id' => $this->user->id]);
});

test('bmr formula returns correct value for male', function () {
    $bmr = BmrCalculator::bmr('male', 80, 180, 30);

    // (10*80) + (6.25*180) - (5*30) + 5 = 800 + 1125 - 150 + 5 = 1780
    expect($bmr)->toBe(1780.0);
});

test('bmr formula returns correct value for female', function () {
    $bmr = BmrCalculator::bmr('female', 60, 165, 25);

    // (10*60) + (6.25*165) - (5*25) - 161 = 600 + 1031.25 - 125 - 161 = 1345.25
    expect($bmr)->toBe(1345.25);
});

test('tdee applies activity factor correctly', function () {
    // BMR male 80kg 180cm 30y = 1780
    $sedentary = BmrCalculator::tdee('male', 80, 180, 30, 'sedentary');
    $veryActive = BmrCalculator::tdee('male', 80, 180, 30, 'very_active');

    expect($sedentary)->toBe((int) round(1780 * 1.2))
        ->and($veryActive)->toBe((int) round(1780 * 1.725));
});

test('guest cannot access health form', function () {
    $this->get(route('cabinet.health'))
        ->assertRedirect(route('login'));
});

test('health form renders for authenticated user', function () {
    Livewire::actingAs($this->user)
        ->test(HealthForm::class)
        ->assertSuccessful()
        ->assertSee(__('cabinet.health_profile'));
});

test('health form loads existing profile data', function () {
    $this->user->profile->update([
        'sex' => 'male',
        'birth_date' => '1990-01-15',
        'height_cm' => 180,
        'weight_kg' => 80,
        'activity_level' => 'moderately_active',
        'daily_kcal_target' => 2500,
    ]);

    Livewire::actingAs($this->user)
        ->test(HealthForm::class)
        ->assertSet('sex', 'male')
        ->assertSet('birth_date', '1990-01-15')
        ->assertSet('height_cm', '180.0')
        ->assertSet('weight_kg', '80.0')
        ->assertSet('activity_level', 'moderately_active')
        ->assertSet('daily_kcal_target', 2500);
});

test('health form saves profile data', function () {
    Livewire::actingAs($this->user)
        ->test(HealthForm::class)
        ->set('sex', 'female')
        ->set('birth_date', '1995-06-20')
        ->set('height_cm', '165')
        ->set('weight_kg', '60')
        ->set('activity_level', 'lightly_active')
        ->set('daily_kcal_target', 1800)
        ->call('save');

    $profile = $this->user->profile->fresh();
    expect($profile->sex)->toBe('female')
        ->and($profile->birth_date->format('Y-m-d'))->toBe('1995-06-20')
        ->and((float) $profile->height_cm)->toBe(165.0)
        ->and((float) $profile->weight_kg)->toBe(60.0)
        ->and($profile->activity_level)->toBe('lightly_active')
        ->and($profile->daily_kcal_target)->toBe(1800);
});

test('health form computes suggested kcal when all fields set', function () {
    $age = Carbon::parse('1990-01-15')->age;
    $expected = BmrCalculator::tdee('male', 80, 180, $age, 'sedentary');

    Livewire::actingAs($this->user)
        ->test(HealthForm::class)
        ->set('sex', 'male')
        ->set('birth_date', '1990-01-15')
        ->set('height_cm', '180')
        ->set('weight_kg', '80')
        ->set('activity_level', 'sedentary')
        ->assertSet('suggested_kcal', $expected);
});

test('suggested kcal is null when fields are incomplete', function () {
    Livewire::actingAs($this->user)
        ->test(HealthForm::class)
        ->set('sex', 'male')
        ->set('height_cm', '180')
        ->assertSet('suggested_kcal', null);
});

test('use suggested button sets daily kcal target', function () {
    $component = Livewire::actingAs($this->user)
        ->test(HealthForm::class)
        ->set('sex', 'male')
        ->set('birth_date', '1990-01-15')
        ->set('height_cm', '180')
        ->set('weight_kg', '80')
        ->set('activity_level', 'sedentary');

    $suggested = $component->get('suggested_kcal');
    expect($suggested)->not->toBeNull();

    $component->call('useSuggested')
        ->assertSet('daily_kcal_target', $suggested);
});

test('health form validates input ranges', function () {
    Livewire::actingAs($this->user)
        ->test(HealthForm::class)
        ->set('height_cm', '10')
        ->set('weight_kg', '5')
        ->set('daily_kcal_target', 100)
        ->call('save')
        ->assertHasErrors(['height_cm', 'weight_kg', 'daily_kcal_target']);
});

test('health form rejects invalid sex value', function () {
    Livewire::actingAs($this->user)
        ->test(HealthForm::class)
        ->set('sex', 'invalid')
        ->call('save')
        ->assertHasErrors(['sex']);
});
