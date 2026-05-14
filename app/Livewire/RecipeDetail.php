<?php

namespace App\Livewire;

use App\Models\Recipe;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class RecipeDetail extends Component
{
    public Recipe $recipe;

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

    public function render(): View
    {
        $heroUrl = $this->recipe->getFirstMediaUrl('hero', 'full');

        return view('livewire.recipe-detail')
            ->layout('components.layouts.app', [
                'title' => $this->recipe->title.' — '.config('app.name'),
                'metaDescription' => $this->recipe->summary ?? mb_substr(strip_tags((string) $this->recipe->description), 0, 160),
                'ogType' => 'article',
                'ogImage' => $heroUrl ?: null,
                'canonicalUrl' => route('recipes.show', $this->recipe->slug),
            ]);
    }
}
