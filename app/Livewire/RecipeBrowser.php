<?php

namespace App\Livewire;

use App\Models\Allergen;
use App\Models\Category;
use App\Models\Cuisine;
use App\Models\Recipe;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class RecipeBrowser extends Component
{
    use WithPagination;

    public ?int $category_id = null;

    public ?int $cuisine_id = null;

    public ?int $max_kcal = null;

    public ?int $max_prep_time = null;

    /** @var array<int> */
    public array $diet_tags = [];

    /** @var array<int> */
    public array $exclude_allergens = [];

    public string $sort = 'newest';

    /** @var array<string, array<string, mixed>> */
    protected $queryString = [
        'category_id' => ['except' => null, 'as' => 'category'],
        'cuisine_id' => ['except' => null, 'as' => 'cuisine'],
        'max_kcal' => ['except' => null],
        'max_prep_time' => ['except' => null],
        'sort' => ['except' => 'newest'],
    ];

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatedCuisineId(): void
    {
        $this->resetPage();
    }

    public function updatedMaxKcal(): void
    {
        $this->resetPage();
    }

    public function updatedMaxPrepTime(): void
    {
        $this->resetPage();
    }

    public function updatedDietTags(): void
    {
        $this->resetPage();
    }

    public function updatedExcludeAllergens(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->category_id = null;
        $this->cuisine_id = null;
        $this->max_kcal = null;
        $this->max_prep_time = null;
        $this->diet_tags = [];
        $this->exclude_allergens = [];
        $this->sort = 'newest';
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->category_id !== null
            || $this->cuisine_id !== null
            || $this->max_kcal !== null
            || $this->max_prep_time !== null
            || $this->diet_tags !== []
            || $this->exclude_allergens !== [];
    }

    public function render(): View
    {
        return view('livewire.recipe-browser', [
            'recipes' => $this->getRecipes(),
            'categories' => Category::orderBy('name')->get(),
            'cuisines' => Cuisine::orderBy('name')->get(),
            'dietTags' => Tag::where('type', 'diet')->orderBy('name')->get(),
            'allergens' => Allergen::orderBy('name')->get(),
        ])->layout('components.layouts.app', [
            'title' => __('recipes.catalog'),
        ]);
    }

    /** @return LengthAwarePaginator<int, Recipe> */
    private function getRecipes(): LengthAwarePaginator
    {
        $query = Recipe::query()
            ->where('status', 'published')
            ->when($this->category_id, fn ($q) => $q->where('category_id', $this->category_id))
            ->when($this->cuisine_id, fn ($q) => $q->where('cuisine_id', $this->cuisine_id))
            ->when($this->max_kcal, fn ($q, $v) => $q->where('kcal_per_serving', '<=', $v))
            ->when($this->max_prep_time, fn ($q, $v) => $q->where('prep_time_min', '<=', $v));

        if ($this->diet_tags !== []) {
            $query->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $this->diet_tags), '>=', count($this->diet_tags));
        }

        if ($this->exclude_allergens !== []) {
            $query->whereDoesntHave('recipeIngredients', function ($q) {
                $q->whereHas('ingredient', function ($q2) {
                    $q2->whereHas('allergens', fn ($q3) => $q3->whereIn('allergens.id', $this->exclude_allergens));
                });
            });
        }

        return $query
            ->when($this->sort === 'newest', fn ($q) => $q->orderByDesc('published_at'))
            ->when($this->sort === 'lowest_kcal', fn ($q) => $q->orderBy('kcal_per_serving'))
            ->when($this->sort === 'shortest_prep', fn ($q) => $q->orderBy('prep_time_min'))
            ->paginate(12);
    }
}
