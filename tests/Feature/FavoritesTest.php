<?php

use App\Livewire\Cabinet\FavoritesList;
use App\Livewire\FavoriteButton;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\QueryException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->user = User::factory()->create();
    $this->user->assignRole('user');
});

test('user can favorite a recipe', function () {
    $recipe = Recipe::factory()->published()->create();

    Livewire::actingAs($this->user)
        ->test(FavoriteButton::class, ['recipeId' => $recipe->id])
        ->assertSet('isFavorited', false)
        ->call('toggle')
        ->assertSet('isFavorited', true)
        ->assertDispatched('favorite-toggled');

    expect($this->user->favorites()->where('recipe_id', $recipe->id)->exists())->toBeTrue();
});

test('user can unfavorite a recipe', function () {
    $recipe = Recipe::factory()->published()->create();
    $this->user->favorites()->attach($recipe->id);

    Livewire::actingAs($this->user)
        ->test(FavoriteButton::class, ['recipeId' => $recipe->id])
        ->assertSet('isFavorited', true)
        ->call('toggle')
        ->assertSet('isFavorited', false);

    expect($this->user->favorites()->where('recipe_id', $recipe->id)->exists())->toBeFalse();
});

test('guest is redirected to login when toggling favorite', function () {
    $recipe = Recipe::factory()->published()->create();

    Livewire::test(FavoriteButton::class, ['recipeId' => $recipe->id])
        ->assertSet('isFavorited', false)
        ->call('toggle')
        ->assertRedirect(route('login'));
});

test('favorite button shows correct initial state', function () {
    $recipe = Recipe::factory()->published()->create();
    $this->user->favorites()->attach($recipe->id);

    Livewire::actingAs($this->user)
        ->test(FavoriteButton::class, ['recipeId' => $recipe->id])
        ->assertSet('isFavorited', true)
        ->assertSee(__('recipes.favorited'));
});

test('favorite button shows unfavorited state for non-saved recipe', function () {
    $recipe = Recipe::factory()->published()->create();

    Livewire::actingAs($this->user)
        ->test(FavoriteButton::class, ['recipeId' => $recipe->id])
        ->assertSet('isFavorited', false)
        ->assertSee(__('recipes.add_favorite'));
});

test('recipe detail page shows favorite button', function () {
    $recipe = Recipe::factory()->published()->create([
        'slug' => 'test-recipe',
    ]);

    $this->actingAs($this->user)
        ->get(route('recipes.show', 'test-recipe'))
        ->assertOk()
        ->assertSeeLivewire(FavoriteButton::class);
});

test('favorites page requires authentication', function () {
    $this->get(route('cabinet.favorites'))
        ->assertRedirect(route('login'));
});

test('favorites page renders for authenticated user', function () {
    $this->actingAs($this->user)
        ->get(route('cabinet.favorites'))
        ->assertOk()
        ->assertSeeLivewire(FavoritesList::class);
});

test('favorites page lists favorited recipes', function () {
    $recipe = Recipe::factory()->published()->create(['title' => 'My Favorite Pasta']);
    $this->user->favorites()->attach($recipe->id);

    Livewire::actingAs($this->user)
        ->test(FavoritesList::class)
        ->assertSee('My Favorite Pasta');
});

test('favorites page does not show unfavorited recipes', function () {
    $favorited = Recipe::factory()->published()->create(['title' => 'Favorited Recipe']);
    $notFavorited = Recipe::factory()->published()->create(['title' => 'Not Favorited Recipe']);
    $this->user->favorites()->attach($favorited->id);

    Livewire::actingAs($this->user)
        ->test(FavoritesList::class)
        ->assertSee('Favorited Recipe')
        ->assertDontSee('Not Favorited Recipe');
});

test('favorites page shows empty state when no favorites', function () {
    Livewire::actingAs($this->user)
        ->test(FavoritesList::class)
        ->assertSee(__('cabinet.no_favorites'));
});

