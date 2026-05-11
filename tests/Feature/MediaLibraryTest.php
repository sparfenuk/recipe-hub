<?php

use App\Filament\Resources\IngredientResource\Pages\EditIngredient;
use App\Models\Ingredient;
use App\Models\User;
use Database\Seeders\IngredientCategorySeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(RoleSeeder::class);
    $this->seed(UnitSeeder::class);
    $this->seed(IngredientCategorySeeder::class);
});

it('ingredient model has photo media collection', function () {
    $ingredient = Ingredient::factory()->create();
    $ingredient->addMedia(UploadedFile::fake()->image('photo.jpg', 400, 400))
        ->toMediaCollection('photo');

    expect($ingredient->getFirstMedia('photo'))->not->toBeNull()
        ->and($ingredient->getFirstMediaUrl('photo'))->not->toBeEmpty();
});

it('ingredient photo collection is single-file', function () {
    $ingredient = Ingredient::factory()->create();

    $ingredient->addMedia(UploadedFile::fake()->image('first.jpg', 400, 400))
        ->toMediaCollection('photo');
    $ingredient->addMedia(UploadedFile::fake()->image('second.jpg', 400, 400))
        ->toMediaCollection('photo');

    expect($ingredient->getMedia('photo'))->toHaveCount(1)
        ->and($ingredient->getFirstMedia('photo')->file_name)->toBe('second.jpg');
});

it('ingredient registers thumb, card, and full conversions', function () {
    $ingredient = Ingredient::factory()->create();
    $ingredient->addMedia(UploadedFile::fake()->image('photo.jpg', 2000, 1500))
        ->toMediaCollection('photo');

    $media = $ingredient->getFirstMedia('photo');

    expect($media->hasGeneratedConversion('thumb'))->toBeTrue()
        ->and($media->getUrl('thumb'))->not->toBeEmpty()
        ->and($media->getUrl('card'))->not->toBeEmpty()
        ->and($media->getUrl('full'))->not->toBeEmpty();
});

it('user model has avatar media collection', function () {
    $user = User::factory()->create();
    $user->addMedia(UploadedFile::fake()->image('avatar.jpg', 200, 200))
        ->toMediaCollection('avatar');

    expect($user->getFirstMedia('avatar'))->not->toBeNull()
        ->and($user->getFirstMediaUrl('avatar'))->not->toBeEmpty();
});

it('user avatar collection is single-file', function () {
    $user = User::factory()->create();

    $user->addMedia(UploadedFile::fake()->image('first.jpg', 200, 200))
        ->toMediaCollection('avatar');
    $user->addMedia(UploadedFile::fake()->image('second.jpg', 200, 200))
        ->toMediaCollection('avatar');

    expect($user->getMedia('avatar'))->toHaveCount(1)
        ->and($user->getFirstMedia('avatar')->file_name)->toBe('second.jpg');
});

it('user avatar thumb conversion is generated immediately', function () {
    $user = User::factory()->create();
    $user->addMedia(UploadedFile::fake()->image('avatar.jpg', 500, 500))
        ->toMediaCollection('avatar');

    $media = $user->getFirstMedia('avatar');

    expect($media->hasGeneratedConversion('thumb'))->toBeTrue();
});

it('media is deleted when ingredient is deleted', function () {
    $ingredient = Ingredient::factory()->create();
    $ingredient->addMedia(UploadedFile::fake()->image('photo.jpg', 400, 400))
        ->toMediaCollection('photo');

    $mediaPath = $ingredient->getFirstMedia('photo')->getPath();
    expect(file_exists($mediaPath))->toBeTrue();

    $ingredient->delete();

    expect(file_exists($mediaPath))->toBeFalse();
});

it('admin can upload ingredient photo via filament', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $ingredient = Ingredient::factory()->create();

    Livewire::actingAs($admin)
        ->test(EditIngredient::class, [
            'record' => $ingredient->getRouteKey(),
        ])
        ->fillForm([
            'photo' => [UploadedFile::fake()->image('ingredient.jpg', 600, 400)],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $ingredient->refresh();
    expect($ingredient->getFirstMedia('photo'))->not->toBeNull();
});

it('media-library queue is configured as image-processing', function () {
    expect(config('media-library.queue_name'))->toBe('image-processing');
});
