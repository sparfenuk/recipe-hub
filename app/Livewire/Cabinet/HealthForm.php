<?php

namespace App\Livewire\Cabinet;

use App\Models\User;
use App\Services\Nutrition\BmrCalculator;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class HealthForm extends Component
{
    public ?string $sex = null;

    public ?string $birth_date = null;

    public ?string $height_cm = null;

    public ?string $weight_kg = null;

    public ?string $activity_level = null;

    public ?int $daily_kcal_target = null;

    public int $p_pct = 30;

    public int $f_pct = 30;

    public int $c_pct = 40;

    public ?int $suggested_kcal = null;

    public bool $saved = false;

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $profile = $user->profile;

        if ($profile) {
            $this->sex = $profile->sex;
            /** @var Carbon|null $birthDate */
            $birthDate = $profile->birth_date;
            $this->birth_date = $birthDate?->format('Y-m-d');
            $this->height_cm = $profile->height_cm !== null ? (string) $profile->height_cm : null;
            $this->weight_kg = $profile->weight_kg !== null ? (string) $profile->weight_kg : null;
            $this->activity_level = $profile->activity_level;
            $this->daily_kcal_target = $profile->daily_kcal_target;
            $this->p_pct = $profile->p_pct ?? 30;
            $this->f_pct = $profile->f_pct ?? 30;
            $this->c_pct = $profile->c_pct ?? 40;
        }

        $this->computeSuggested();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'sex' => ['nullable', 'in:male,female'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'height_cm' => ['nullable', 'numeric', 'min:50', 'max:300'],
            'weight_kg' => ['nullable', 'numeric', 'min:20', 'max:500'],
            'activity_level' => ['nullable', 'in:sedentary,lightly_active,moderately_active,very_active,extremely_active'],
            'daily_kcal_target' => ['nullable', 'integer', 'min:500', 'max:10000'],
            'p_pct' => ['required', 'integer', 'min:0', 'max:100'],
            'f_pct' => ['required', 'integer', 'min:0', 'max:100'],
            'c_pct' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['sex', 'birth_date', 'height_cm', 'weight_kg', 'activity_level'])) {
            $this->computeSuggested();
        }
    }

    public function useSuggested(): void
    {
        if ($this->suggested_kcal !== null) {
            $this->daily_kcal_target = $this->suggested_kcal;
        }
    }

    public function macroSum(): int
    {
        return $this->p_pct + $this->f_pct + $this->c_pct;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->macroSum() !== 100) {
            $this->addError('p_pct', __('cabinet.macro_sum_error'));

            return;
        }

        /** @var User $user */
        $user = Auth::user();

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'sex' => $this->sex,
                'birth_date' => $this->birth_date ?: null,
                'height_cm' => $this->height_cm ?: null,
                'weight_kg' => $this->weight_kg ?: null,
                'activity_level' => $this->activity_level,
                'daily_kcal_target' => $this->daily_kcal_target,
                'p_pct' => $this->p_pct,
                'f_pct' => $this->f_pct,
                'c_pct' => $this->c_pct,
            ],
        );

        $this->saved = true;
    }

    public function render(): View
    {
        return view('livewire.cabinet.health-form')
            ->layout('components.layouts.app', [
                'title' => __('cabinet.health_profile'),
            ]);
    }

    private function computeSuggested(): void
    {
        if ($this->sex && $this->birth_date && $this->height_cm && $this->weight_kg && $this->activity_level) {
            $age = Carbon::parse($this->birth_date)->age;

            if ($age > 0) {
                $this->suggested_kcal = BmrCalculator::tdee(
                    $this->sex,
                    (float) $this->weight_kg,
                    (float) $this->height_cm,
                    $age,
                    $this->activity_level,
                );

                return;
            }
        }

        $this->suggested_kcal = null;
    }
}
