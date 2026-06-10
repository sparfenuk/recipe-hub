<?php

namespace App\Services;

use App\Enums\RecipeStatus;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecipeDuplicationService
{
    /**
     * Deep-clone a recipe as a draft: copies translations, ingredients, steps,
     * tags and media, with a freshly de-duplicated slug. Wrapped in a transaction
     * so a partial failure (e.g. a media copy throwing) can't leave a half-cloned
     * recipe behind. Extracted from the Filament resource so it is unit-testable
     * and reusable outside the admin panel (CQ.9).
     */
    public function duplicate(Recipe $recipe): Recipe
    {
        $recipe->loadMissing('recipeIngredients', 'steps', 'tags', 'media');

        return DB::transaction(function () use ($recipe): Recipe {
            $clone = $recipe->replicate();

            foreach ($recipe->getTranslations('title') as $locale => $value) {
                $clone->setTranslation('title', $locale, $value.' (Copy)');
            }

            $clone->status = RecipeStatus::Draft;
            $clone->published_at = null;
            $clone->nutrition_cached_at = null;
            $clone->slug = $this->uniqueSlug($recipe);
            $clone->save();

            foreach ($recipe->recipeIngredients as $ri) {
                $clone->recipeIngredients()->create($ri->only([
                    'ingredient_id', 'position', 'amount', 'unit_id',
                    'grams_override', 'note', 'is_optional', 'group_label',
                ]));
            }

            foreach ($recipe->steps as $step) {
                $clone->steps()->create([
                    'position' => $step->position,
                    'body' => $step->getTranslations('body'),
                ]);
            }

            $clone->tags()->sync($recipe->tags->pluck('id'));

            foreach ($recipe->media as $media) {
                $media->copy($clone, $media->collection_name);
            }

            return $clone;
        });
    }

    /**
     * Slug of the form "<base>-copy", suffixed with -2, -3, … on collision
     * (including soft-deleted recipes, whose slug is still reserved).
     */
    private function uniqueSlug(Recipe $recipe): string
    {
        $base = Str::slug($recipe->getTranslation('title', 'en', false) ?: $recipe->getTranslation('title', 'uk'));
        $base = $base !== '' ? $base.'-copy' : 'copy';

        $slug = $base;
        $counter = 1;

        while (Recipe::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.++$counter;
        }

        return $slug;
    }
}
