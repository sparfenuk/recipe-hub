<?php

namespace App\Livewire;

use App\Models\Ingredient;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class IngredientAutocomplete extends Component
{
    public string $query = '';

    public string $mode = 'include';

    /** @var array<int, string> */
    public array $selected = [];

    public function updatedQuery(): void
    {
        // Results are computed in render()
    }

    public function selectIngredient(int $id, string $name): void
    {
        if (! isset($this->selected[$id])) {
            $this->selected[$id] = $name;
        }

        $this->query = '';
        $this->dispatch('ingredient-filter-updated', mode: $this->mode, ids: array_map('intval', array_keys($this->selected)));
    }

    public function removeIngredient(int $id): void
    {
        unset($this->selected[$id]);
        $this->dispatch('ingredient-filter-updated', mode: $this->mode, ids: array_map('intval', array_keys($this->selected)));
    }

    #[On('clear-ingredient-filters')]
    public function clearSelection(): void
    {
        $this->selected = [];
        $this->query = '';
    }

    public function render(): View
    {
        return view('livewire.ingredient-autocomplete', [
            'results' => $this->getResults(),
        ]);
    }

    /** @return Collection<int, Ingredient> */
    private function getResults(): Collection
    {
        if (mb_strlen($this->query) < 2) {
            return collect();
        }

        return Ingredient::search($this->query)
            ->query(fn ($q) => $q->where('is_active', true))
            ->take(10)
            ->get()
            ->reject(fn (Ingredient $i) => isset($this->selected[$i->id]))
            ->values();
    }
}
