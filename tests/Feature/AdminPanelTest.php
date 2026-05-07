<?php

use App\Filament\Widgets\WelcomeWidget;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);
});

test('admin login page is accessible', function () {
    $this->get('/admin/login')->assertOk();
});

test('admin dashboard redirects guests to login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('non-admin user cannot access the admin panel', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

test('admin user can access the admin dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk();
});

test('admin panel forces English locale regardless of cookie', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->withCookie('locale', 'uk')
        ->get('/admin')
        ->assertOk();

    expect(app()->getLocale())->toBe('en');
});

test('admin panel forces English locale regardless of Accept-Language header', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->withHeader('Accept-Language', 'uk')
        ->get('/admin')
        ->assertOk();

    expect(app()->getLocale())->toBe('en');
});

test('welcome widget renders correctly', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(WelcomeWidget::class)
        ->assertSee('Welcome to Recipe Hub Admin')
        ->assertSee('Manage recipes, ingredients, and site content');
});
