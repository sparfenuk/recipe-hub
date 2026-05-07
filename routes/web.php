<?php

use App\Livewire\Cabinet\Dashboard;
use App\Livewire\Cabinet\ProfileForm;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->prefix('cabinet')->group(function () {
    Route::get('/', Dashboard::class)->name('cabinet');
    Route::get('/profile', ProfileForm::class)->name('cabinet.profile');
});
