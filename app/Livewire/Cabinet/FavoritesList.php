<?php

namespace App\Livewire\Cabinet;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class FavoritesList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sort = 'newest';

    /** @var array<string, array<string, mixed>> */
    protected $queryString = [
        'search' => ['except' => '', 'as' => 'q'],
        'sort' => ['except' => 'newest'],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function unfavorite(int $recipeId): void
    {
        /** @var User $user */
        $user = Auth::user();
        $user->favorites()->detach($recipeId);
    }

    public function render(): View
    {
        return view('livewire.cabinet.favorites-list', [
            'recipes' => $this->getFavorites(),
        ])->layout('components.layouts.app', [
            'title' => __('cabinet.favorites'),
        ]);
    }

    /** @return LengthAwarePaginator<int, Recipe> */
    private function getFavorites(): LengthAwarePaginator
    {
        /** @var User $user */
        $user = Auth::user();

        $query = $user->favorites()
            ->where('status', 'published')
            ->with('media');

        if ($this->search !== '') {
            $ids = Recipe::search($this->search)->keys();
            $query->whereIn('recipes.id', $ids);
        }

        return $query
            ->when($this->sort === 'newest', fn ($q) => $q->orderByDesc('favorites.created_at'))
            ->when($this->sort === 'oldest', fn ($q) => $q->orderBy('favorites.created_at'))
            ->when($this->sort === 'alpha', fn ($q) => $q->orderBy('recipes.title->'.app()->getLocale()))
            ->when($this->sort === 'lowest_kcal', fn ($q) => $q->orderByRaw('COALESCE(recipes.ref_kcal_per_serving, recipes.kcal_per_serving) asc'))
            ->paginate(12);
    }
}
