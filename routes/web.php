<?php

use App\Http\Controllers\RecipePdfController;
use App\Livewire\Cabinet\CalculationHistory;
use App\Livewire\Cabinet\Dashboard;
use App\Livewire\Cabinet\FavoritesList;
use App\Livewire\Cabinet\HealthForm;
use App\Livewire\Cabinet\ProfileForm;
use App\Livewire\RecipeBrowser;
use App\Livewire\RecipeDetail;
use App\Models\Recipe;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('/book', 'pages.book')->name('book');
Route::view('/author', 'pages.author')->name('author');

Route::get('/recipes', RecipeBrowser::class)->name('recipes.index');
Route::get('/recipes/random', function () {
    $recipe = Recipe::query()
        ->where('status', 'published')
        ->inRandomOrder()
        ->first();

    abort_unless($recipe !== null, 404);

    return redirect()->route('recipes.show', $recipe->slug);
})->name('recipes.random');
Route::get('/recipes/{slug}', RecipeDetail::class)->name('recipes.show');
Route::get('/recipes/{slug}/pdf', RecipePdfController::class)->middleware('throttle:10,1')->name('recipes.pdf');

Route::middleware(['auth', 'verified'])->prefix('cabinet')->group(function () {
    Route::get('/', Dashboard::class)->name('cabinet');
    Route::get('/profile', ProfileForm::class)->name('cabinet.profile');
    Route::get('/health', HealthForm::class)->name('cabinet.health');
    Route::get('/favorites', FavoritesList::class)->name('cabinet.favorites');
    Route::get('/calculations', CalculationHistory::class)->name('cabinet.calculations');
});
