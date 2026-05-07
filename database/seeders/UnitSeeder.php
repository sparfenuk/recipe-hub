<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'g', 'name' => 'gram', 'type' => 'mass', 'to_base_factor' => 1.0],
            ['code' => 'kg', 'name' => 'kilogram', 'type' => 'mass', 'to_base_factor' => 1000.0],
            ['code' => 'mg', 'name' => 'milligram', 'type' => 'mass', 'to_base_factor' => 0.001],
            ['code' => 'ml', 'name' => 'milliliter', 'type' => 'volume', 'to_base_factor' => 1.0],
            ['code' => 'l', 'name' => 'liter', 'type' => 'volume', 'to_base_factor' => 1000.0],
            ['code' => 'tsp', 'name' => 'teaspoon', 'type' => 'volume', 'to_base_factor' => 4.92892],
            ['code' => 'tbsp', 'name' => 'tablespoon', 'type' => 'volume', 'to_base_factor' => 14.7868],
            ['code' => 'cup', 'name' => 'cup', 'type' => 'volume', 'to_base_factor' => 236.588],
            ['code' => 'oz', 'name' => 'ounce', 'type' => 'mass', 'to_base_factor' => 28.3495],
            ['code' => 'lb', 'name' => 'pound', 'type' => 'mass', 'to_base_factor' => 453.592],
            ['code' => 'piece', 'name' => 'piece', 'type' => 'count', 'to_base_factor' => 1.0],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['code' => $unit['code']],
                $unit,
            );
        }
    }
}
