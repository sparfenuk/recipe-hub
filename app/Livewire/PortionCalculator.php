<?php

namespace App\Livewire;

use App\Models\CalculatorSession;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read int|null $dailyKcalTarget
 * @property-read bool $isScaled
 */
class PortionCalculator extends Component
{
    public Recipe $recipe;

    public int $originalServings;

    public string $mode = 'servings';

    public ?int $targetServings = null;

    public ?float $targetKcal = null;

    public ?int $targetDailyPct = null;

    public bool $saved = false;

    public function mount(Recipe $recipe): void
    {
        $this->recipe = $recipe;
        $this->originalServings = max($recipe->servings, 1);
        $this->targetServings = $this->originalServings;
    }

    public function setMode(string $mode): void
    {
        if (! in_array($mode, ['servings', 'kcal', 'daily_pct'], true)) {
            return;
        }

        $this->mode = $mode;
        $this->saved = false;
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['targetServings', 'targetKcal', 'targetDailyPct'], true)) {
            $this->saved = false;
        }
    }

    #[Computed]
    public function scaleFactor(): float
    {
        return match ($this->mode) {
            'kcal' => $this->kcalScaleFactor(),
            'daily_pct' => $this->dailyPctScaleFactor(),
            default => $this->servingsScaleFactor(),
        };
    }

    private function servingsScaleFactor(): float
    {
        if (! $this->targetServings || $this->targetServings < 1) {
            return 1.0;
        }

        return $this->targetServings / $this->originalServings;
    }

    private function kcalScaleFactor(): float
    {
        $totalKcal = (float) $this->recipe->total_kcal;

        if (! $this->targetKcal || $this->targetKcal <= 0 || $totalKcal <= 0) {
            return 1.0;
        }

        return $this->targetKcal / $totalKcal;
    }

    private function dailyPctScaleFactor(): float
    {
        $dailyTarget = $this->dailyKcalTarget;

        if (! $dailyTarget || ! $this->targetDailyPct || $this->targetDailyPct < 5 || $this->targetDailyPct > 100) {
            return 1.0;
        }

        $totalKcal = (float) $this->recipe->total_kcal;

        if ($totalKcal <= 0) {
            return 1.0;
        }

        return ($dailyTarget * $this->targetDailyPct / 100) / $totalKcal;
    }

    #[Computed]
    public function dailyKcalTarget(): ?int
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->profile?->daily_kcal_target;
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
        $servings = max($this->originalServings, 1);

        $totalKcal = round((float) $this->recipe->total_kcal * $factor, 2);
        $totalProtein = round((float) $this->recipe->total_protein_g * $factor, 2);
        $totalFat = round((float) $this->recipe->total_fat_g * $factor, 2);
        $totalCarbs = round((float) $this->recipe->total_carbs_g * $factor, 2);
        $totalFiber = round((float) $this->recipe->total_fiber_g * $factor, 2);

        if ($this->mode === 'servings') {
            return [
                'kcal' => $totalKcal,
                'protein_g' => $totalProtein,
                'fat_g' => $totalFat,
                'carbs_g' => $totalCarbs,
                'fiber_g' => $totalFiber,
                'kcal_per_serving' => round((float) ($this->recipe->kcal_per_serving ?? 0), 2),
                'protein_per_serving_g' => round((float) ($this->recipe->protein_per_serving_g ?? 0), 2),
                'fat_per_serving_g' => round((float) ($this->recipe->fat_per_serving_g ?? 0), 2),
                'carbs_per_serving_g' => round((float) ($this->recipe->carbs_per_serving_g ?? 0), 2),
                'fiber_per_serving_g' => round((float) ($this->recipe->fiber_per_serving_g ?? 0), 2),
            ];
        }

        return [
            'kcal' => $totalKcal,
            'protein_g' => $totalProtein,
            'fat_g' => $totalFat,
            'carbs_g' => $totalCarbs,
            'fiber_g' => $totalFiber,
            'kcal_per_serving' => round($totalKcal / $servings, 2),
            'protein_per_serving_g' => round($totalProtein / $servings, 2),
            'fat_per_serving_g' => round($totalFat / $servings, 2),
            'carbs_per_serving_g' => round($totalCarbs / $servings, 2),
            'fiber_per_serving_g' => round($totalFiber / $servings, 2),
        ];
    }

    public function saveCalculation(): void
    {
        if (! Auth::check() || ! $this->isScaled) {
            return;
        }

        if (! in_array($this->mode, ['servings', 'kcal', 'daily_pct'], true)) {
            return;
        }

        $inputValue = match ($this->mode) {
            'kcal' => $this->targetKcal ?? 0,
            'daily_pct' => $this->targetDailyPct ?? 0,
            default => $this->targetServings ?? $this->originalServings,
        };

        CalculatorSession::create([
            'user_id' => Auth::id(),
            'recipe_id' => $this->recipe->id,
            'mode' => $this->mode,
            'input_value' => $inputValue,
            'scale_factor' => $this->scaleFactor(),
            'totals' => $this->scaledNutrition(),
        ]);

        $this->saved = true;
    }

    public function resetCalculator(): void
    {
        $this->targetServings = $this->originalServings;
        $this->targetKcal = null;
        $this->targetDailyPct = null;
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

    #[Computed]
    public function isScaled(): bool
    {
        return match ($this->mode) {
            'kcal' => $this->targetKcal !== null && $this->targetKcal > 0,
            'daily_pct' => $this->targetDailyPct !== null && $this->targetDailyPct >= 5 && $this->targetDailyPct <= 100,
            default => $this->targetServings !== $this->originalServings,
        };
    }

    public function render(): View
    {
        return view('livewire.portion-calculator');
    }
}
