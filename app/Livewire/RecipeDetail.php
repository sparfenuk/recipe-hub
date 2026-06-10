<?php

namespace App\Livewire;

use App\Models\Recipe;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
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
            ->where('status', 'published')
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
            ->where('status', 'published')
            ->where('category_id', $this->recipe->category_id)
            ->whereKeyNot($this->recipe->id)
            ->with('media')
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();
    }
}
