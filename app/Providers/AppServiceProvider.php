<?php

namespace App\Providers;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Observers\IngredientObserver;
use App\Observers\RecipeIngredientObserver;
use App\Observers\RecipeObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Recipe::observe(RecipeObserver::class);
        RecipeIngredient::observe(RecipeIngredientObserver::class);
        Ingredient::observe(IngredientObserver::class);

        Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin')) {
                return true;
            }

            return null;
        });
    }
}
