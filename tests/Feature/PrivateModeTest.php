<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// --- APP_PRIVATE=false (default): public routes stay public ---

test('private mode off — public routes are not redirected', function () {
    config(['app.private' => false]);

    $this->get('/')->assertOk();
    $this->get('/recipes')->assertOk();
    $this->get('/book')->assertOk();
    $this->get('/author')->assertOk();
});

// --- APP_PRIVATE=true: anonymous users are redirected to login ---

test('private mode on — landing redirects guests to login', function () {
    config(['app.private' => true]);

    $this->get('/')->assertRedirect(route('login'));
});

test('private mode on — recipes index redirects guests to login', function () {
    config(['app.private' => true]);

    $this->get('/recipes')->assertRedirect(route('login'));
});

test('private mode on — book and author pages redirect guests', function () {
    config(['app.private' => true]);

    $this->get('/book')->assertRedirect(route('login'));
    $this->get('/author')->assertRedirect(route('login'));
});

test('private mode on — recipe PDF redirects guests', function () {
    config(['app.private' => true]);

    $this->get('/recipes/anything/pdf')->assertRedirect(route('login'));
});

// --- APP_PRIVATE=true: authenticated users pass through ---

test('private mode on — authenticated user can reach landing', function () {
    config(['app.private' => true]);

    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk();
});

test('private mode on — authenticated user can reach recipes index', function () {
    config(['app.private' => true]);

    $this->actingAs(User::factory()->create())
        ->get('/recipes')
        ->assertOk();
});

// --- APP_PRIVATE=true: allowlist works for guests ---

test('private mode on — login page is reachable by guests', function () {
    config(['app.private' => true]);

    $this->get('/login')->assertOk();
});

test('private mode on — forgot-password page is reachable by guests', function () {
    config(['app.private' => true]);

    $this->get('/forgot-password')->assertOk();
});

test('private mode on — health check is reachable by guests', function () {
    config(['app.private' => true]);

    $this->get('/up')->assertOk();
});

// --- Registration is gated by private mode (route still exists for tests) ---

test('private mode on — register page redirects guests to login', function () {
    config(['app.private' => true]);

    $this->get('/register')->assertRedirect(route('login'));
});

test('private mode off — register page is reachable (default)', function () {
    config(['app.private' => false]);

    $this->get('/register')->assertOk();
});
