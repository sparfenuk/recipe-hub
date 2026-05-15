<?php

namespace App\Livewire;

use App\Models\CalculatorSession;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read int|null $dailyKcalTarget
 * @property-read bool $isScaled
 * @property-read array{protein_g: float, fat_g: float, carbs_g: float}|null $macroTargets
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

    /**
     * Per-recipe total kcal as displayed to the reader. Uses cookbook reference values
     * (ref_*) when present so scaling math stays consistent with the on-page numbers;
     * falls back to ingredient-computed cache when no reference is set.
     */
    private function displayTotalKcal(): float
    {
        $servings = max($this->originalServings, 1);

        if ($this->recipe->ref_kcal_per_serving !== null) {
            return (float) $this->recipe->ref_kcal_per_serving * $servings;
        }

        return (float) $this->recipe->total_kcal;
    }

    private function kcalScaleFactor(): float
    {
        $totalKcal = $this->displayTotalKcal();

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

        $totalKcal = $this->displayTotalKcal();

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

    /** @return array{protein_g: float, fat_g: float, carbs_g: float}|null */
    #[Computed]
    public function macroTargets(): ?array
    {
        $kcal = $this->dailyKcalTarget;

        if (! $kcal) {
            return null;
        }

        /** @var User $user */
        $user = Auth::user();
        $profile = $user->profile;

        return [
            'protein_g' => round($kcal * ($profile->p_pct ?? 30) / 100 / 4, 1),
            'fat_g' => round($kcal * ($profile->f_pct ?? 30) / 100 / 9, 1),
            'carbs_g' => round($kcal * ($profile->c_pct ?? 40) / 100 / 4, 1),
        ];
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

        // Display source: cookbook reference (ref_*) when seeded, else ingredient cache.
        // Fiber has no PDF reference so always falls through to computed.
        $kcalPS = (float) ($this->recipe->display_kcal_per_serving ?? 0);
        $proteinPS = (float) ($this->recipe->display_protein_per_serving_g ?? 0);
        $fatPS = (float) ($this->recipe->display_fat_per_serving_g ?? 0);
        $carbsPS = (float) ($this->recipe->display_carbs_per_serving_g ?? 0);
        $fiberPS = (float) ($this->recipe->fiber_per_serving_g ?? 0);

        $totalKcal = round($kcalPS * $servings * $factor, 2);
        $totalProtein = round($proteinPS * $servings * $factor, 2);
        $totalFat = round($fatPS * $servings * $factor, 2);
        $totalCarbs = round($carbsPS * $servings * $factor, 2);
        $totalFiber = round($fiberPS * $servings * $factor, 2);

        if ($this->mode === 'servings') {
            // Per-serving values are intrinsic to the recipe — they don't change when the
            // reader asks for more or fewer servings, only the totals do.
            return [
                'kcal' => $totalKcal,
                'protein_g' => $totalProtein,
                'fat_g' => $totalFat,
                'carbs_g' => $totalCarbs,
                'fiber_g' => $totalFiber,
                'kcal_per_serving' => round($kcalPS, 2),
                'protein_per_serving_g' => round($proteinPS, 2),
                'fat_per_serving_g' => round($fatPS, 2),
                'carbs_per_serving_g' => round($carbsPS, 2),
                'fiber_per_serving_g' => round($fiberPS, 2),
            ];
        }

        // kcal / daily_pct modes shrink the whole recipe, so per-serving values scale too.
        return [
            'kcal' => $totalKcal,
            'protein_g' => $totalProtein,
            'fat_g' => $totalFat,
            'carbs_g' => $totalCarbs,
            'fiber_g' => $totalFiber,
            'kcal_per_serving' => round($kcalPS * $factor, 2),
            'protein_per_serving_g' => round($proteinPS * $factor, 2),
            'fat_per_serving_g' => round($fatPS * $factor, 2),
            'carbs_per_serving_g' => round($carbsPS * $factor, 2),
            'fiber_per_serving_g' => round($fiberPS * $factor, 2),
        ];
    }

    public function saveCalculation(): void
    {
        if (! Auth::check() || ! $this->isScaled) {
            return;
        }

        $key = 'calculator:'.Auth::id();
        if (RateLimiter::tooManyAttempts($key, 60)) {
            $this->addError('save', __('Too many requests. Please wait before saving again.'));

            return;
        }
        RateLimiter::hit($key, 60);

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
