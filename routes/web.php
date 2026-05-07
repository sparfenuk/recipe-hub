<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/cabinet', function () {
        return view('cabinet.dashboard');
    })->name('cabinet');
});
