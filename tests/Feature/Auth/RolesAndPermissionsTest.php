<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('role seeder creates all four roles', function () {
    expect(Role::pluck('name')->sort()->values()->all())
        ->toBe(['admin', 'editor', 'guest', 'user']);
});

test('role seeder is idempotent', function () {
    $this->seed(RoleSeeder::class);

    expect(Role::count())->toBe(4);
});

test('registered user receives user role', function () {
    $this->post('/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $user = User::where('email', 'jane@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole('user'))->toBeTrue();
});

test('admin role grants admin.access permission', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($admin->hasPermissionTo('admin.access'))->toBeTrue();
});

test('user role does not grant admin.access permission', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    expect($user->hasPermissionTo('admin.access'))->toBeFalse();
});

test('editor role grants recipe and ingredient write permissions', function () {
    $editor = User::factory()->create();
    $editor->assignRole('editor');

    expect($editor->hasPermissionTo('recipe.create'))->toBeTrue()
        ->and($editor->hasPermissionTo('recipe.update'))->toBeTrue()
        ->and($editor->hasPermissionTo('ingredient.create'))->toBeTrue()
        ->and($editor->hasPermissionTo('media.upload'))->toBeTrue()
        ->and($editor->hasPermissionTo('recipe.delete'))->toBeFalse();
});

test('admin gate before grants all abilities to admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($admin->can('anything-at-all'))->toBeTrue();
});

test('non-admin cannot pass admin gate', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    expect($user->can('admin.access'))->toBeFalse();
});

test('admin can access filament admin panel', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();
});

test('regular user cannot access filament admin panel', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});
