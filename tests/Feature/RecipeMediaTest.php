<?php

use App\Filament\Resources\RecipeResource;
use App\Filament\Resources\RecipeResource\Pages\CreateRecipe;
use App\Filament\Resources\RecipeResource\Pages\EditRecipe;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('recipe hero collection is single file', function () {
    $recipe = Recipe::factory()->create(['author_id' => $this->admin->id]);

    $recipe->addMedia(UploadedFile::fake()->image('hero1.jpg'))
        ->toMediaCollection('hero');
    $recipe->addMedia(UploadedFile::fake()->image('hero2.jpg'))
        ->toMediaCollection('hero');

    expect($recipe->getMedia('hero'))->toHaveCount(1)
        ->and($recipe->getFirstMediaUrl('hero'))->toContain('hero2');
});

test('recipe gallery collection accepts multiple files', function () {
    $recipe = Recipe::factory()->create(['author_id' => $this->admin->id]);

    $recipe->addMedia(UploadedFile::fake()->image('photo1.jpg'))
        ->toMediaCollection('gallery');
    $recipe->addMedia(UploadedFile::fake()->image('photo2.jpg'))
        ->toMediaCollection('gallery');
    $recipe->addMedia(UploadedFile::fake()->image('photo3.jpg'))
        ->toMediaCollection('gallery');

    expect($recipe->getMedia('gallery'))->toHaveCount(3);
});

test('recipe hero generates all three conversions', function () {
    $recipe = Recipe::factory()->create(['author_id' => $this->admin->id]);

    $recipe->addMedia(UploadedFile::fake()->image('hero.jpg', 1920, 1080))
        ->toMediaCollection('hero');

    $media = $recipe->getFirstMedia('hero');

    expect($media->hasGeneratedConversion('thumb'))->toBeTrue()
        ->and($media->hasGeneratedConversion('card'))->toBeTrue()
        ->and($media->hasGeneratedConversion('full'))->toBeTrue();
});

test('filament recipe form has media section', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateRecipe::class)
        ->assertFormFieldExists('hero')
        ->assertFormFieldExists('gallery');
});

test('filament recipe edit page shows media section', function () {
    $recipe = Recipe::factory()->create(['author_id' => $this->admin->id]);

    Livewire::actingAs($this->admin)
        ->test(EditRecipe::class, ['record' => $recipe->getRouteKey()])
        ->assertFormFieldExists('hero')
        ->assertFormFieldExists('gallery');
});

test('duplicate recipe copies media', function () {
    $recipe = Recipe::factory()->create([
        'title' => 'Media Recipe',
        'slug' => 'media-recipe',
        'author_id' => $this->admin->id,
    ]);

    $recipe->addMedia(UploadedFile::fake()->image('hero.jpg'))
        ->toMediaCollection('hero');
    $recipe->addMedia(UploadedFile::fake()->image('gallery1.jpg'))
        ->toMediaCollection('gallery');
    $recipe->addMedia(UploadedFile::fake()->image('gallery2.jpg'))
        ->toMediaCollection('gallery');

    $originalHeroId = $recipe->getFirstMedia('hero')->id;

    $clone = RecipeResource::duplicateRecipe($recipe);
    $clone->refresh();

    expect($clone->getMedia('hero'))->toHaveCount(1)
        ->and($clone->getMedia('gallery'))->toHaveCount(2)
        ->and($clone->getFirstMedia('hero')->id)->not->toBe($originalHeroId);
});

test('deleting recipe clears all media', function () {
    $recipe = Recipe::factory()->create(['author_id' => $this->admin->id]);

    $recipe->addMedia(UploadedFile::fake()->image('hero.jpg'))
        ->toMediaCollection('hero');
    $recipe->addMedia(UploadedFile::fake()->image('gallery1.jpg'))
        ->toMediaCollection('gallery');

    $recipeId = $recipe->id;
    $recipe->forceDelete();

    expect(Media::where('model_type', Recipe::class)
        ->where('model_id', $recipeId)
        ->count())->toBe(0);
});

test('recipe list table has hero image column', function () {
    $recipe = Recipe::factory()->create(['author_id' => $this->admin->id]);

    Livewire::actingAs($this->admin)
        ->test(RecipeResource\Pages\ListRecipes::class)
        ->assertCanRenderTableColumn('hero');
});
