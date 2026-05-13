<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Cuisine;
use App\Models\Recipe;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class RecipeBrowser extends Component
{
    use WithPagination;

    public ?int $category_id = null;

    public ?int $cuisine_id = null;

    /** @var array<string, array<string, string|null>> */
    protected $queryString = [
        'category_id' => ['except' => null, 'as' => 'category'],
        'cuisine_id' => ['except' => null, 'as' => 'cuisine'],
    ];

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatedCuisineId(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->category_id = null;
        $this->cuisine_id = null;
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.recipe-browser', [
            'recipes' => $this->getRecipes(),
            'categories' => Category::orderBy('name')->get(),
            'cuisines' => Cuisine::orderBy('name')->get(),
        ])->layout('components.layouts.app', [
            'title' => __('recipes.catalog'),
        ]);
    }

    /** @return LengthAwarePaginator<int, Recipe> */
    private function getRecipes(): LengthAwarePaginator
    {
        return Recipe::query()
            ->where('status', 'published')
            ->when($this->category_id, fn ($q) => $q->where('category_id', $this->category_id))
            ->when($this->cuisine_id, fn ($q) => $q->where('cuisine_id', $this->cuisine_id))
            ->orderByDesc('published_at')
            ->paginate(12);
    }
}
