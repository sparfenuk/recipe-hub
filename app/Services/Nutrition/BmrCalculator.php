<?php

namespace App\Services\Nutrition;

class BmrCalculator
{
    public const ACTIVITY_LEVELS = [
        'sedentary' => 1.2,
        'lightly_active' => 1.375,
        'moderately_active' => 1.55,
        'very_active' => 1.725,
        'extremely_active' => 1.9,
    ];

    public static function bmr(string $sex, float $weightKg, float $heightCm, int $ageYears): float
    {
        if (! in_array($sex, ['male', 'female'], true)) {
            throw new \ValueError("Invalid sex value: {$sex}. Must be 'male' or 'female'.");
        }

        $base = (10 * $weightKg) + (6.25 * $heightCm) - (5 * $ageYears);

        return $sex === 'male' ? $base + 5 : $base - 161;
    }

    public static function tdee(string $sex, float $weightKg, float $heightCm, int $ageYears, string $activityLevel): int
    {
        $factor = self::ACTIVITY_LEVELS[$activityLevel] ?? 1.2;

        return (int) round(self::bmr($sex, $weightKg, $heightCm, $ageYears) * $factor);
    }
}
