<?php

namespace App\Livewire;

use App\Models\Allergen;
use App\Models\Category;
use App\Models\Cuisine;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class RecipeBrowser extends Component
{
    use WithPagination;

    private const PAGE_SIZE = 12;

    public int $perPage = self::PAGE_SIZE;

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
        $this->resetView();
    }

    public function updatedCuisineIds(): void
    {
        $this->resetView();
    }

    public function toggleCategory(int $id): void
    {
        $this->category_ids = in_array($id, $this->category_ids, true)
            ? array_values(array_diff($this->category_ids, [$id]))
            : [...$this->category_ids, $id];
        $this->resetView();
    }

    public function toggleCuisine(int $id): void
    {
        $this->cuisine_ids = in_array($id, $this->cuisine_ids, true)
            ? array_values(array_diff($this->cuisine_ids, [$id]))
            : [...$this->cuisine_ids, $id];
        $this->resetView();
    }

    public function updatedMaxKcal(): void
    {
        $this->resetView();
    }

    public function updatedMaxPrepTime(): void
    {
        $this->resetView();
    }

    public function updatedDietTags(): void
    {
        $this->resetView();
    }

    public function updatedExcludeAllergens(): void
    {
        $this->resetView();
    }

    public function updatedSort(): void
    {
        $this->resetView();
    }

    public function updatedSearch(): void
    {
        $this->resetView();
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
        $this->resetView();
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
        $this->resetView();
    }

    public function loadMore(): void
    {
        $this->perPage += self::PAGE_SIZE;
    }

    private function resetView(): void
    {
        $this->perPage = self::PAGE_SIZE;
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->activeFilterCount() > 0;
    }

    /** Number of filters currently applied — drives the mobile "Filters (n)" button (UX.4). */
    public function activeFilterCount(): int
    {
        return count($this->category_ids)
            + count($this->cuisine_ids)
            + count($this->diet_tags)
            + count($this->exclude_allergens)
            + count($this->include_ingredients)
            + count($this->exclude_ingredients)
            + ($this->max_kcal !== null ? 1 : 0)
            + ($this->max_prep_time !== null ? 1 : 0)
            + ($this->search !== '' ? 1 : 0);
    }

    public function removeDietTag(int $id): void
    {
        $this->diet_tags = array_values(array_filter($this->diet_tags, fn ($v): bool => (int) $v !== $id));
        $this->resetView();
    }

    public function removeAllergen(int $id): void
    {
        $this->exclude_allergens = array_values(array_filter($this->exclude_allergens, fn ($v): bool => (int) $v !== $id));
        $this->resetView();
    }

    public function removeIngredient(string $mode, int $id): void
    {
        if ($mode === 'include') {
            $this->include_ingredients = array_values(array_diff($this->include_ingredients, [$id]));
        } elseif ($mode === 'exclude') {
            $this->exclude_ingredients = array_values(array_diff($this->exclude_ingredients, [$id]));
        }

        // Keep the autocomplete component's own chips in sync.
        $this->dispatch('remove-ingredient', mode: $mode, id: $id);
        $this->resetView();
    }

    public function clearMaxKcal(): void
    {
        $this->max_kcal = null;
        $this->resetView();
    }

    public function clearMaxPrepTime(): void
    {
        $this->max_prep_time = null;
        $this->resetView();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetView();
    }

    public function render(): View
    {
        $nameByLocale = 'name->'.app()->getLocale();

        $categories = Category::whereHas('recipes', fn ($q) => $q->where('status', 'published'))->orderBy($nameByLocale)->get();
        $cuisines = Cuisine::whereHas('recipes', fn ($q) => $q->where('status', 'published'))->orderBy($nameByLocale)->get();
        $dietTags = Tag::where('type', 'diet')
            ->whereHas('recipes', fn ($q) => $q->where('status', 'published'))
            ->orderBy($nameByLocale)->get();
        $allergens = Allergen::whereHas('ingredients.recipes', fn ($q) => $q->where('status', 'published'))
            ->orderBy($nameByLocale)->get();

        return view('livewire.recipe-browser', [
            'recipes' => $this->getRecipes(),
            'categories' => $categories,
            'cuisines' => $cuisines,
            'dietTags' => $dietTags,
            'allergens' => $allergens,
            'activeFilters' => $this->buildActiveFilters($categories, $cuisines, $dietTags, $allergens),
        ])->layout('components.layouts.app', [
            'title' => __('recipes.catalog').' — '.config('app.name'),
            'metaDescription' => __('recipes.catalog_desc'),
            'canonicalUrl' => route('recipes.index'),
        ]);
    }

    /**
     * Build the active-filter chips shown above the results grid (UX.4).
     *
     * @param  Collection<int, Category>  $categories
     * @param  Collection<int, Cuisine>  $cuisines
     * @param  Collection<int, Tag>  $dietTags
     * @param  Collection<int, Allergen>  $allergens
     * @return list<array{label: string, action: string}>
     */
    private function buildActiveFilters($categories, $cuisines, $dietTags, $allergens): array
    {
        $chips = [];

        foreach ($this->category_ids as $id) {
            if (($name = $categories->firstWhere('id', $id)?->name) !== null) {
                $chips[] = ['label' => (string) $name, 'action' => "toggleCategory($id)"];
            }
        }

        foreach ($this->cuisine_ids as $id) {
            if (($name = $cuisines->firstWhere('id', $id)?->name) !== null) {
                $chips[] = ['label' => (string) $name, 'action' => "toggleCuisine($id)"];
            }
        }

        foreach ($this->diet_tags as $tag) {
            $id = (int) $tag;
            if (($name = $dietTags->firstWhere('id', $id)?->name) !== null) {
                $chips[] = ['label' => (string) $name, 'action' => "removeDietTag($id)"];
            }
        }

        foreach ($this->exclude_allergens as $allergen) {
            $id = (int) $allergen;
            if (($name = $allergens->firstWhere('id', $id)?->name) !== null) {
                $chips[] = ['label' => (string) $name, 'action' => "removeAllergen($id)"];
            }
        }

        $ingredientIds = array_merge($this->include_ingredients, $this->exclude_ingredients);
        $ingredientNames = $ingredientIds === []
            ? collect()
            : Ingredient::whereIn('id', $ingredientIds)->pluck('name', 'id');

        foreach ($this->include_ingredients as $id) {
            if (($name = $ingredientNames[$id] ?? null) !== null) {
                $chips[] = ['label' => (string) $name, 'action' => "removeIngredient('include', $id)"];
            }
        }

        foreach ($this->exclude_ingredients as $id) {
            if (($name = $ingredientNames[$id] ?? null) !== null) {
                $chips[] = ['label' => '− '.$name, 'action' => "removeIngredient('exclude', $id)"];
            }
        }

        if ($this->max_kcal !== null) {
            $chips[] = ['label' => (string) __('recipes.chip_max_kcal', ['value' => $this->max_kcal]), 'action' => 'clearMaxKcal'];
        }

        if ($this->max_prep_time !== null) {
            $chips[] = ['label' => (string) __('recipes.chip_max_prep', ['value' => $this->max_prep_time]), 'action' => 'clearMaxPrepTime'];
        }

        if ($this->search !== '') {
            $chips[] = ['label' => '“'.$this->search.'”', 'action' => 'clearSearch'];
        }

        return $chips;
    }

    /** @return LengthAwarePaginator<int, Recipe> */
    private function getRecipes(): LengthAwarePaginator
    {
        $query = Recipe::query()
            ->where('status', 'published')
            ->when($this->category_ids !== [], fn ($q) => $q->whereIn('category_id', $this->category_ids))
            ->when($this->cuisine_ids !== [], fn ($q) => $q->whereIn('cuisine_id', $this->cuisine_ids))
            ->when($this->max_kcal, fn ($q, $v) => $q->whereRaw('COALESCE(ref_kcal_per_serving, kcal_per_serving) <= ?', [$v]))
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
            ->when($this->sort === 'lowest_kcal', fn ($q) => $q->orderByRaw('COALESCE(ref_kcal_per_serving, kcal_per_serving) asc'))
            ->when($this->sort === 'shortest_prep', fn ($q) => $q->orderBy('prep_time_min'))
            ->paginate($this->perPage, ['*'], 'page', 1);
    }
}
