<?php

use App\Livewire\PortionCalculator;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('registration is rate limited at 5 per minute', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->post('/register', [
            'name' => 'User '.$i,
            'email' => "user{$i}@example.com",
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]);
    }

    $response = $this->post('/register', [
        'name' => 'Extra User',
        'email' => 'extra@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertStatus(429);
});

test('password reset request is rate limited', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->post('/forgot-password', [
            'email' => "user{$i}@example.com",
        ]);
    }

    $response = $this->post('/forgot-password', [
        'email' => 'another@example.com',
    ]);

    $response->assertStatus(429);
});

test('login page is rate limited', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->get('/login');
    }

    $response = $this->get('/login');

    $response->assertStatus(429);
});

test('auth rate limiter allows up to 5 requests', function () {
    for ($i = 0; $i < 5; $i++) {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }
});

test('429 error page renders with localized content', function () {
    for ($i = 0; $i < 6; $i++) {
        $this->get('/login');
    }

    $response = $this->get('/login');

    $response->assertStatus(429);
    $response->assertSee('429');
    $response->assertSee('Too Many Requests');
});

test('429 error page renders in Ukrainian', function () {
    $this->withCookie('locale', 'uk');

    for ($i = 0; $i < 6; $i++) {
        $this->withCookie('locale', 'uk')->get('/login');
    }

    $response = $this->withCookie('locale', 'uk')->get('/login');

    $response->assertStatus(429);
});

test('calculator save is rate limited at 60 per minute', function () {
    $user = User::factory()->create();
    $unit = Unit::create(['code' => 'g', 'name' => 'gram', 'type' => 'mass', 'to_base_factor' => 1]);
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $user->id,
        'servings' => 4,
        'total_kcal' => 500,
    ]);
    $ingredient = Ingredient::factory()->create();
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $unit->id,
        'amount' => 100,
        'position' => 1,
    ]);

    $key = 'calculator:'.$user->id;
    RateLimiter::clear($key);

    for ($i = 0; $i < 60; $i++) {
        RateLimiter::hit($key, 60);
    }

    Livewire::actingAs($user)
        ->test(PortionCalculator::class, ['recipe' => $recipe])
        ->set('targetServings', 8)
        ->call('saveCalculation')
        ->assertHasErrors('save');
});

test('calculator save succeeds within rate limit', function () {
    $user = User::factory()->create();
    $unit = Unit::create(['code' => 'g', 'name' => 'gram', 'type' => 'mass', 'to_base_factor' => 1]);
    $recipe = Recipe::factory()->published()->create([
        'author_id' => $user->id,
        'servings' => 4,
        'total_kcal' => 500,
    ]);
    $ingredient = Ingredient::factory()->create();
    RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'unit_id' => $unit->id,
        'amount' => 100,
        'position' => 1,
    ]);

    $key = 'calculator:'.$user->id;
    RateLimiter::clear($key);

    Livewire::actingAs($user)
        ->test(PortionCalculator::class, ['recipe' => $recipe])
        ->set('targetServings', 8)
        ->call('saveCalculation')
        ->assertHasNoErrors('save')
        ->assertSet('saved', true);
});

test('auth rate limiter is defined', function () {
    expect(RateLimiter::limiter('auth'))->not->toBeNull();
});

test('api rate limiter is defined', function () {
    expect(RateLimiter::limiter('api'))->not->toBeNull();
});

test('api rate limiter allows 60 per minute for authenticated users', function () {
    $user = User::factory()->create();
    $request = Request::create('/api/test');
    $request->setUserResolver(fn () => $user);

    $limit = call_user_func(RateLimiter::limiter('api'), $request);

    expect($limit->maxAttempts)->toBe(60);
});

test('api rate limiter allows 30 per minute for guests', function () {
    $request = Request::create('/api/test');

    $limit = call_user_func(RateLimiter::limiter('api'), $request);

    expect($limit->maxAttempts)->toBe(30);
});

test('translation keys exist for rate limiting strings', function () {
    $keys = [
        'Too Many Requests',
        'You have made too many requests. Please wait a moment and try again.',
        'Go Back',
        'Too many requests. Please wait before saving again.',
    ];

    $en = json_decode(file_get_contents(lang_path('en.json')), true);
    $uk = json_decode(file_get_contents(lang_path('uk.json')), true);

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key);
        expect($uk)->toHaveKey($key);
    }
});
