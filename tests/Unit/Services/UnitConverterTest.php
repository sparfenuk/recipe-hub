<?php

use App\Models\Unit;
use App\Services\Nutrition\UnitConverter;
use Database\Seeders\UnitSeeder;

beforeEach(function () {
    $this->seed(UnitSeeder::class);
});

function unit(string $code): Unit
{
    return Unit::where('code', $code)->firstOrFail();
}

// --- Mass conversions ---

test('grams pass through unchanged', function () {
    $result = UnitConverter::toGrams(250, unit('g'));

    expect($result)->toBe(250.0);
});

test('kilograms convert to grams', function () {
    $result = UnitConverter::toGrams(1.5, unit('kg'));

    expect($result)->toBe(1500.0);
});

test('milligrams convert to grams', function () {
    $result = UnitConverter::toGrams(500, unit('mg'));

    expect($result)->toBe(0.5);
});

test('ounces convert to grams', function () {
    $result = UnitConverter::toGrams(4, unit('oz'));

    expect($result)->toBeGreaterThan(113.39)
        ->toBeLessThan(113.40);
});

test('pounds convert to grams', function () {
    $result = UnitConverter::toGrams(2, unit('lb'));

    expect($result)->toBeGreaterThan(907.18)
        ->toBeLessThan(907.19);
});

// --- Volume conversions ---

test('milliliters convert to grams via density', function () {
    $result = UnitConverter::toGrams(250, unit('ml'), densityGPerMl: 0.92);

    expect($result)->toBe(230.0);
});

test('liters convert to grams via density', function () {
    $result = UnitConverter::toGrams(0.5, unit('l'), densityGPerMl: 1.03);

    expect($result)->toBe(515.0);
});

test('teaspoons convert to grams via density', function () {
    $result = UnitConverter::toGrams(2, unit('tsp'), densityGPerMl: 1.0);

    expect($result)->toBeGreaterThan(9.85)
        ->toBeLessThan(9.86);
});

test('tablespoons convert to grams via density', function () {
    $result = UnitConverter::toGrams(1, unit('tbsp'), densityGPerMl: 1.35);

    expect($result)->toBeGreaterThan(19.96)
        ->toBeLessThan(19.97);
});

test('cups convert to grams via density', function () {
    // 1 cup flour: density ~0.59 g/ml, cup = 236.588 ml → ~139.59g
    $result = UnitConverter::toGrams(1, unit('cup'), densityGPerMl: 0.59);

    expect($result)->toBeGreaterThan(139.5)
        ->toBeLessThan(139.6);
});

test('volume conversion throws without density', function () {
    UnitConverter::toGrams(100, unit('ml'));
})->throws(InvalidArgumentException::class, 'Density');

test('volume conversion throws with zero density', function () {
    UnitConverter::toGrams(100, unit('ml'), densityGPerMl: 0.0);
})->throws(InvalidArgumentException::class, 'Density');

// --- Count conversions ---

test('pieces convert to grams via piece weight', function () {
    // 3 eggs at 50g each = 150g
    $result = UnitConverter::toGrams(3, unit('piece'), pieceWeightG: 50.0);

    expect($result)->toBe(150.0);
});

test('fractional pieces convert correctly', function () {
    $result = UnitConverter::toGrams(0.5, unit('piece'), pieceWeightG: 120.0);

    expect($result)->toBe(60.0);
});

test('count conversion throws without piece weight', function () {
    UnitConverter::toGrams(1, unit('piece'));
})->throws(InvalidArgumentException::class, 'Piece weight');

test('count conversion throws with zero piece weight', function () {
    UnitConverter::toGrams(1, unit('piece'), pieceWeightG: 0.0);
})->throws(InvalidArgumentException::class, 'Piece weight');

// --- Mass conversions ignore optional parameters ---

test('mass conversion ignores density and piece weight', function () {
    $result = UnitConverter::toGrams(100, unit('g'), densityGPerMl: 0.92, pieceWeightG: 50.0);

    expect($result)->toBe(100.0);
});

// --- Edge cases ---

test('zero amount returns zero for mass', function () {
    expect(UnitConverter::toGrams(0, unit('kg')))->toBe(0.0);
});

test('zero amount returns zero for volume with density', function () {
    expect(UnitConverter::toGrams(0, unit('ml'), densityGPerMl: 1.0))->toBe(0.0);
});

test('zero amount returns zero for count with piece weight', function () {
    expect(UnitConverter::toGrams(0, unit('piece'), pieceWeightG: 50.0))->toBe(0.0);
});