test('user can remove favorite from favorites list', function () {
    $recipe = Recipe::factory()->published()->create(['title' => 'Recipe To Remove']);
    $this->user->favorites()->attach($recipe->id);

    Livewire::actingAs($this->user)
        ->test(FavoritesList::class)
        ->assertSee('Recipe To Remove')
        ->call('unfavorite', $recipe->id)
        ->assertDontSee('Recipe To Remove');

    expect($this->user->favorites()->where('recipe_id', $recipe->id)->exists())->toBeFalse();
});

test('favorites page excludes unpublished recipes', function () {
    $published = Recipe::factory()->published()->create(['title' => 'Published Fav']);
    $draft = Recipe::factory()->create(['title' => 'Draft Fav', 'status' => 'draft']);
    $this->user->favorites()->attach([$published->id, $draft->id]);

    Livewire::actingAs($this->user)
        ->test(FavoritesList::class)
        ->assertSee('Published Fav')
        ->assertDontSee('Draft Fav');
});

test('favorites page supports sorting by newest saved', function () {
    $recipe1 = Recipe::factory()->published()->create(['title' => 'First Recipe']);
    $recipe2 = Recipe::factory()->published()->create(['title' => 'Second Recipe']);

    $this->user->favorites()->attach($recipe1->id, ['created_at' => now()->subMinute()]);
    $this->user->favorites()->attach($recipe2->id, ['created_at' => now()]);

    Livewire::actingAs($this->user)
        ->test(FavoritesList::class)
        ->assertSet('sort', 'newest')
        ->assertSeeInOrder(['Second Recipe', 'First Recipe']);
});

test('favorites page supports sorting alphabetically', function () {
    $recipeA = Recipe::factory()->published()->create(['title' => 'Apple Pie']);
    $recipeZ = Recipe::factory()->published()->create(['title' => 'Zucchini Soup']);
    $this->user->favorites()->attach([$recipeA->id, $recipeZ->id]);

    Livewire::actingAs($this->user)
        ->test(FavoritesList::class)
        ->set('sort', 'alpha')
        ->assertSeeInOrder(['Apple Pie', 'Zucchini Soup']);
});

test('user favorites relation returns correct recipes', function () {
    $recipe1 = Recipe::factory()->published()->create();
    $recipe2 = Recipe::factory()->published()->create();
    $recipe3 = Recipe::factory()->published()->create();

    $this->user->favorites()->attach([$recipe1->id, $recipe3->id]);

    $favorites = $this->user->favorites;
    expect($favorites)->toHaveCount(2)
        ->and($favorites->pluck('id')->toArray())->toContain($recipe1->id, $recipe3->id)
        ->and($favorites->pluck('id')->toArray())->not->toContain($recipe2->id);
});

test('recipe favoritedBy relation returns correct users', function () {
    $recipe = Recipe::factory()->published()->create();
    $user2 = User::factory()->create();

    $this->user->favorites()->attach($recipe->id);
    $user2->favorites()->attach($recipe->id);

    expect($recipe->favoritedBy)->toHaveCount(2);
});

test('favorites are cleaned up when recipe is deleted', function () {
    $recipe = Recipe::factory()->published()->create();
    $this->user->favorites()->attach($recipe->id);

    expect($this->user->favorites()->count())->toBe(1);

    $recipe->forceDelete();

    expect($this->user->favorites()->count())->toBe(0);
});

test('favorites are cleaned up when user is deleted', function () {
    $recipe = Recipe::factory()->published()->create();
    $this->user->favorites()->attach($recipe->id);

    expect($recipe->favoritedBy()->count())->toBe(1);

    $this->user->forceDelete();

    expect($recipe->favoritedBy()->count())->toBe(0);
});

test('dashboard shows favorites link instead of placeholder', function () {
    $this->actingAs($this->user)
        ->get(route('cabinet'))
        ->assertOk()
        ->assertSee(route('cabinet.favorites'))
        ->assertSee(__('cabinet.favorites_desc'));
});

test('duplicate favorite is prevented by composite primary key', function () {
    $recipe = Recipe::factory()->published()->create();
    $this->user->favorites()->attach($recipe->id);

    expect(fn () => $this->user->favorites()->attach($recipe->id))
        ->toThrow(QueryException::class);
});
