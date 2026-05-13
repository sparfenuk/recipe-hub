<?php

namespace App\Services\Nutrition;

class BmrCalculator
{
    private const ACTIVITY_FACTORS = [
        'sedentary' => 1.2,
        'lightly_active' => 1.375,
        'moderately_active' => 1.55,
        'very_active' => 1.725,
        'extremely_active' => 1.9,
    ];

    public static function bmr(string $sex, float $weightKg, float $heightCm, int $ageYears): float
    {
        $base = (10 * $weightKg) + (6.25 * $heightCm) - (5 * $ageYears);

        return $sex === 'male' ? $base + 5 : $base - 161;
    }

    public static function tdee(string $sex, float $weightKg, float $heightCm, int $ageYears, string $activityLevel): int
    {
        $factor = self::ACTIVITY_FACTORS[$activityLevel] ?? 1.2;

        return (int) round(self::bmr($sex, $weightKg, $heightCm, $ageYears) * $factor);
    }
}
