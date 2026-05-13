<?php

namespace App\Livewire;

use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PortionCalculator extends Component
{
    public Recipe $recipe;

    public int $originalServings;

    public ?int $targetServings = null;

    public function mount(Recipe $recipe): void
    {
        $this->recipe = $recipe;
        $this->originalServings = max($recipe->servings, 1);
        $this->targetServings = $this->originalServings;
    }

    #[Computed]
    public function scaleFactor(): float
    {
        if (! $this->targetServings || $this->targetServings < 1) {
            return 1.0;
        }

        return $this->targetServings / $this->originalServings;
    }

    /** @return Collection<int, mixed> */
    #[Computed]
    public function scaledIngredients(): Collection
    {
        $this->recipe->loadMissing('recipeIngredients.ingredient', 'recipeIngredients.unit');
        $factor = $this->scaleFactor();

        return $this->recipe->recipeIngredients->map(fn (RecipeIngredient $ri): array => [
            'name' => $ri->ingredient?->name,
            'amount' => round((float) $ri->amount * $factor, 3),
            'unit_code' => $ri->unit?->code,
            'note' => $ri->note,
            'is_optional' => (bool) $ri->is_optional,
            'group_label' => $ri->group_label,
        ]);
    }

    /** @return array{kcal: float, protein_g: float, fat_g: float, carbs_g: float, fiber_g: float, kcal_per_serving: float, protein_per_serving_g: float, fat_per_serving_g: float, carbs_per_serving_g: float, fiber_per_serving_g: float} */
    #[Computed]
    public function scaledNutrition(): array
    {
        $factor = $this->scaleFactor();

        return [
            'kcal' => round((float) $this->recipe->total_kcal * $factor, 2),
            'protein_g' => round((float) $this->recipe->total_protein_g * $factor, 2),
            'fat_g' => round((float) $this->recipe->total_fat_g * $factor, 2),
            'carbs_g' => round((float) $this->recipe->total_carbs_g * $factor, 2),
            'fiber_g' => round((float) $this->recipe->total_fiber_g * $factor, 2),
            'kcal_per_serving' => round((float) ($this->recipe->kcal_per_serving ?? 0), 2),
            'protein_per_serving_g' => round((float) ($this->recipe->protein_per_serving_g ?? 0), 2),
            'fat_per_serving_g' => round((float) ($this->recipe->fat_per_serving_g ?? 0), 2),
            'carbs_per_serving_g' => round((float) ($this->recipe->carbs_per_serving_g ?? 0), 2),
            'fiber_per_serving_g' => round((float) ($this->recipe->fiber_per_serving_g ?? 0), 2),
        ];
    }

    public function resetServings(): void
    {
        $this->targetServings = $this->originalServings;
    }

    public function increment(): void
    {
        $this->targetServings = min(($this->targetServings ?? $this->originalServings) + 1, 100);
    }

    public function decrement(): void
    {
        $this->targetServings = max(($this->targetServings ?? $this->originalServings) - 1, 1);
    }

    public function render(): View
    {
        return view('livewire.portion-calculator', [
            'isScaled' => $this->targetServings !== $this->originalServings,
        ]);
    }
}
