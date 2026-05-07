<?php

use App\Models\Unit;
use Database\Seeders\UnitSeeder;

beforeEach(function () {
    $this->seed(UnitSeeder::class);
});

test('seeder creates all 11 units', function () {
    expect(Unit::count())->toBe(11);
});

test('seeder is idempotent', function () {
    $this->seed(UnitSeeder::class);

    expect(Unit::count())->toBe(11);
});

test('unit codes are unique', function () {
    $codes = Unit::pluck('code');

    expect($codes->unique()->count())->toBe($codes->count());
});

test('mass units have correct type', function () {
    $massCodes = ['g', 'kg', 'mg', 'oz', 'lb'];

    foreach ($massCodes as $code) {
        $unit = Unit::where('code', $code)->first();
        expect($unit->isMass())->toBeTrue("Expected {$code} to be mass");
        expect($unit->isVolume())->toBeFalse();
        expect($unit->isCount())->toBeFalse();
    }
});

test('volume units have correct type', function () {
    $volumeCodes = ['ml', 'l', 'tsp', 'tbsp', 'cup'];

    foreach ($volumeCodes as $code) {
        $unit = Unit::where('code', $code)->first();
        expect($unit->isVolume())->toBeTrue("Expected {$code} to be volume");
        expect($unit->isMass())->toBeFalse();
        expect($unit->isCount())->toBeFalse();
    }
});

test('piece is a count unit', function () {
    $unit = Unit::where('code', 'piece')->first();

    expect($unit->isCount())->toBeTrue();
    expect($unit->isMass())->toBeFalse();
    expect($unit->isVolume())->toBeFalse();
});

test('gram has base factor of 1', function () {
    $unit = Unit::where('code', 'g')->first();

    expect((float) $unit->to_base_factor)->toBe(1.0);
});

test('milliliter has base factor of 1', function () {
    $unit = Unit::where('code', 'ml')->first();

    expect((float) $unit->to_base_factor)->toBe(1.0);
});
