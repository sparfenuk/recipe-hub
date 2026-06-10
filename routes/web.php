<?php

use App\Http\Controllers\RecipePdfController;
use App\Livewire\Cabinet\CalculationHistory;
use App\Livewire\Cabinet\Dashboard;
use App\Livewire\Cabinet\FavoritesList;
use App\Livewire\Cabinet\HealthForm;
use App\Livewire\Cabinet\ProfileForm;
use App\Livewire\RecipeBrowser;
use App\Livewire\RecipeDetail;
use App\Models\Category;
use App\Models\Recipe;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Landing section tiles. Kept here (not in the Blade) so the category lookup
    // is a controller-layer query rather than a template-time DB call. (UX.26)
    $sectionTiles = [
        ['slug' => 'breakfast',        'label' => 'book.section_breakfast', 'icon' => 'heroicon-o-sun',      'classes' => 'from-amber-100 to-amber-50 text-amber-800 ring-amber-200 hover:ring-amber-400'],
        ['slug' => 'lunch',            'label' => 'book.section_lunch',     'icon' => 'heroicon-o-cake',     'classes' => 'from-emerald-100 to-emerald-50 text-emerald-800 ring-emerald-200 hover:ring-emerald-400'],
        ['slug' => 'dinner',           'label' => 'book.section_dinner',    'icon' => 'heroicon-o-moon',     'classes' => 'from-indigo-100 to-indigo-50 text-indigo-800 ring-indigo-200 hover:ring-indigo-400'],
        ['slug' => 'snacks',           'label' => 'book.section_snacks',    'icon' => 'heroicon-o-sparkles', 'classes' => 'from-rose-100 to-rose-50 text-rose-800 ring-rose-200 hover:ring-rose-400'],
        ['slug' => 'smoothies',        'label' => 'book.section_smoothies', 'icon' => 'heroicon-o-beaker',   'classes' => 'from-lime-100 to-lime-50 text-lime-800 ring-lime-200 hover:ring-lime-400'],
        ['slug' => 'ice-cream',        'label' => 'book.section_ice_cream', 'icon' => 'heroicon-o-cloud',    'classes' => 'from-sky-100 to-sky-50 text-sky-800 ring-sky-200 hover:ring-sky-400'],
        ['slug' => 'desserts',         'label' => 'book.section_desserts',  'icon' => 'heroicon-o-heart',    'classes' => 'from-pink-100 to-pink-50 text-pink-800 ring-pink-200 hover:ring-pink-400'],
        ['slug' => 'sauces-dressings', 'label' => 'book.section_sauces',    'icon' => 'heroicon-o-bolt',     'classes' => 'from-orange-100 to-orange-50 text-orange-800 ring-orange-200 hover:ring-orange-400'],
    ];

    $categoryIdBySlug = Category::query()
        ->whereIn('slug', array_column($sectionTiles, 'slug'))
        ->pluck('id', 'slug');

    return view('welcome', compact('sectionTiles', 'categoryIdBySlug'));
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
