<?php

use App\Livewire\Cabinet\Dashboard;
use App\Livewire\Cabinet\HealthForm;
use App\Livewire\Cabinet\ProfileForm;
use App\Livewire\RecipeBrowser;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/recipes', RecipeBrowser::class)->name('recipes.index');
Route::get('/recipes/{slug}', fn () => abort(404))->name('recipes.show');

Route::middleware(['auth', 'verified'])->prefix('cabinet')->group(function () {
    Route::get('/', Dashboard::class)->name('cabinet');
    Route::get('/profile', ProfileForm::class)->name('cabinet.profile');
    Route::get('/health', HealthForm::class)->name('cabinet.health');
});
