<?php

use App\Livewire\Cabinet\HealthForm;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->user = User::factory()->create();
    $this->user->assignRole('user');
    $this->user->profile()->create(['user_id' => $this->user->id]);
});

test('macro targets default to 30/30/40', function () {
    Livewire::actingAs($this->user)
        ->test(HealthForm::class)
        ->assertSet('p_pct', 30)
        ->assertSet('f_pct', 30)
        ->assertSet('c_pct', 40);
});

test('macro targets load from profile', function () {
    $this->user->profile->update([
        'p_pct' => 40,
        'f_pct' => 25,
        'c_pct' => 35,
    ]);

    Livewire::actingAs($this->user)
        ->test(HealthForm::class)
        ->assertSet('p_pct', 40)
        ->assertSet('f_pct', 25)
        ->assertSet('c_pct', 35);
});

test('macro targets save when sum is 100', function () {
    Livewire::actingAs($this->user)
        ->test(HealthForm::class)
        ->set('p_pct', 40)
        ->set('f_pct', 30)
        ->set('c_pct', 30)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true);

    $profile = $this->user->profile->fresh();
    expect($profile->p_pct)->toBe(40)
        ->and($profile->f_pct)->toBe(30)
        ->and($profile->c_pct)->toBe(30);
});

test('macro targets fail validation when sum is not 100', function () {
    Livewire::actingAs($this->user)
        ->test(HealthForm::class)
        ->set('p_pct', 40)
        ->set('f_pct', 40)
        ->set('c_pct', 40)
        ->call('save')
        ->assertHasErrors(['p_pct'])
        ->assertSet('saved', false);
});

test('macro sum helper returns correct total', function () {
    $component = Livewire::actingAs($this->user)
        ->test(HealthForm::class)
        ->set('p_pct', 25)
        ->set('f_pct', 35)
        ->set('c_pct', 40);

    expect($component->get('p_pct') + $component->get('f_pct') + $component->get('c_pct'))->toBe(100);
});

test('macro targets reject negative values', function () {
    Livewire::actingAs($this->user)
        ->test(HealthForm::class)
        ->set('p_pct', -10)
        ->set('f_pct', 60)
        ->set('c_pct', 50)
        ->call('save')
        ->assertHasErrors(['p_pct']);
});

test('macro targets reject values over 100', function () {
    Livewire::actingAs($this->user)
        ->test(HealthForm::class)
        ->set('p_pct', 110)
        ->set('f_pct', 0)
        ->set('c_pct', 0)
        ->call('save')
        ->assertHasErrors(['p_pct']);
});
