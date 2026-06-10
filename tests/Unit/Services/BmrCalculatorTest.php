<?php

use App\Services\Nutrition\BmrCalculator;

it('computes the Mifflin-St Jeor BMR for males', function () {
    // 10*80 + 6.25*180 - 5*30 + 5 = 1780
    expect(BmrCalculator::bmr('male', 80, 180, 30))->toBe(1780.0);
});

it('computes the Mifflin-St Jeor BMR for females', function () {
    // 10*65 + 6.25*165 - 5*30 - 161 = 1370.25
    expect(BmrCalculator::bmr('female', 65, 165, 30))->toBe(1370.25);
});

it('throws on an invalid sex', function () {
    BmrCalculator::bmr('other', 70, 170, 30);
})->throws(ValueError::class);

it('applies each activity factor to the TDEE', function () {
    // BMR(male, 80, 180, 30) = 1780
    expect(BmrCalculator::tdee('male', 80, 180, 30, 'sedentary'))->toBe(2136)          // 1780 * 1.2
        ->and(BmrCalculator::tdee('male', 80, 180, 30, 'lightly_active'))->toBe(2448)   // 1780 * 1.375
        ->and(BmrCalculator::tdee('male', 80, 180, 30, 'moderately_active'))->toBe(2759) // 1780 * 1.55
        ->and(BmrCalculator::tdee('male', 80, 180, 30, 'very_active'))->toBe(3071)      // 1780 * 1.725
        ->and(BmrCalculator::tdee('male', 80, 180, 30, 'extremely_active'))->toBe(3382); // 1780 * 1.9
});

it('falls back to the sedentary (1.2) factor for an unknown activity level', function () {
    // Documented behaviour: an unrecognised activity level uses 1.2, like sedentary.
    expect(BmrCalculator::tdee('male', 80, 180, 30, 'unknown'))->toBe(2136)
        ->and(BmrCalculator::tdee('male', 80, 180, 30, 'unknown'))
        ->toBe(BmrCalculator::tdee('male', 80, 180, 30, 'sedentary'));
});
