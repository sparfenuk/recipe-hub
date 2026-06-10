<?php

namespace App\Livewire;

use App\Enums\RecipeStatus;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class RecipeDetail extends Component
{
    public Recipe $recipe;

    /**
     * Scaling state mirrored from the portion calculator so the main ingredient
     * list is the single source of truth for amounts, print included. (UX.3)
     */
    public float $portionFactor = 1.0;

    public ?int $portionServings = null;

    public string $portionMode = 'servings';

    public bool $portionScaled = false;

    public function mount(string $slug): void
    {
        $this->recipe = Recipe::query()
            ->where('slug', $slug)
            ->where('status', RecipeStatus::Published)
            ->with([
                'author',
                'category',
                'cuisine',
                'tags',
                'recipeIngredients.ingredient',
                'recipeIngredients.unit',
                'steps.media',
                'media',
            ])
            ->firstOrFail();
    }

    #[On('portion-scaled')]
    public function onPortionScaled(float $factor, ?int $servings, string $mode, bool $isScaled): void
    {
        $this->portionFactor = $factor;
        $this->portionServings = $servings;
        $this->portionMode = $mode;
        $this->portionScaled = $isScaled;
    }

    public function resetPortion(): void
    {
        $this->dispatch('reset-portion');
        $this->portionFactor = 1.0;
        $this->portionServings = null;
        $this->portionScaled = false;
    }

    /**
     * schema.org/Recipe structured data. Built here rather than in a Blade @php
     * block so it is type-checked and unit-testable; the view only json_encodes
     * it with the JSON_HEX_TAG|JSON_HEX_AMP XSS guard. (CQ.10)
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function jsonLd(): array
    {
        $recipe = $this->recipe;

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Recipe',
            'name' => $recipe->title,
            'url' => route('recipes.show', $recipe->slug),
        ];

        if ($recipe->summary) {
            $data['description'] = $recipe->summary;
        }

        $heroUrl = $recipe->getFirstMediaUrl('hero', 'full');
        if ($heroUrl !== '') {
            $data['image'] = $heroUrl;
        }

        $author = $recipe->author;
        if ($author !== null) {
            $data['author'] = ['@type' => 'Person', 'name' => $author->name];
        }

        $publishedAt = $recipe->published_at;
        if ($publishedAt !== null) {
            $data['datePublished'] = Carbon::parse($publishedAt)->toIso8601String();
        }

        if ($recipe->prep_time_min) {
            $data['prepTime'] = 'PT'.$recipe->prep_time_min.'M';
        }

        if ($recipe->cook_time_min) {
            $data['cookTime'] = 'PT'.$recipe->cook_time_min.'M';
        }

        if ($recipe->total_time_min) {
            $data['totalTime'] = 'PT'.$recipe->total_time_min.'M';
        }

        if ($recipe->servings) {
            $data['recipeYield'] = $recipe->servings.' servings';
        }

        $category = $recipe->category;
        if ($category !== null) {
            $data['recipeCategory'] = $category->name;
        }

        $cuisine = $recipe->cuisine;
        if ($cuisine !== null) {
            $data['recipeCuisine'] = $cuisine->name;
        }

        if ($recipe->tags->isNotEmpty()) {
            $data['keywords'] = $recipe->tags->pluck('name')->implode(', ');
        }

        $data['recipeIngredient'] = $recipe->recipeIngredients->map(function (RecipeIngredient $ri): string {
            $parts = [];
            if ($ri->amount) {
                $parts[] = rtrim(rtrim(number_format((float) $ri->amount, 3), '0'), '.');
            }
            $unit = $ri->unit;
            if ($unit !== null) {
                $parts[] = $unit->name;
            }
            $ingredient = $ri->ingredient;
            if ($ingredient !== null) {
                $parts[] = $ingredient->name;
            }

            return implode(' ', $parts);
        })->values()->all();

        $data['recipeInstructions'] = $recipe->steps->map(fn (RecipeStep $step): array => [
            '@type' => 'HowToStep',
            'position' => $step->position,
            'text' => trim(strip_tags((string) $step->body)),
        ])->values()->all();

        if ($recipe->display_kcal_per_serving) {
            $data['nutrition'] = [
                '@type' => 'NutritionInformation',
                'calories' => $recipe->display_kcal_per_serving.' kcal',
                'proteinContent' => $recipe->display_protein_per_serving_g.'g',
                'fatContent' => $recipe->display_fat_per_serving_g.'g',
                'carbohydrateContent' => $recipe->display_carbs_per_serving_g.'g',
                'fiberContent' => $recipe->fiber_per_serving_g.'g',
            ];
        }

        return $data;
    }

    public function render(): View
    {
        // Subsequent (event-driven) renders rehydrate the model without the
        // mount-time eager loads, so reload what the view needs.
        $this->recipe->loadMissing([
            'author',
            'category',
            'cuisine',
            'tags',
            'recipeIngredients.ingredient',
            'recipeIngredients.unit',
            'steps.media',
            'media',
        ]);

        $heroUrl = $this->recipe->getFirstMediaUrl('hero', 'full');

        return view('livewire.recipe-detail', [
            'relatedRecipes' => $this->relatedRecipes(),
        ])
            ->layout('components.layouts.app', [
                'title' => $this->recipe->title.' — '.config('app.name'),
                'metaDescription' => $this->recipe->summary ?? mb_substr(strip_tags((string) $this->recipe->description), 0, 160),
                'ogType' => 'article',
                'ogImage' => $heroUrl ?: null,
                'canonicalUrl' => route('recipes.show', $this->recipe->slug),
            ]);
    }

    /**
     * Up to three other published recipes from the same category, newest first. (UX.18)
     *
     * @return Collection<int, Recipe>
     */
    private function relatedRecipes(): Collection
    {
        if ($this->recipe->category_id === null) {
            return collect();
        }

        return Recipe::query()
            ->where('status', RecipeStatus::Published)
            ->where('category_id', $this->recipe->category_id)
            ->whereKeyNot($this->recipe->id)
            ->with('media')
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();
    }
}
