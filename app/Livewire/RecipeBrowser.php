<?php

namespace App\Livewire;

use App\Models\Allergen;
use App\Models\Category;
use App\Models\Cuisine;
use App\Models\Recipe;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class RecipeBrowser extends Component
{
    use WithPagination;

    /** @var array<int> */
    public array $category_ids = [];

    /** @var array<int> */
    public array $cuisine_ids = [];

    public ?int $max_kcal = null;

    public ?int $max_prep_time = null;

    /** @var array<int> */
    public array $diet_tags = [];

    /** @var array<int> */
    public array $exclude_allergens = [];

    public string $sort = 'newest';

    public string $search = '';

    /** @var array<int> */
    public array $include_ingredients = [];

    /** @var array<int> */
    public array $exclude_ingredients = [];

    /** @var array<string, array<string, mixed>> */
    protected $queryString = [
        'category_ids' => ['except' => [], 'as' => 'categories'],
        'cuisine_ids' => ['except' => [], 'as' => 'cuisines'],
        'max_kcal' => ['except' => null],
        'max_prep_time' => ['except' => null],
        'search' => ['except' => '', 'as' => 'q'],
        'sort' => ['except' => 'newest'],
    ];

    public function updatedCategoryIds(): void
    {
        $this->resetPage();
    }

    public function updatedCuisineIds(): void
    {
        $this->resetPage();
    }

    public function toggleCategory(int $id): void
    {
        $this->category_ids = in_array($id, $this->category_ids, true)
            ? array_values(array_diff($this->category_ids, [$id]))
            : [...$this->category_ids, $id];
        $this->resetPage();
    }

    public function toggleCuisine(int $id): void
    {
        $this->cuisine_ids = in_array($id, $this->cuisine_ids, true)
            ? array_values(array_diff($this->cuisine_ids, [$id]))
            : [...$this->cuisine_ids, $id];
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /** @param  array<int>  $ids */
    #[On('ingredient-filter-updated')]
    public function onIngredientFilterUpdated(string $mode, array $ids): void
    {
        match ($mode) {
            'include' => $this->include_ingredients = $ids,
            'exclude' => $this->exclude_ingredients = $ids,
            default => null,
        };
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->category_ids = [];
        $this->cuisine_ids = [];
        $this->max_kcal = null;
        $this->max_prep_time = null;
        $this->diet_tags = [];
        $this->exclude_allergens = [];
        $this->include_ingredients = [];
        $this->exclude_ingredients = [];
        $this->sort = 'newest';
        $this->search = '';
        $this->dispatch('clear-ingredient-filters');
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->category_ids !== []
            || $this->cuisine_ids !== []
            || $this->max_kcal !== null
            || $this->max_prep_time !== null
            || $this->diet_tags !== []
            || $this->exclude_allergens !== []
            || $this->include_ingredients !== []
            || $this->exclude_ingredients !== []
            || $this->search !== '';
    }

    public function render(): View
    {
        $nameByLocale = 'name->'.app()->getLocale();

        return view('livewire.recipe-browser', [
            'recipes' => $this->getRecipes(),
            'categories' => Category::whereHas('recipes', fn ($q) => $q->where('status', 'published'))->orderBy($nameByLocale)->get(),
            'cuisines' => Cuisine::whereHas('recipes', fn ($q) => $q->where('status', 'published'))->orderBy($nameByLocale)->get(),
            'dietTags' => Tag::where('type', 'diet')
                ->whereHas('recipes', fn ($q) => $q->where('status', 'published'))
                ->orderBy($nameByLocale)->get(),
            'allergens' => Allergen::whereHas('ingredients.recipes', fn ($q) => $q->where('status', 'published'))
                ->orderBy($nameByLocale)->get(),
        ])->layout('components.layouts.app', [
            'title' => __('recipes.catalog').' — '.config('app.name'),
            'metaDescription' => __('recipes.catalog_desc'),
            'canonicalUrl' => route('recipes.index'),
        ]);
    }

    /** @return LengthAwarePaginator<int, Recipe> */
    private function getRecipes(): LengthAwarePaginator
    {
        $query = Recipe::query()
            ->where('status', 'published')
            ->when($this->category_ids !== [], fn ($q) => $q->whereIn('category_id', $this->category_ids))
            ->when($this->cuisine_ids !== [], fn ($q) => $q->whereIn('cuisine_id', $this->cuisine_ids))
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

        if ($this->include_ingredients !== []) {
            foreach ($this->include_ingredients as $ingredientId) {
                $query->whereHas('recipeIngredients', fn ($q) => $q->where('ingredient_id', $ingredientId));
            }
        }

        if ($this->exclude_ingredients !== []) {
            $query->whereDoesntHave('recipeIngredients', fn ($q) => $q->whereIn('ingredient_id', $this->exclude_ingredients));
        }

        if ($this->search !== '') {
            $ids = Recipe::search($this->search)->keys();
            $query->whereIn('id', $ids);
        }

        return $query
            ->with('media')
            ->when($this->search === '' && $this->sort === 'newest', fn ($q) => $q->orderByDesc('published_at'))
            ->when($this->sort === 'lowest_kcal', fn ($q) => $q->orderBy('kcal_per_serving'))
            ->when($this->sort === 'shortest_prep', fn ($q) => $q->orderBy('prep_time_min'))
            ->paginate(12);
    }
}
