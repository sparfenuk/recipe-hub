<?php

use App\Livewire\Cabinet\ProfileForm;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('cabinet redirects guests to login', function () {
    $this->get('/cabinet')
        ->assertRedirect('/login');
});

test('cabinet dashboard renders for authenticated user', function () {
    $user = User::factory()->create();
    $user->profile()->create();

    $this->actingAs($user)
        ->get('/cabinet')
        ->assertOk()
        ->assertSee($user->name)
        ->assertSee($user->email);
});

test('profile page renders for authenticated user', function () {
    $user = User::factory()->create();
    $user->profile()->create();

    $this->actingAs($user)
        ->get('/cabinet/profile')
        ->assertOk();
});

test('user profile is auto-created on registration', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $user = User::where('email', 'test@example.com')->first();

    expect($user->profile)->not->toBeNull()
        ->and($user->profile->units_pref)->toBe('metric')
        ->and($user->profile->p_pct)->toBe(30)
        ->and($user->profile->f_pct)->toBe(30)
        ->and($user->profile->c_pct)->toBe(40);
});

test('profile form can update name', function () {
    $user = User::factory()->create(['name' => 'Old Name']);
    $user->profile()->create();

    Livewire::actingAs($user)
        ->test(ProfileForm::class)
        ->set('name', 'New Name')
        ->call('save')
        ->assertSet('saved', true);

    expect($user->fresh()->name)->toBe('New Name');
});

test('profile form validates name is required', function () {
    $user = User::factory()->create();
    $user->profile()->create();

    Livewire::actingAs($user)
        ->test(ProfileForm::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('profile form can update units preference', function () {
    $user = User::factory()->create();
    $user->profile()->create(['units_pref' => 'metric']);

    Livewire::actingAs($user)
        ->test(ProfileForm::class)
        ->set('units_pref', 'imperial')
        ->call('save')
        ->assertSet('saved', true);

    expect($user->profile->fresh()->units_pref)->toBe('imperial');
});

test('profile form can upload avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $user->profile()->create();

    Livewire::actingAs($user)
        ->test(ProfileForm::class)
        ->set('avatar', UploadedFile::fake()->image('avatar.jpg', 200, 200))
        ->call('save')
        ->assertSet('saved', true);

    $user->refresh();
    expect($user->getFirstMedia('avatar'))->not->toBeNull()
        ->and($user->getFirstMediaUrl('avatar'))->not->toBeEmpty();
});

test('profile form can remove avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $user->profile()->create();
    $user->addMedia(UploadedFile::fake()->image('old.jpg', 200, 200))
        ->toMediaCollection('avatar');

    expect($user->getFirstMedia('avatar'))->not->toBeNull();

    Livewire::actingAs($user)
        ->test(ProfileForm::class)
        ->call('removeAvatar')
        ->assertSet('currentAvatarUrl', null);

    $user->refresh();
    expect($user->getFirstMedia('avatar'))->toBeNull();
});

test('profile form rejects non-image file', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $user->profile()->create();

    Livewire::actingAs($user)
        ->test(ProfileForm::class)
        ->set('avatar', UploadedFile::fake()->create('document.txt', 100))
        ->call('save')
        ->assertHasErrors(['avatar']);
});

test('profile form rejects oversized image', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $user->profile()->create();

    Livewire::actingAs($user)
        ->test(ProfileForm::class)
        ->set('avatar', UploadedFile::fake()->image('huge.jpg')->size(3000))
        ->call('save')
        ->assertHasErrors(['avatar']);
});

test('user profile belongs to user', function () {
    $user = User::factory()->create();
    $profile = $user->profile()->create(['units_pref' => 'imperial']);

    expect($profile->user->id)->toBe($user->id)
        ->and($profile)->toBeInstanceOf(UserProfile::class);
});

test('user profile has default macro splits', function () {
    $user = User::factory()->create();
    $user->profile()->create();

    $profile = $user->profile()->first();

    expect($profile->p_pct)->toBe(30)
        ->and($profile->f_pct)->toBe(30)
        ->and($profile->c_pct)->toBe(40);
});
