<?php

namespace App\Services\Nutrition;

use App\Models\Unit;
use InvalidArgumentException;

class UnitConverter
{
    /**
     * @param  float  $amount  Quantity in the given unit.
     * @param  Unit  $unit  The unit the amount is expressed in.
     * @param  float|null  $densityGPerMl  Ingredient density (g/ml), required for volume units.
     * @param  float|null  $pieceWeightG  Weight of one piece in grams, required for count units.
     */
    public static function toGrams(
        float $amount,
        Unit $unit,
        ?float $densityGPerMl = null,
        ?float $pieceWeightG = null,
    ): float {
        if ($unit->isMass()) {
            return self::massToGrams($amount, $unit);
        }

        if ($unit->isVolume()) {
            return self::volumeToGrams($amount, $unit, $densityGPerMl);
        }

        return self::countToGrams($amount, $pieceWeightG);
    }

    private static function massToGrams(float $amount, Unit $unit): float
    {
        return $amount * (float) $unit->to_base_factor;
    }

    private static function volumeToGrams(float $amount, Unit $unit, ?float $densityGPerMl): float
    {
        if ($densityGPerMl === null || $densityGPerMl <= 0) {
            throw new InvalidArgumentException(
                "Density (g/ml) is required to convert volume unit [{$unit->code}] to grams.",
            );
        }

        $milliliters = $amount * (float) $unit->to_base_factor;

        return $milliliters * $densityGPerMl;
    }

    private static function countToGrams(float $amount, ?float $pieceWeightG): float
    {
        if ($pieceWeightG === null || $pieceWeightG <= 0) {
            throw new InvalidArgumentException(
                'Piece weight (grams) is required to convert count units to grams.',
            );
        }

        return $amount * $pieceWeightG;
    }
}
